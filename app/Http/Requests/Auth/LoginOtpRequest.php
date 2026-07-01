<?php

namespace App\Http\Requests\Auth;

use App\Enums\UserRole;
use App\Models\User;
use App\Services\LoginOtpService;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'otp' => ['required', 'string', 'size:6', 'regex:/^\d{6}$/'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'otp' => preg_replace('/\D+/', '', (string) $this->input('otp', '')),
        ]);
    }

    /**
     * @throws ValidationException
     */
    public function authenticate(LoginOtpService $loginOtp): User
    {
        $this->ensureIsNotRateLimited();

        try {
            $user = $loginOtp->verify(
                $this->string('email')->toString(),
                $this->string('otp')->toString()
            );
        } catch (ValidationException $e) {
            RateLimiter::hit($this->throttleKey());
            throw $e;
        }

        $this->ensureUserCanLogin($user);

        RateLimiter::clear($this->throttleKey());

        return $user;
    }

    /**
     * @throws ValidationException
     */
    private function ensureUserCanLogin(User $user): void
    {
        if ($user->role === UserRole::Muthowif) {
            $user->loadMissing('muthowifProfile');
            $profile = $user->muthowifProfile;

            if ($profile === null || ! $profile->isApproved()) {
                $message = match (true) {
                    $profile === null => 'Akun muthowif tidak lengkap. Hubungi admin.',
                    $profile->isPending() => 'Akun muthowif Anda belum disetujui admin. Tunggu hingga pendaftaran disetujui sebelum masuk.',
                    $profile->isRejected() => 'Pendaftaran muthowif ditolak.'.($profile->rejection_reason ? ' '.$profile->rejection_reason : ''),
                    default => 'Akun muthowif tidak dapat digunakan saat ini.',
                };

                throw ValidationException::withMessages([
                    'email' => $message,
                ]);
            }
        }

        if ($user->isCompanyCustomer() && ! $user->is_company_approved) {
            throw ValidationException::withMessages([
                'email' => 'Akun perusahaan Anda belum disetujui oleh admin. Anda belum dapat masuk.',
            ]);
        }
    }

    /**
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }
}
