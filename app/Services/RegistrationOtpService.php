<?php

namespace App\Services;

use App\Jobs\SendWhatsAppTextJob;
use App\Support\IntlPhone;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class RegistrationOtpService
{
    private const OTP_TTL_SECONDS = 600;

    private const SEND_COOLDOWN_SECONDS = 60;

    private const MAX_VERIFY_ATTEMPTS = 5;

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
    public function send(string $phoneInput, ?string $recipientName = null): void
    {
        $normalized = IntlPhone::normalize($phoneInput);
        $fonnteDial = IntlPhone::fonnteDial($phoneInput);
        if ($normalized === null || $fonnteDial === null || strlen($normalized) < 8 || strlen($normalized) > 15) {
            throw ValidationException::withMessages([
                'phone' => ['Format nomor WhatsApp tidak valid. Gunakan +kode negara dan nomor lengkap, atau nomor lokal sesuai wilayah default (PHONE_DEFAULT_REGION).'],
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

        $appName = config('app.name', 'BaytGo');
        $recipientName = trim((string) $recipientName);
        // Build greeting (always included)
        $greeting = __('auth_otp.otp_greeting', ['name' => $recipientName]);
        if (! is_string($greeting) || trim($greeting) === '' || $greeting === 'auth_otp.otp_greeting') {
            $greeting = '';
        } else {
            $greeting .= "\n\n"; // separate from OTP message
        }
        $otpMessage = __('auth_otp.otp_message', [
            'otp' => $otp,
            'app' => $appName,
        ]);
        // Guard: if translation fails, use a hard‑coded fallback.
        if (! is_string($otpMessage) || trim($otpMessage) === '' || $otpMessage === 'auth_otp.otp_message') {
            $otpMessage = "Kode verifikasi {$appName} Anda: {$otp}\n\nJangan bagikan kode ini kepada siapa pun. Berlaku 10 menit.";
        }
        $message = $greeting.$otpMessage;

        SendWhatsAppTextJob::dispatchAfterResponse(
            $fonnteDial['target'],
            $message,
            $fonnteDial['country_calling_code'],
            [$this->cacheCodeKey($normalized)],
        );

        Cache::put('reg_otp_cooldown:'.$normalized, true, now()->addSeconds(self::SEND_COOLDOWN_SECONDS));
        RateLimiter::hit('reg-otp-hour:'.$normalized, 3600);
    }

    /**
     * @throws ValidationException
     */
    public function verify(string $phoneInput, string $otp): string
    {
        $normalized = IntlPhone::normalize($phoneInput);
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
        $normalized = IntlPhone::normalize($phoneInput);
        if ($normalized === null) {
            return false;
        }

        return session('registration_phone_verified') === $normalized;
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
