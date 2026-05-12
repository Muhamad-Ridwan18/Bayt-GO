<?php

namespace App\Http\Controllers\Auth;

use App\Enums\CustomerType;
use App\Enums\MuthowifVerificationStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\MuthowifProfile;
use App\Models\User;
use App\Services\RegistrationOtpService;
use App\Support\IntlPhone;
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

        $fields = $pending['fields'];
        $phone = (string) ($fields['phone'] ?? '');
        if (! $registrationOtp->isPhoneVerifiedForRegistration($phone)) {
            return redirect()->route('register.verify-whatsapp')->withErrors([
                'otp' => ['Verifikasi OTP WhatsApp wajib untuk menyelesaikan pendaftaran.'],
            ]);
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
        if ($prevNormalized !== null && $prevNormalized === $normalized) {
            return redirect()->route('register.verify-whatsapp');
        }

        $pending['fields']['phone'] = $phoneInput;
        $pending['fields']['country'] = $this->nullableCountryIso($request->input('country'));
        session(['pending_registration' => $pending]);
        $registrationOtp->clearVerificationSession();

        return redirect()
            ->route('register.verify-whatsapp')
            ->with('status', __('auth_otp.phone_updated'));
    }

    private function validateRegistrationRequest(Request $request): void
    {
        if ($request->has('country') && $request->string('country')->toString() === '') {
            $request->merge(['country' => null]);
        }

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role' => ['required', Rule::enum(UserRole::class)->only([UserRole::Customer, UserRole::Muthowif])],
            'customer_type' => ['required_if:role,customer', 'nullable', Rule::enum(CustomerType::class)],
            'phone' => ['required_if:role,customer', 'required_if:role,muthowif', 'nullable', 'string', 'min:8', 'max:24'],
            'country' => ['nullable', 'string', 'size:2', 'regex:/^[A-Za-z]{2}$/'],
            'address' => ['required_if:role,customer', 'required_if:role,muthowif', 'nullable', 'string', 'max:2000'],
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
            'photo' => ['required_if:role,muthowif', 'nullable', 'file', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            'ktp_image' => ['required_if:role,muthowif', 'nullable', 'file', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            'supporting_documents' => ['nullable', 'array', 'max:20'],
            'supporting_documents.*' => ['file', 'mimes:pdf,jpeg,jpg,png,webp', 'max:10240'],
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
        }
    }

    /**
     * Registrasi langsung (OTP tidak aktif — tidak ada langkah WhatsApp).
     *
     * @throws Throwable
     */
    private function commitRegistrationDirectly(Request $request, RegistrationOtpService $registrationOtp): RedirectResponse
    {
        $storedDir = null;
        $user = null;

        try {
            DB::beginTransaction();

            $role = UserRole::from($request->input('role'));

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

            if ($user->isMuthowif()) {
                $storedDir = 'muthowif_documents/'.$user->id;

                $photoPath = $request->file('photo')->store($storedDir, 'local');
                $ktpPath = $request->file('ktp_image')->store($storedDir, 'local');

                $languages = $this->requestStringList($request->input('languages'));
                $educations = $this->requestStringList($request->input('educations'));
                $workExperiences = $this->requestStringList($request->input('work_experiences'));

                $profile = MuthowifProfile::create([
                    'user_id' => $user->id,
                    'phone' => $request->input('phone'),
                    'address' => $request->input('address'),
                    'nik' => $request->input('nik'),
                    'birth_date' => $request->input('birth_date'),
                    'passport_number' => $request->input('passport_number'),
                    'languages' => $languages,
                    'educations' => $educations,
                    'work_experiences' => $workExperiences,
                    'reference_text' => $request->input('reference_text'),
                    'photo_path' => $photoPath,
                    'ktp_image_path' => $ktpPath,
                    'verification_status' => MuthowifVerificationStatus::Pending,
                ]);

                $files = $request->file('supporting_documents', []);
                if (! is_array($files)) {
                    $files = array_filter([$files]);
                }
                foreach ($files as $index => $file) {
                    if (! $file || ! $file->isValid()) {
                        continue;
                    }
                    $path = $file->store($storedDir, 'local');
                    $profile->supportingDocuments()->create([
                        'path' => $path,
                        'original_name' => $file->getClientOriginalName(),
                        'sort_order' => (int) $index,
                    ]);
                }
            }

            DB::commit();

            if ($registrationOtp->otpEnabled()) {
                $registrationOtp->clearVerificationSession();
            }
        } catch (Throwable $e) {
            DB::rollBack();

            if ($storedDir !== null) {
                Storage::disk('local')->deleteDirectory($storedDir);
            }

            throw $e;
        }

        event(new Registered($user));

        if ($user->isMuthowif()) {
            return redirect()->route('muthowif.registration.pending');
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
        $storedDir = null;
        $user = null;

        try {
            DB::beginTransaction();

            $role = UserRole::from($input['role']);

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

            if ($user->isMuthowif()) {
                if ($muthowifFiles === null) {
                    throw new \RuntimeException('Berkas muthowif hilang.');
                }

                $storedDir = 'muthowif_documents/'.$user->id;
                Storage::disk('local')->makeDirectory($storedDir);

                $photoDest = $storedDir.'/'.basename($muthowifFiles['photo_path']);
                $ktpDest = $storedDir.'/'.basename($muthowifFiles['ktp_path']);
                Storage::disk('local')->move($muthowifFiles['photo_path'], $photoDest);
                Storage::disk('local')->move($muthowifFiles['ktp_path'], $ktpDest);

                $languages = $this->requestStringList($input['languages'] ?? null);
                $educations = $this->requestStringList($input['educations'] ?? null);
                $workExperiences = $this->requestStringList($input['work_experiences'] ?? null);

                $profile = MuthowifProfile::create([
                    'user_id' => $user->id,
                    'phone' => $input['phone'],
                    'address' => $input['address'],
                    'nik' => $input['nik'],
                    'birth_date' => $input['birth_date'],
                    'passport_number' => $input['passport_number'],
                    'languages' => $languages,
                    'educations' => $educations,
                    'work_experiences' => $workExperiences,
                    'reference_text' => $input['reference_text'] ?? null,
                    'photo_path' => $photoDest,
                    'ktp_image_path' => $ktpDest,
                    'verification_status' => MuthowifVerificationStatus::Pending,
                ]);

                foreach ($muthowifFiles['supporting'] as $row) {
                    $dest = $storedDir.'/'.basename($row['path']);
                    Storage::disk('local')->move($row['path'], $dest);
                    $profile->supportingDocuments()->create([
                        'path' => $dest,
                        'original_name' => $row['original_name'],
                        'sort_order' => $row['sort_order'],
                    ]);
                }
            }

            DB::commit();

            if ($registrationOtp->otpEnabled()) {
                $registrationOtp->clearVerificationSession();
            }
            if ($pending !== null) {
                $this->discardPendingRegistration($pending);
            }
        } catch (Throwable $e) {
            DB::rollBack();

            if ($storedDir !== null) {
                Storage::disk('local')->deleteDirectory($storedDir);
            }

            throw $e;
        }

        event(new Registered($user));

        if ($user->isMuthowif()) {
            return redirect()->route('muthowif.registration.pending');
        }

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
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
            $photoPath = $request->file('photo')->store($base, 'local');
            $ktpPath = $request->file('ktp_image')->store($base, 'local');
            $supporting = [];
            $files = $request->file('supporting_documents', []);
            if (! is_array($files)) {
                $files = array_filter([$files]);
            }
            foreach ($files as $index => $file) {
                if (! $file || ! $file->isValid()) {
                    continue;
                }
                $supporting[] = [
                    'path' => $file->store($base, 'local'),
                    'original_name' => $file->getClientOriginalName(),
                    'sort_order' => (int) $index,
                ];
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
}
