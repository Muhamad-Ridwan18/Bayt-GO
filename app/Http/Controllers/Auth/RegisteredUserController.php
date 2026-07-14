<?php

namespace App\Http\Controllers\Auth;

use App\Enums\CustomerType;
use App\Enums\MuthowifVerificationStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Jobs\NotifyAdminsOfMuthowifRegistration;
use App\Models\MuthowifProfile;
use App\Models\User;
use App\Services\MuthowifRejectedReregistration;
use App\Services\RegistrationOtpService;
use App\Services\UploadedImageOptimizer;
use App\Support\IntlPhone;
use App\Support\MuthowifVerificationBroadcast;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class RegisteredUserController extends Controller
{
    private const PENDING_REGISTRATION_TTL_MINUTES = 30;

    public function create(): View
    {
        $registrationOtp = app(RegistrationOtpService::class);
        $otpEnabled = $registrationOtp->otpEnabled();

        if (! session()->has('errors') && ! session()->has('registration_draft')) {
            $sessionId = session()->getId();
            Storage::disk('local')->deleteDirectory("tmp_registration/{$sessionId}");
            session()->forget('registration_files');
        }

        return view('auth.register', [
            'otpEnabled' => $otpEnabled,
        ]);
    }

    public function showVerifyWhatsApp(): View|RedirectResponse
    {
        $registrationOtp = app(RegistrationOtpService::class);
        if (! $registrationOtp->otpEnabled()) {
            return redirect()->route('register');
        }

        $pending = session('pending_registration');
        if (! is_array($pending) || empty($pending['fields']['phone'])) {
            return redirect()->route('register');
        }

        if (($pending['expires_at'] ?? 0) < now()->getTimestamp()) {
            $this->discardPendingRegistration($pending);

            return redirect()->route('register')->withErrors([
                'phone' => __('auth_otp.session_expired'),
            ]);
        }

        $phone = (string) $pending['fields']['phone'];
        $phoneVerifiedInitial = $registrationOtp->isPhoneVerifiedForRegistration($phone);

        return view('auth.register-verify-whatsapp', [
            'maskedPhone' => $this->maskedPhoneLabel($phone),
            'pendingPhone' => $phone,
            'pendingCountry' => $pending['fields']['country'] ?? null,
            'role' => (string) ($pending['fields']['role'] ?? 'customer'),
            'phoneVerifiedInitial' => $phoneVerifiedInitial,
        ]);
    }

    /**
     * @throws Throwable
     */
    public function store(Request $request): RedirectResponse
    {
        $this->cacheUploadedFiles($request);

        session([
            'registration_draft' => $request->except([
                'photo',
                'ktp_image',
                'supporting_documents',
                '_token',
            ]),
        ]);

        $this->validateRegistrationRequest($request);

        $registrationOtp = app(RegistrationOtpService::class);

        if ($registrationOtp->otpEnabled()) {
            $role = UserRole::from($request->input('role'));
            if ($role === UserRole::Customer || $role === UserRole::Muthowif) {
                $old = session('pending_registration');
                if (is_array($old)) {
                    $this->discardPendingRegistration($old);
                }

                $this->persistPendingRegistration($request);
                $registrationOtp->clearVerificationSession();

                try {
                    $registrationOtp->send($request->string('phone')->toString(), $request->string('name')->toString());
                } catch (ValidationException $e) {
                    $pending = session('pending_registration');
                    $this->discardPendingRegistration($pending);

                    return redirect()
                        ->route('register')
                        ->withErrors($e->errors())
                        ->withInput();
                }

                return redirect()->route('register.verify-whatsapp');
            }
        }

        return $this->commitRegistrationDirectly($request, $registrationOtp);
    }

    /**
     * @throws Throwable
     */
    public function complete(Request $request): RedirectResponse
    {
        $registrationOtp = app(RegistrationOtpService::class);
        if (! $registrationOtp->otpEnabled()) {
            return redirect()->route('register');
        }

        $pending = session('pending_registration');
        if (! is_array($pending) || empty($pending['fields'])) {
            return redirect()->route('register')->withErrors([
                'phone' => __('auth_otp.session_expired'),
            ]);
        }

        if (($pending['expires_at'] ?? 0) < now()->getTimestamp()) {
            $this->discardPendingRegistration($pending);

            return redirect()->route('register')->withErrors([
                'phone' => __('auth_otp.session_expired'),
            ]);
        }

        $request->merge([
            'otp' => preg_replace('/\D+/', '', (string) $request->input('otp', '')),
        ]);

        $request->validate([
            'otp' => ['required', 'string', 'size:6', 'regex:/^\d{6}$/'],
        ]);

        $fields = $pending['fields'];
        $phone = (string) ($fields['phone'] ?? '');

        try {
            $normalized = $registrationOtp->verify($phone, $request->string('otp')->toString());
            $registrationOtp->rememberVerifiedPhone($normalized);
        } catch (ValidationException $e) {
            return redirect()->route('register.verify-whatsapp')->withErrors($e->errors());
        }

        try {
            $password = Crypt::decryptString((string) $fields['password_enc']);
        } catch (Throwable) {
            $this->discardPendingRegistration($pending);

            return redirect()->route('register')->withErrors([
                'phone' => __('auth_otp.session_expired'),
            ]);
        }

        $input = $fields;
        unset($input['password_enc']);
        $input['password'] = $password;

        $muthowifFiles = $pending['muthowif_files'] ?? null;

        return $this->commitRegistrationFromInput($input, $muthowifFiles, $registrationOtp, $pending);
    }

    public function removeCachedRegistrationFile(Request $request): RedirectResponse
    {
        $request->validate([
            'type' => ['required', 'string', Rule::in(['photo', 'ktp_image', 'supporting_document'])],
            'file_id' => ['nullable', 'string', 'uuid'],
            'path' => ['nullable', 'string', 'max:500'],
        ]);

        $type = $request->string('type')->toString();

        if ($type === 'photo') {
            $this->deleteCachedRegistrationFile('registration_files.photo');
        } elseif ($type === 'ktp_image') {
            $this->deleteCachedRegistrationFile('registration_files.ktp_image');
        } else {
            $targetId = $request->string('file_id')->toString();
            $targetPath = $request->string('path')->toString();
            if ($targetId === '' && $targetPath === '') {
                return redirect()->route('register');
            }

            $docs = session('registration_files.supporting_documents', []);
            if (! is_array($docs)) {
                return redirect()->route('register');
            }

            $removed = false;
            foreach ($docs as $i => $doc) {
                if (! is_array($doc)) {
                    continue;
                }

                $matches = ($targetId !== '' && ($doc['id'] ?? '') === $targetId)
                    || ($targetId === '' && $targetPath !== '' && ($doc['path'] ?? '') === $targetPath);

                if (! $matches) {
                    continue;
                }

                $storedPath = (string) ($doc['path'] ?? '');
                if ($storedPath !== '' && Storage::disk('local')->exists($storedPath)) {
                    Storage::disk('local')->delete($storedPath);
                }

                unset($docs[$i]);
                $removed = true;
                break;
            }

            if (! $removed) {
                return redirect()->route('register');
            }

            session(['registration_files.supporting_documents' => array_values($docs)]);
        }

        $draft = session('registration_draft', []);

        return redirect()
            ->route('register')
            ->withInput(is_array($draft) ? $draft : [])
            ->with('status', __('guest.register.file_removed'));
    }

    public function updatePendingPhone(Request $request): RedirectResponse
    {
        $registrationOtp = app(RegistrationOtpService::class);
        if (! $registrationOtp->otpEnabled()) {
            return redirect()->route('register');
        }

        $pending = session('pending_registration');
        if (! is_array($pending) || empty($pending['fields'])) {
            return redirect()->route('register')->withErrors([
                'phone' => __('auth_otp.session_expired'),
            ]);
        }

        if (($pending['expires_at'] ?? 0) < now()->getTimestamp()) {
            $this->discardPendingRegistration($pending);

            return redirect()->route('register')->withErrors([
                'phone' => __('auth_otp.session_expired'),
            ]);
        }

        if ($request->has('country') && $request->string('country')->toString() === '') {
            $request->merge(['country' => null]);
        }

        $phoneInput = (string) $request->input('phone', '');

        $request->validate([
            'phone' => ['required', 'string'],
            'country' => ['nullable', 'string', 'size:2', 'regex:/^[A-Za-z]{2}$/'],
        ]);

        $normalized = IntlPhone::normalize($phoneInput);
        $fonnteDial = IntlPhone::fonnteDial($phoneInput);
        if ($normalized === null || $fonnteDial === null || strlen($normalized) < 8 || strlen($normalized) > 15) {
            throw ValidationException::withMessages([
                'phone' => ['Format nomor WhatsApp tidak valid. Gunakan +kode negara dan nomor lengkap, atau nomor lokal sesuai wilayah default (PHONE_DEFAULT_REGION).'],
            ]);
        }

        $role = UserRole::tryFrom((string) ($pending['fields']['role'] ?? ''));
        if ($role === UserRole::Customer) {
            $request->validate([
                'phone' => [Rule::unique('users', 'phone')],
            ]);
        }

        $previousPhone = (string) ($pending['fields']['phone'] ?? '');
        $prevNormalized = IntlPhone::normalize($previousPhone);
        $isResend = ($prevNormalized !== null && $prevNormalized === $normalized);

        $pending['fields']['phone'] = $phoneInput;
        $pending['fields']['country'] = $this->nullableCountryIso($request->input('country'));
        session(['pending_registration' => $pending]);
        $registrationOtp->clearVerificationSession();

        try {
            $registrationOtp->send($request->string('phone')->toString(), $pending['fields']['name'] ?? null);
        } catch (ValidationException $e) {
            return redirect()
                ->route('register.verify-whatsapp')
                ->withErrors($e->errors());
        }

        $statusKey = $isResend ? 'auth_otp.otp_resent' : 'auth_otp.phone_updated';

        return redirect()
            ->route('register.verify-whatsapp')
            ->with('status', __($statusKey));
    }

    private function validateRegistrationRequest(Request $request): void
    {
        if ($request->has('country') && $request->string('country')->toString() === '') {
            $request->merge(['country' => null]);
        }

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                app(MuthowifRejectedReregistration::class)->emailUniqueRule(
                    $request->input('role'),
                    is_string($request->input('email')) ? $request->input('email') : null,
                ),
            ],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role' => ['required', Rule::enum(UserRole::class)->only([UserRole::Customer, UserRole::Muthowif])],
            'customer_type' => ['required_if:role,customer', 'nullable', Rule::enum(CustomerType::class)],
            'phone' => ['required_if:role,customer', 'required_if:role,muthowif', 'nullable', 'string', 'min:8', 'max:24'],
            'country' => ['nullable', 'string', 'size:2', 'regex:/^[A-Za-z]{2}$/'],
            'address' => ['required_if:role,customer', 'required_if:role,muthowif', 'nullable', 'string', 'max:2000'],
            'work_location' => ['required_if:role,muthowif', 'nullable', 'string', 'max:255'],
            'ppui_number' => ['required_if:customer_type,company', 'nullable', 'string', 'max:64'],
            'nik' => ['required_if:role,muthowif', 'nullable', 'string', 'size:16', 'regex:/^\d{16}$/'],
            'birth_date' => ['required_if:role,muthowif', 'nullable', 'date', 'before:today', 'after:1900-01-01'],
            'passport_number' => ['required_if:role,muthowif', 'nullable', 'string', 'max:64'],
            'languages' => ['required_if:role,muthowif', 'nullable', 'array'],
            'languages.*' => ['nullable', 'string', 'max:500'],
            'educations' => ['nullable', 'array'],
            'educations.*' => ['nullable', 'string', 'max:2000'],
            'work_experiences' => ['nullable', 'array'],
            'work_experiences.*' => ['nullable', 'string', 'max:2000'],
            'reference_text' => ['nullable', 'string', 'max:10000'],
            'photo' => [Rule::requiredIf(fn () => $request->input('role') === 'muthowif' && ! session()->has('registration_files.photo')), 'nullable', 'file', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            'ktp_image' => [Rule::requiredIf(fn () => $request->input('role') === 'muthowif' && ! session()->has('registration_files.ktp_image')), 'nullable', 'file', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            'supporting_documents' => ['nullable', 'array', 'max:20'],
            'supporting_documents.*' => ['file', 'mimes:pdf,jpeg,jpg,png,webp', 'max:10240'],
            'muthowif_referral_code' => ['nullable', 'string', 'max:16'],
        ];

        $request->validate($rules);

        if ($request->string('role')->toString() === UserRole::Customer->value) {
            $request->validate([
                'phone' => [Rule::unique('users', 'phone')],
            ]);
        }

        if ($request->string('role')->toString() === UserRole::Muthowif->value) {
            $languages = $this->requestStringList($request->input('languages'));
            if (count($languages) === 0) {
                throw ValidationException::withMessages([
                    'languages' => 'Isi minimal satu bahasa.',
                ]);
            }

            $codeRaw = $request->input('muthowif_referral_code');
            $code = is_string($codeRaw) ? strtoupper(trim($codeRaw)) : '';
            if ($code !== '') {
                $exists = MuthowifProfile::query()
                    ->where('referral_code', $code)
                    ->where('verification_status', MuthowifVerificationStatus::Approved)
                    ->exists();
                if (! $exists) {
                    throw ValidationException::withMessages([
                        'muthowif_referral_code' => [__('auth_custom.muthowif_referral_invalid')],
                    ]);
                }
            }
            $request->merge(['muthowif_referral_code' => $code !== '' ? $code : null]);
        }
    }

    /**
     * Registrasi langsung (OTP tidak aktif — tidak ada langkah WhatsApp).
     *
     * @throws Throwable
     */
    private function commitRegistrationDirectly(Request $request, RegistrationOtpService $registrationOtp): RedirectResponse
    {
        $role = UserRole::from($request->input('role'));
        $stagedMuthowifFiles = $role === UserRole::Muthowif
            ? $this->stageMuthowifFilesFromRequest($request)
            : null;
        $user = null;
        $muthowifProfileId = null;

        try {
            DB::beginTransaction();

            $reregistration = app(MuthowifRejectedReregistration::class);
            $existingRejected = $role === UserRole::Muthowif
                ? $reregistration->findByEmail($request->string('email')->toString())
                : null;

            if ($existingRejected !== null) {
                $user = $existingRejected;
                $user->update([
                    'name' => $request->string('name')->toString(),
                    'password' => Hash::make($request->string('password')->toString()),
                    'country' => $this->nullableCountryIso($request->input('country')),
                ]);
            } else {
                $user = User::create([
                    'name' => $request->string('name')->toString(),
                    'email' => $request->string('email')->toString(),
                    'password' => Hash::make($request->string('password')->toString()),
                    'role' => $role,
                    'phone' => $role === UserRole::Customer ? $request->string('phone')->toString() : null,
                    'country' => $this->nullableCountryIso($request->input('country')),
                    'address' => $role === UserRole::Customer ? $request->string('address')->toString() : null,
                    'customer_type' => $role === UserRole::Customer
                        ? CustomerType::from($request->string('customer_type')->toString())
                        : null,
                    'ppui_number' => $role === UserRole::Customer
                        && $request->string('customer_type')->toString() === CustomerType::Company->value
                        ? $request->string('ppui_number')->toString()
                        : null,
                ]);
            }

            if ($user->isMuthowif()) {
                if ($stagedMuthowifFiles === null) {
                    throw new \RuntimeException('Berkas muthowif hilang.');
                }

                $finalDir = 'muthowif_documents/'.$user->id;
                $languages = $this->requestStringList($request->input('languages'));
                $educations = $this->requestStringList($request->input('educations'));
                $workExperiences = $this->requestStringList($request->input('work_experiences'));

                $codeRaw = $request->input('muthowif_referral_code');
                $code = is_string($codeRaw) ? strtoupper(trim($codeRaw)) : '';
                $referredById = $code !== '' ? $this->resolveReferredByMuthowifProfileId($code) : null;

                $workLocation = $request->input('work_location');
                $workLocation = is_string($workLocation) ? trim($workLocation) : null;

                $profilePayload = [
                    'phone' => $request->input('phone'),
                    'address' => $request->input('address'),
                    'work_location' => filled($workLocation) ? $workLocation : null,
                    'nik' => $request->input('nik'),
                    'birth_date' => $request->input('birth_date'),
                    'passport_number' => $request->input('passport_number'),
                    'languages' => $languages,
                    'educations' => $educations,
                    'work_experiences' => $workExperiences,
                    'reference_text' => $request->input('reference_text'),
                    'photo_path' => $finalDir.'/'.basename($stagedMuthowifFiles['photo_path']),
                    'ktp_image_path' => $finalDir.'/'.basename($stagedMuthowifFiles['ktp_path']),
                    'verification_status' => MuthowifVerificationStatus::Pending,
                    'verified_at' => null,
                    'rejection_reason' => null,
                    'referred_by_muthowif_profile_id' => $referredById,
                ];

                if ($existingRejected !== null) {
                    $profile = $user->muthowifProfile;
                    if ($profile === null) {
                        throw new \RuntimeException('Profil muthowif hilang.');
                    }
                    $reregistration->discardStoredDocuments($profile);
                    $profile->update($profilePayload);
                } else {
                    $profile = MuthowifProfile::create(array_merge($profilePayload, [
                        'user_id' => $user->id,
                    ]));
                }
                $muthowifProfileId = (string) $profile->getKey();

                foreach ($stagedMuthowifFiles['supporting'] as $row) {
                    $profile->supportingDocuments()->create([
                        'path' => $finalDir.'/'.basename($row['path']),
                        'original_name' => $row['original_name'],
                        'sort_order' => $row['sort_order'],
                    ]);
                }
            }

            DB::commit();

            if ($user->isMuthowif() && $stagedMuthowifFiles !== null) {
                $this->finalizeStagedMuthowifFiles((string) $user->id, $stagedMuthowifFiles);
            }

            session()->forget(['registration_files', 'registration_draft']);
            $sessionId = session()->getId();
            Storage::disk('local')->deleteDirectory("tmp_registration/{$sessionId}");

            if ($registrationOtp->otpEnabled()) {
                $registrationOtp->clearVerificationSession();
            }
        } catch (Throwable $e) {
            DB::rollBack();

            if ($stagedMuthowifFiles !== null) {
                Storage::disk('local')->deleteDirectory($stagedMuthowifFiles['staging_dir']);
            }

            throw $e;
        }

        NotifyAdminsOfMuthowifRegistration::afterMuthowifRegistered($muthowifProfileId);
        if ($muthowifProfileId !== null) {
            MuthowifVerificationBroadcast::afterResponse($muthowifProfileId);
        }

        event(new Registered($user));

        if ($user->isMuthowif()) {
            return redirect()->route('muthowif.registration.pending');
        }

        if ($user->isCompanyCustomer() && ! $user->is_company_approved) {
            session(['pending_company_id' => $user->id]);

            return redirect()->route('company.registration.pending');
        }

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }

    /**
     * @param  array<string, mixed>  $input
     * @param  array{photo_path: string, ktp_path: string, supporting: list<array{path: string, original_name: string, sort_order: int}>}|null  $muthowifFiles
     * @param  array<string, mixed>|null  $pending
     *
     * @throws Throwable
     */
    private function commitRegistrationFromInput(array $input, ?array $muthowifFiles, RegistrationOtpService $registrationOtp, ?array $pending): RedirectResponse
    {
        $user = null;
        $muthowifProfileId = null;

        try {
            DB::beginTransaction();

            $role = UserRole::from($input['role']);

            $reregistration = app(MuthowifRejectedReregistration::class);
            $existingRejected = $role === UserRole::Muthowif
                ? $reregistration->findByEmail((string) $input['email'])
                : null;

            if ($existingRejected !== null) {
                $user = $existingRejected;
                $user->update([
                    'name' => (string) $input['name'],
                    'password' => Hash::make((string) $input['password']),
                    'country' => $this->nullableCountryIso($input['country'] ?? null),
                ]);
            } else {
                $user = User::create([
                    'name' => (string) $input['name'],
                    'email' => (string) $input['email'],
                    'password' => Hash::make((string) $input['password']),
                    'role' => $role,
                    'phone' => $role === UserRole::Customer ? (string) $input['phone'] : null,
                    'country' => $this->nullableCountryIso($input['country'] ?? null),
                    'address' => $role === UserRole::Customer ? (string) $input['address'] : null,
                    'customer_type' => $role === UserRole::Customer
                        ? CustomerType::from((string) $input['customer_type'])
                        : null,
                    'ppui_number' => $role === UserRole::Customer
                        && (string) $input['customer_type'] === CustomerType::Company->value
                        ? (string) ($input['ppui_number'] ?? '')
                        : null,
                ]);
            }

            if ($user->isMuthowif()) {
                if ($muthowifFiles === null) {
                    throw new \RuntimeException('Berkas muthowif hilang.');
                }

                $finalDir = 'muthowif_documents/'.$user->id;
                $languages = $this->requestStringList($input['languages'] ?? null);
                $educations = $this->requestStringList($input['educations'] ?? null);
                $workExperiences = $this->requestStringList($input['work_experiences'] ?? null);

                $referralRaw = $input['muthowif_referral_code'] ?? null;
                $referralNorm = is_string($referralRaw) ? strtoupper(trim($referralRaw)) : '';
                $referredById = $referralNorm !== '' ? $this->resolveReferredByMuthowifProfileId($referralNorm) : null;

                $workLocation = $input['work_location'] ?? null;
                $workLocation = is_string($workLocation) ? trim($workLocation) : null;

                $profilePayload = [
                    'phone' => $input['phone'],
                    'address' => $input['address'],
                    'work_location' => filled($workLocation) ? $workLocation : null,
                    'nik' => $input['nik'],
                    'birth_date' => $input['birth_date'],
                    'passport_number' => $input['passport_number'],
                    'languages' => $languages,
                    'educations' => $educations,
                    'work_experiences' => $workExperiences,
                    'reference_text' => $input['reference_text'] ?? null,
                    'photo_path' => $finalDir.'/'.basename($muthowifFiles['photo_path']),
                    'ktp_image_path' => $finalDir.'/'.basename($muthowifFiles['ktp_path']),
                    'verification_status' => MuthowifVerificationStatus::Pending,
                    'verified_at' => null,
                    'rejection_reason' => null,
                    'referred_by_muthowif_profile_id' => $referredById,
                ];

                if ($existingRejected !== null) {
                    $profile = $user->muthowifProfile;
                    if ($profile === null) {
                        throw new \RuntimeException('Profil muthowif hilang.');
                    }
                    $reregistration->discardStoredDocuments($profile);
                    $profile->update($profilePayload);
                } else {
                    $profile = MuthowifProfile::create(array_merge($profilePayload, [
                        'user_id' => $user->id,
                    ]));
                }
                $muthowifProfileId = (string) $profile->getKey();

                foreach ($muthowifFiles['supporting'] as $row) {
                    $profile->supportingDocuments()->create([
                        'path' => $finalDir.'/'.basename($row['path']),
                        'original_name' => $row['original_name'],
                        'sort_order' => $row['sort_order'],
                    ]);
                }
            }

            DB::commit();

            if ($user->isMuthowif() && $muthowifFiles !== null) {
                $this->finalizePendingMuthowifFiles((string) $user->id, $muthowifFiles);
            }

            if ($registrationOtp->otpEnabled()) {
                $registrationOtp->clearVerificationSession();
            }
            if ($pending !== null) {
                $this->discardPendingRegistration($pending);
            }
        } catch (Throwable $e) {
            DB::rollBack();

            throw $e;
        }

        NotifyAdminsOfMuthowifRegistration::afterMuthowifRegistered($muthowifProfileId);
        if ($muthowifProfileId !== null) {
            MuthowifVerificationBroadcast::afterResponse($muthowifProfileId);
        }

        event(new Registered($user));

        if ($user->isMuthowif()) {
            return redirect()->route('muthowif.registration.pending');
        }

        if ($user->isCompanyCustomer() && ! $user->is_company_approved) {
            session(['pending_company_id' => $user->id]);

            return redirect()->route('company.registration.pending');
        }

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }

    private function resolveReferredByMuthowifProfileId(string $normalizedReferralCode): ?string
    {
        $code = strtoupper(trim($normalizedReferralCode));
        if ($code === '') {
            return null;
        }

        $id = MuthowifProfile::query()
            ->where('referral_code', $code)
            ->where('verification_status', MuthowifVerificationStatus::Approved)
            ->value('id');

        return $id !== null ? (string) $id : null;
    }

    private function persistPendingRegistration(Request $request): void
    {
        $pendingId = (string) Str::uuid();
        $base = 'pending_registration/'.$pendingId;

        $fields = $request->except([
            '_token', 'password', 'password_confirmation', 'photo', 'ktp_image', 'supporting_documents',
        ]);
        $fields['password_enc'] = Crypt::encryptString($request->string('password')->toString());

        $muthowifFiles = null;
        if ($request->string('role')->toString() === UserRole::Muthowif->value) {
            Storage::disk('local')->makeDirectory($base);
            $optimizer = app(UploadedImageOptimizer::class);

            // Handle Photo (uploaded or cached)
            $photoFile = $request->file('photo');
            if ($photoFile && $photoFile->isValid()) {
                $photoPath = $optimizer->store($photoFile, $base, 'local', 'profile');
            } else {
                $cachedPhoto = session('registration_files.photo');
                $photoPath = $base.'/'.basename($cachedPhoto['path']);
                Storage::disk('local')->move($cachedPhoto['path'], $photoPath);
                $photoPath = $optimizer->optimizeStoredPath($photoPath, 'local', 'profile');
            }

            // Handle KTP Image (uploaded or cached)
            $ktpFile = $request->file('ktp_image');
            if ($ktpFile && $ktpFile->isValid()) {
                $ktpPath = $optimizer->store($ktpFile, $base, 'local', 'profile');
            } else {
                $cachedKtp = session('registration_files.ktp_image');
                $ktpPath = $base.'/'.basename($cachedKtp['path']);
                Storage::disk('local')->move($cachedKtp['path'], $ktpPath);
                $ktpPath = $optimizer->optimizeStoredPath($ktpPath, 'local', 'profile');
            }

            $supporting = [];
            $files = $request->file('supporting_documents', []);
            if (! is_array($files)) {
                $files = array_filter([$files]);
            }
            foreach ($files as $index => $file) {
                if ($file && $file->isValid()) {
                    $supporting[] = [
                        'path' => $optimizer->store($file, $base, 'local', 'document'),
                        'original_name' => $file->getClientOriginalName(),
                        'sort_order' => count($supporting),
                    ];
                }
            }

            $cachedDocs = session('registration_files.supporting_documents', []);
            foreach ($cachedDocs as $doc) {
                if (Storage::disk('local')->exists($doc['path'])) {
                    $dest = $base.'/'.basename($doc['path']);
                    Storage::disk('local')->move($doc['path'], $dest);
                    $supporting[] = [
                        'path' => $dest,
                        'original_name' => $doc['original_name'],
                        'sort_order' => count($supporting),
                    ];
                }
            }

            $muthowifFiles = [
                'photo_path' => $photoPath,
                'ktp_path' => $ktpPath,
                'supporting' => $supporting,
            ];
        }

        session([
            'pending_registration' => [
                'id' => $pendingId,
                'expires_at' => now()->addMinutes(self::PENDING_REGISTRATION_TTL_MINUTES)->getTimestamp(),
                'fields' => $fields,
                'muthowif_files' => $muthowifFiles,
            ],
        ]);

        session()->forget(['registration_files', 'registration_draft']);
        $sessionId = session()->getId();
        Storage::disk('local')->deleteDirectory("tmp_registration/{$sessionId}");
    }

    /**
     * @param  array<string, mixed>|null  $pending
     */
    private function discardPendingRegistration(?array $pending): void
    {
        if ($pending === null) {
            return;
        }
        $id = $pending['id'] ?? null;
        if (is_string($id) && $id !== '') {
            Storage::disk('local')->deleteDirectory('pending_registration/'.$id);
        }
        session()->forget('pending_registration');
    }

    private function maskedPhoneLabel(string $phoneInput): string
    {
        $n = IntlPhone::normalize($phoneInput);
        if ($n === null || strlen($n) < 4) {
            return '••••';
        }

        return str_repeat('•', max(0, strlen($n) - 4)).substr($n, -4);
    }

    private function nullableCountryIso(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }
        $c = strtoupper(trim($value));
        if (strlen($c) !== 2 || ! ctype_alpha($c)) {
            return null;
        }

        return $c;
    }

    /**
     * @param  array<int, mixed>|null  $input
     * @return list<string>
     */
    private function requestStringList(?array $input): array
    {
        if ($input === null) {
            return [];
        }

        return array_values(array_filter(
            array_map(static fn ($s): string => is_string($s) ? trim($s) : '', $input),
            static fn (string $s): bool => $s !== ''
        ));
    }

    /**
     * Optimasi & simpan berkas muthowif di luar transaksi DB.
     *
     * @return array{staging_dir: string, photo_path: string, ktp_path: string, supporting: list<array{path: string, original_name: string, sort_order: int}>}
     */
    private function stageMuthowifFilesFromRequest(Request $request): array
    {
        $stagingDir = 'tmp_muthowif_reg/'.Str::uuid();
        Storage::disk('local')->makeDirectory($stagingDir);
        $optimizer = app(UploadedImageOptimizer::class);

        $photoFile = $request->file('photo');
        if ($photoFile && $photoFile->isValid()) {
            $photoPath = $optimizer->store($photoFile, $stagingDir, 'local', 'profile');
        } else {
            $cachedPhoto = session('registration_files.photo');
            $photoPath = $stagingDir.'/'.basename($cachedPhoto['path']);
            Storage::disk('local')->move($cachedPhoto['path'], $photoPath);
            $photoPath = $optimizer->optimizeStoredPath($photoPath, 'local', 'profile');
        }

        $ktpFile = $request->file('ktp_image');
        if ($ktpFile && $ktpFile->isValid()) {
            $ktpPath = $optimizer->store($ktpFile, $stagingDir, 'local', 'profile');
        } else {
            $cachedKtp = session('registration_files.ktp_image');
            $ktpPath = $stagingDir.'/'.basename($cachedKtp['path']);
            Storage::disk('local')->move($cachedKtp['path'], $ktpPath);
            $ktpPath = $optimizer->optimizeStoredPath($ktpPath, 'local', 'profile');
        }

        $supporting = [];
        $files = $request->file('supporting_documents', []);
        if (! is_array($files)) {
            $files = array_filter([$files]);
        }
        foreach ($files as $file) {
            if ($file && $file->isValid()) {
                $supporting[] = [
                    'path' => $optimizer->store($file, $stagingDir, 'local', 'document'),
                    'original_name' => $file->getClientOriginalName(),
                    'sort_order' => count($supporting),
                ];
            }
        }

        $cachedDocs = session('registration_files.supporting_documents', []);
        foreach ($cachedDocs as $doc) {
            if (Storage::disk('local')->exists($doc['path'])) {
                $dest = $stagingDir.'/'.basename($doc['path']);
                Storage::disk('local')->move($doc['path'], $dest);
                $supporting[] = [
                    'path' => $dest,
                    'original_name' => $doc['original_name'],
                    'sort_order' => count($supporting),
                ];
            }
        }

        return [
            'staging_dir' => $stagingDir,
            'photo_path' => $photoPath,
            'ktp_path' => $ktpPath,
            'supporting' => $supporting,
        ];
    }

    /**
     * @param  array{staging_dir: string, photo_path: string, ktp_path: string, supporting: list<array{path: string, original_name: string, sort_order: int}>}  $staged
     */
    private function finalizeStagedMuthowifFiles(string $userId, array $staged): void
    {
        $finalDir = 'muthowif_documents/'.$userId;
        Storage::disk('local')->makeDirectory($finalDir);

        $this->moveRegistrationFile($staged['photo_path'], $finalDir.'/'.basename($staged['photo_path']));
        $this->moveRegistrationFile($staged['ktp_path'], $finalDir.'/'.basename($staged['ktp_path']));

        foreach ($staged['supporting'] as $row) {
            $this->moveRegistrationFile($row['path'], $finalDir.'/'.basename($row['path']));
        }

        Storage::disk('local')->deleteDirectory($staged['staging_dir']);
    }

    /**
     * @param  array{photo_path: string, ktp_path: string, supporting: list<array{path: string, original_name: string, sort_order: int}>}  $pendingFiles
     */
    private function finalizePendingMuthowifFiles(string $userId, array $pendingFiles): void
    {
        $finalDir = 'muthowif_documents/'.$userId;
        Storage::disk('local')->makeDirectory($finalDir);

        $this->moveRegistrationFile($pendingFiles['photo_path'], $finalDir.'/'.basename($pendingFiles['photo_path']));
        $this->moveRegistrationFile($pendingFiles['ktp_path'], $finalDir.'/'.basename($pendingFiles['ktp_path']));

        foreach ($pendingFiles['supporting'] as $row) {
            $this->moveRegistrationFile($row['path'], $finalDir.'/'.basename($row['path']));
        }
    }

    private function moveRegistrationFile(string $from, string $to): void
    {
        if (Storage::disk('local')->exists($from)) {
            Storage::disk('local')->move($from, $to);
        }
    }

    private function deleteCachedRegistrationFile(string $sessionKey): void
    {
        $file = session($sessionKey);
        if (is_array($file) && ! empty($file['path']) && Storage::disk('local')->exists($file['path'])) {
            Storage::disk('local')->delete($file['path']);
        }

        session()->forget($sessionKey);
    }

    private function cacheUploadedFiles(Request $request): void
    {
        $sessionId = session()->getId();
        $baseDir = "tmp_registration/{$sessionId}";
        $optimizer = app(UploadedImageOptimizer::class);

        // Cache photo
        if ($request->hasFile('photo') && $request->file('photo')->isValid()) {
            $oldPhoto = session('registration_files.photo');
            if ($oldPhoto && Storage::disk('local')->exists($oldPhoto['path'])) {
                Storage::disk('local')->delete($oldPhoto['path']);
            }
            $path = $optimizer->store($request->file('photo'), $baseDir.'/photo', 'local', 'profile');
            session([
                'registration_files.photo' => [
                    'path' => $path,
                    'original_name' => $request->file('photo')->getClientOriginalName(),
                ],
            ]);
        }

        // Cache KTP Image
        if ($request->hasFile('ktp_image') && $request->file('ktp_image')->isValid()) {
            $oldKtp = session('registration_files.ktp_image');
            if ($oldKtp && Storage::disk('local')->exists($oldKtp['path'])) {
                Storage::disk('local')->delete($oldKtp['path']);
            }
            $path = $optimizer->store($request->file('ktp_image'), $baseDir.'/ktp_image', 'local', 'profile');
            session([
                'registration_files.ktp_image' => [
                    'path' => $path,
                    'original_name' => $request->file('ktp_image')->getClientOriginalName(),
                ],
            ]);
        }

        // Cache supporting documents
        if ($request->hasFile('supporting_documents')) {
            $files = $request->file('supporting_documents');
            if (! is_array($files)) {
                $files = array_filter([$files]);
            }

            $cachedDocs = session('registration_files.supporting_documents', []);

            foreach ($files as $file) {
                if ($file && $file->isValid()) {
                    $path = $optimizer->store($file, $baseDir.'/supporting', 'local', 'document');
                    $cachedDocs[] = [
                        'id' => (string) Str::uuid(),
                        'path' => $path,
                        'original_name' => $file->getClientOriginalName(),
                    ];
                }
            }

            session(['registration_files.supporting_documents' => $cachedDocs]);
        }
    }
}
