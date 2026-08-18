<?php

namespace App\Http\Controllers\Api;

use App\Enums\CustomerType;
use App\Enums\MuthowifVerificationStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Jobs\NotifyAdminsOfMuthowifRegistration;
use App\Models\MuthowifProfile;
use App\Models\User;
use App\Services\MuthowifRejectedReregistration;
use App\Services\UploadedImageOptimizer;
use App\Support\MuthowifVerificationBroadcast;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Throwable;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'device_name' => 'required',
        ]);

        $throttleKey = $this->loginThrottleKey($request);
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            event(new Lockout($request));
            $seconds = RateLimiter::availableIn($throttleKey);

            return response()->json([
                'message' => trans('auth.throttle', [
                    'seconds' => $seconds,
                    'minutes' => ceil($seconds / 60),
                ]),
            ], 429);
        }

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            RateLimiter::hit($throttleKey);

            return response()->json([
                'message' => 'Email atau password salah.',
            ], 422);
        }

        if ($user->isMuthowif()) {
            $user->loadMissing('muthowifProfile');
            $profile = $user->muthowifProfile;

            if ($profile === null || ! $profile->isApproved()) {
                RateLimiter::hit($throttleKey);

                $message = match (true) {
                    $profile === null => 'Akun muthowif tidak lengkap. Hubungi admin.',
                    $profile->isPending() => 'Akun muthowif Anda belum disetujui admin. Tunggu hingga pendaftaran disetujui sebelum masuk.',
                    $profile->isRejected() => 'Pendaftaran muthowif ditolak.'.($profile->rejection_reason ? ' '.$profile->rejection_reason : ''),
                    default => 'Akun muthowif tidak dapat digunakan saat ini.',
                };

                return response()->json(['message' => $message], 403);
            }
        }

        if ($user->isCompanyCustomer() && ! $user->is_company_approved) {
            RateLimiter::hit($throttleKey);

            return response()->json([
                'message' => 'Akun perusahaan Anda belum disetujui oleh admin. Anda belum dapat masuk.',
            ], 403);
        }

        RateLimiter::clear($throttleKey);

        $token = $user->createToken($request->device_name)->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $this->apiUserPayload($user),
        ]);
    }

    private function loginThrottleKey(Request $request): string
    {
        return Str::transliterate(Str::lower($request->string('email')->toString()).'|'.$request->ip());
    }

    /**
     * @throws Throwable
     */
    public function register(Request $request)
    {
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
            'phone' => ['required', 'string', 'min:10', 'max:24'],
            'country' => ['nullable', 'string', 'size:2', 'regex:/^[A-Za-z]{2}$/'],
            'address' => ['required', 'string', 'max:2000'],
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
            'muthowif_referral_code' => ['nullable', 'string', 'max:16'],
            'photo' => ['required_if:role,muthowif', 'nullable', 'file', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            'ktp_image' => ['required_if:role,muthowif', 'nullable', 'file', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            'gallery_images' => ['nullable', 'array', 'max:20'],
            'gallery_images.*' => ['file', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            'supporting_documents' => ['nullable', 'array', 'max:20'],
            'supporting_documents.*' => ['file', 'mimes:pdf,jpeg,jpg,png,webp', 'max:10240'],
            'device_name' => ['required', 'string'],
        ];

        if ($request->has('country') && $request->string('country')->toString() === '') {
            $request->merge(['country' => null]);
        }

        $request->validate($rules);

        if ($request->string('role')->toString() === UserRole::Muthowif->value) {
            $languages = $this->requestStringList($request->input('languages'));
            if (count($languages) === 0) {
                throw ValidationException::withMessages([
                    'languages' => 'Isi minimal satu bahasa.',
                ]);
            }

            if (count($this->collectGalleryUploads($request)) < 3) {
                throw ValidationException::withMessages([
                    'gallery_images' => 'Unggah minimal 3 foto galeri.',
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

        $storedDir = null;
        $portfolioDir = null;
        $user = null;
        $muthowifProfileId = null;
        $existingRejected = null;

        try {
            DB::beginTransaction();

            $role = UserRole::from($request->input('role'));
            $reregistration = app(MuthowifRejectedReregistration::class);
            $existingRejected = $role === UserRole::Muthowif
                ? $reregistration->findByEmail($request->string('email')->toString())
                : null;

            if ($existingRejected !== null) {
                $user = $existingRejected;
                $user->update([
                    'name' => $request->string('name')->toString(),
                    'password' => Hash::make($request->string('password')->toString()),
                    'country' => $request->filled('country') ? strtoupper($request->string('country')->toString()) : null,
                ]);
            } else {
                $user = User::create([
                    'name' => $request->string('name')->toString(),
                    'email' => $request->string('email')->toString(),
                    'password' => Hash::make($request->string('password')->toString()),
                    'role' => $role,
                    'phone' => $role === UserRole::Customer ? $request->string('phone')->toString() : null,
                    'country' => $request->filled('country') ? strtoupper($request->string('country')->toString()) : null,
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
                $storedDir = 'muthowif_documents/'.$user->id;

                if ($existingRejected !== null) {
                    $profile = $user->muthowifProfile;
                    if ($profile === null) {
                        throw new \RuntimeException('Profil muthowif hilang.');
                    }
                    $reregistration->discardStoredDocuments($profile);
                }

                $optimizer = app(UploadedImageOptimizer::class);
                $photoPath = $optimizer->store($request->file('photo'), $storedDir, 'local', 'profile');
                $ktpPath = $optimizer->store($request->file('ktp_image'), $storedDir, 'local', 'profile');

                $languages = $this->requestStringList($request->input('languages'));
                $educations = $this->requestStringList($request->input('educations'));
                $workExperiences = $this->requestStringList($request->input('work_experiences'));

                $referralRaw = $request->input('muthowif_referral_code');
                $referralNorm = is_string($referralRaw) ? strtoupper(trim($referralRaw)) : '';
                $referredById = $referralNorm !== '' ? $this->resolveReferredByMuthowifProfileId($referralNorm) : null;

                $profilePayload = [
                    'phone' => $request->input('phone'),
                    'address' => $request->input('address'),
                    'work_location' => filled($request->input('work_location'))
                        ? trim((string) $request->input('work_location'))
                        : null,
                    'nik' => $request->input('nik'),
                    'birth_date' => $request->input('birth_date'),
                    'passport_number' => $request->input('passport_number'),
                    'languages' => $languages,
                    'educations' => $educations,
                    'work_experiences' => $workExperiences,
                    'reference_text' => $request->input('reference_text'),
                    'photo_path' => $photoPath,
                    'ktp_image_path' => $ktpPath,
                    'referred_by_muthowif_profile_id' => $referredById,
                ];
                $statusPayload = [
                    'verification_status' => MuthowifVerificationStatus::Pending,
                    'verified_at' => null,
                    'rejection_reason' => null,
                ];

                if ($existingRejected !== null) {
                    $profile->fill($profilePayload);
                    $profile->forceFill($statusPayload)->save();
                } else {
                    $profile = new MuthowifProfile(array_merge($profilePayload, [
                        'user_id' => $user->id,
                    ]));
                    $profile->forceFill($statusPayload)->save();
                }
                $muthowifProfileId = (string) $profile->getKey();

                $files = $request->file('supporting_documents', []);
                if (! is_array($files)) {
                    $files = array_filter([$files]);
                }
                foreach ($files as $index => $file) {
                    if (! $file || ! $file->isValid()) {
                        continue;
                    }
                    $path = $optimizer->store($file, $storedDir, 'local', 'document');
                    $profile->supportingDocuments()->create([
                        'path' => $path,
                        'original_name' => $file->getClientOriginalName(),
                        'sort_order' => (int) $index,
                    ]);
                }

                $portfolioDir = 'portfolio/'.$profile->getKey();
                $this->createRegistrationGalleryPortfolio($profile, $request, $optimizer);
            }

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();

            if ($existingRejected === null) {
                if ($storedDir !== null) {
                    Storage::disk('local')->deleteDirectory($storedDir);
                }
                if ($portfolioDir !== null) {
                    Storage::disk('local')->deleteDirectory($portfolioDir);
                }
            }

            throw $e;
        }

        NotifyAdminsOfMuthowifRegistration::afterMuthowifRegistered($muthowifProfileId);
        if ($muthowifProfileId !== null) {
            MuthowifVerificationBroadcast::afterResponse($muthowifProfileId);
        }

        if ($user->isCompanyCustomer() && ! $user->is_company_approved) {
            return response()->json([
                'message' => 'Pendaftaran berhasil. Akun perusahaan Anda sedang menunggu persetujuan admin.',
            ], 201);
        }

        if ($user->isMuthowif()) {
            return response()->json([
                'message' => 'Pendaftaran berhasil. Dokumen Anda sedang ditinjau oleh admin.',
                'user' => $this->apiUserPayload($user->fresh()),
            ], 201);
        }

        $token = $user->createToken($request->device_name)->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $this->apiUserPayload($user),
        ], 201);
    }

    /**
     * @return array<string, mixed>
     */
    private function apiUserPayload(User $user): array
    {
        $user->loadMissing('muthowifProfile');

        $payload = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'locale' => $user->locale,
        ];

        if ($user->isMuthowif() && $user->muthowifProfile !== null) {
            $payload['muthowif_verification_status'] = $user->muthowifProfile->verification_status->value;
        }

        return $payload;
    }

    /**
     * @return list<\Illuminate\Http\UploadedFile>
     */
    private function collectGalleryUploads(Request $request): array
    {
        $files = $request->file('gallery_images', []);
        if (! is_array($files)) {
            $files = array_filter([$files]);
        }

        $valid = [];
        foreach ($files as $file) {
            if ($file && $file->isValid()) {
                $valid[] = $file;
            }
        }

        return $valid;
    }

    private function createRegistrationGalleryPortfolio(
        MuthowifProfile $profile,
        Request $request,
        UploadedImageOptimizer $optimizer,
    ): void {
        $files = $this->collectGalleryUploads($request);
        if ($files === []) {
            return;
        }

        $profileId = (string) $profile->getKey();
        $destDir = 'portfolio/'.$profileId;

        $portfolio = $profile->portfolios()->create([
            'title' => 'Galeri',
            'description' => null,
            'image_path' => '',
            'sort_order' => $profile->portfolios()->count() + 1,
        ]);

        $coverPath = null;
        foreach ($files as $index => $file) {
            $path = $optimizer->store($file, $destDir, 'local', 'portfolio');
            $portfolio->images()->create([
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'sort_order' => (int) $index,
            ]);
            $coverPath ??= $path;
        }

        if (is_string($coverPath)) {
            $portfolio->image_path = $coverPath;
            $portfolio->save();
        }
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

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }
}
