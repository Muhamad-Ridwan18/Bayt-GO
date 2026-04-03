<?php

namespace App\Services;

use App\Support\PhoneNumber;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class RegistrationOtpService
{
    private const OTP_TTL_SECONDS = 600;

    private const SEND_COOLDOWN_SECONDS = 60;

    private const MAX_VERIFY_ATTEMPTS = 5;

    public function __construct(
        private readonly FonnteService $fonnte
    ) {}

    public function otpEnabled(): bool
    {
        if (! config('services.fonnte.otp_enabled', true)) {
            return false;
        }

        $token = config('services.fonnte.token');

        return $token !== null && $token !== '';
    }

    /**
     * @throws ValidationException
     */
    public function send(string $phoneInput): void
    {
        $normalized = PhoneNumber::normalize($phoneInput);
        if ($normalized === null || strlen($normalized) < 10 || strlen($normalized) > 15) {
            throw ValidationException::withMessages([
                'phone' => ['Format nomor WhatsApp tidak valid.'],
            ]);
        }

        if (Cache::has('reg_otp_cooldown:'.$normalized)) {
            throw ValidationException::withMessages([
                'phone' => ['Tunggu sekitar satu menit sebelum mengirim ulang kode.'],
            ]);
        }

        if (RateLimiter::tooManyAttempts('reg-otp-hour:'.$normalized, 5)) {
            throw ValidationException::withMessages([
                'phone' => ['Terlalu banyak permintaan kode. Coba lagi dalam satu jam.'],
            ]);
        }

        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $hash = hash('sha256', $otp);

        Cache::put($this->cacheCodeKey($normalized), $hash, now()->addSeconds(self::OTP_TTL_SECONDS));
        Cache::forget($this->cacheAttemptsKey($normalized));

        $target = $this->fonnteTarget($normalized);
        $appName = config('app.name', 'BaytGo');
        $message = "Kode verifikasi {$appName} Anda: *{$otp}*\n\nJangan bagikan kode ini kepada siapa pun. Berlaku 10 menit.";

        try {
            $this->fonnte->sendText($target, $message);
        } catch (RuntimeException $e) {
            Cache::forget($this->cacheCodeKey($normalized));
            throw ValidationException::withMessages([
                'phone' => [$e->getMessage()],
            ]);
        }

        Cache::put('reg_otp_cooldown:'.$normalized, true, now()->addSeconds(self::SEND_COOLDOWN_SECONDS));
        RateLimiter::hit('reg-otp-hour:'.$normalized, 3600);
    }

    /**
     * @throws ValidationException
     */
    public function verify(string $phoneInput, string $otp): string
    {
        $normalized = PhoneNumber::normalize($phoneInput);
        if ($normalized === null) {
            throw ValidationException::withMessages([
                'otp' => ['Nomor tidak valid.'],
            ]);
        }

        $stored = Cache::get($this->cacheCodeKey($normalized));
        if ($stored === null || ! is_string($stored)) {
            throw ValidationException::withMessages([
                'otp' => ['Kode kedaluwarsa atau belum dikirim.'],
            ]);
        }

        $attemptKey = $this->cacheAttemptsKey($normalized);
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

        Cache::forget($this->cacheCodeKey($normalized));
        Cache::forget($attemptKey);

        return $normalized;
    }

    public function clearVerificationSession(): void
    {
        session()->forget('registration_phone_verified');
    }

    public function rememberVerifiedPhone(string $normalized): void
    {
        session(['registration_phone_verified' => $normalized]);
    }

    public function isPhoneVerifiedForRegistration(string $phoneInput): bool
    {
        $normalized = PhoneNumber::normalize($phoneInput);
        if ($normalized === null) {
            return false;
        }

        return session('registration_phone_verified') === $normalized;
    }

    /**
     * Fonnte: gunakan format lokal 08… dengan countryCode 62 (default).
     */
    private function fonnteTarget(string $normalized): string
    {
        if (str_starts_with($normalized, '62')) {
            return '0'.substr($normalized, 2);
        }

        return $normalized;
    }

    private function cacheCodeKey(string $normalized): string
    {
        return 'reg_otp_code:'.$normalized;
    }

    private function cacheAttemptsKey(string $normalized): string
    {
        return 'reg_otp_attempts:'.$normalized;
    }
}
