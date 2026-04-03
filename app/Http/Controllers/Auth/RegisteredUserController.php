<?php

namespace App\Http\Controllers\Auth;

use App\Enums\CustomerType;
use App\Enums\MuthowifVerificationStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\MuthowifProfile;
use App\Models\User;
use App\Services\RegistrationOtpService;
use App\Support\PhoneNumber;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        $registrationOtp = app(RegistrationOtpService::class);
        $otpEnabled = $registrationOtp->otpEnabled();
        $phoneVerifiedNormalized = session('registration_phone_verified');

        $phoneVerifiedInitial = false;
        if ($otpEnabled && is_string($phoneVerifiedNormalized) && $phoneVerifiedNormalized !== '') {
            $oldPhone = (string) old('phone', '');
            if ($oldPhone !== '') {
                $phoneVerifiedInitial = PhoneNumber::normalize($oldPhone) === $phoneVerifiedNormalized;
            }
        }

        return view('auth.register', [
            'otpEnabled' => $otpEnabled,
            'phoneVerifiedInitial' => $phoneVerifiedInitial,
        ]);
    }

    /**
     * @throws Throwable
     */
    public function store(Request $request): RedirectResponse
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role' => ['required', Rule::enum(UserRole::class)->only([UserRole::Customer, UserRole::Muthowif])],
            'customer_type' => ['required_if:role,customer', 'nullable', Rule::enum(CustomerType::class)],
            'phone' => ['required_if:role,customer', 'required_if:role,muthowif', 'nullable', 'string', 'min:10', 'max:20'],
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

        $registrationOtp = app(RegistrationOtpService::class);
        if ($registrationOtp->otpEnabled()) {
            $role = UserRole::from($request->input('role'));
            if ($role === UserRole::Customer || $role === UserRole::Muthowif) {
                if (! $registrationOtp->isPhoneVerifiedForRegistration($request->string('phone')->toString())) {
                    throw ValidationException::withMessages([
                        'phone' => ['Verifikasi OTP WhatsApp wajib untuk nomor ini.'],
                    ]);
                }
            }
        }

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
