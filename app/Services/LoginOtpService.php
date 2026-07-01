<?php

namespace App\Services;

use App\Mail\LoginOtpMail;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginOtpService
{
    private const OTP_TTL_SECONDS = 600;

    private const SEND_COOLDOWN_SECONDS = 60;

    private const MAX_VERIFY_ATTEMPTS = 5;

    /**
     * @throws ValidationException
     */
    public function send(string $emailInput): void
    {
        $email = Str::lower(trim($emailInput));
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw ValidationException::withMessages([
                'email' => ['Format email tidak valid.'],
            ]);
        }

        /** @var User|null $user */
        $user = User::query()->where('email', $email)->first();
        if ($user === null) {
            throw ValidationException::withMessages([
                'email' => ['Email tidak terdaftar.'],
            ]);
        }

        if (Cache::has($this->cooldownKey($email))) {
            throw ValidationException::withMessages([
                'email' => ['Tunggu sekitar satu menit sebelum mengirim ulang kode.'],
            ]);
        }

        if (RateLimiter::tooManyAttempts($this->hourlyKey($email), 5)) {
            throw ValidationException::withMessages([
                'email' => ['Terlalu banyak permintaan kode. Coba lagi dalam satu jam.'],
            ]);
        }

        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        Cache::put($this->codeKey($email), hash('sha256', $otp), now()->addSeconds(self::OTP_TTL_SECONDS));
        Cache::forget($this->attemptsKey($email));

        Mail::to($user)->send(new LoginOtpMail($user, $otp));

        Cache::put($this->cooldownKey($email), true, now()->addSeconds(self::SEND_COOLDOWN_SECONDS));
        RateLimiter::hit($this->hourlyKey($email), 3600);
    }

    /**
     * @throws ValidationException
     */
    public function verify(string $emailInput, string $otp): User
    {
        $email = Str::lower(trim($emailInput));
        $stored = Cache::get($this->codeKey($email));
        if ($stored === null || ! is_string($stored)) {
            throw ValidationException::withMessages([
                'otp' => ['Kode kedaluwarsa atau belum dikirim.'],
            ]);
        }

        $attemptKey = $this->attemptsKey($email);
        $attempts = (int) Cache::get($attemptKey, 0);
        if ($attempts >= self::MAX_VERIFY_ATTEMPTS) {
            throw ValidationException::withMessages([
                'otp' => ['Terlalu banyak percobaan. Minta kode baru.'],
            ]);
        }

        $otp = preg_replace('/\D+/', '', $otp ?? '') ?? '';
        if (strlen($otp) !== 6) {
            throw ValidationException::withMessages([
                'otp' => ['Masukkan 6 digit kode OTP.'],
            ]);
        }

        if (! hash_equals($stored, hash('sha256', $otp))) {
            Cache::put($attemptKey, $attempts + 1, now()->addSeconds(self::OTP_TTL_SECONDS));
            throw ValidationException::withMessages([
                'otp' => ['Kode OTP salah.'],
            ]);
        }

        Cache::forget($this->codeKey($email));
        Cache::forget($attemptKey);

        /** @var User|null $user */
        $user = User::query()->where('email', $email)->first();
        if ($user === null) {
            throw ValidationException::withMessages([
                'email' => ['Email tidak terdaftar.'],
            ]);
        }

        return $user;
    }

    private function codeKey(string $email): string
    {
        return 'login_otp_code:'.$email;
    }

    private function attemptsKey(string $email): string
    {
        return 'login_otp_attempts:'.$email;
    }

    private function cooldownKey(string $email): string
    {
        return 'login_otp_cooldown:'.$email;
    }

    private function hourlyKey(string $email): string
    {
        return 'login-otp-hour:'.$email;
    }
}
