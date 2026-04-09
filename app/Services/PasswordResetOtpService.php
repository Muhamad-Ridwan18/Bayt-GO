<?php

namespace App\Services;

use App\Models\User;
use App\Support\PhoneNumber;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class PasswordResetOtpService
{
    private const OTP_TTL_SECONDS = 600;

    private const RESET_TOKEN_TTL_SECONDS = 1800;

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
     * @return array{reset_token: string, masked_phone: string}
     *
     * @throws ValidationException
     */
    public function send(string $phoneInput): array
    {
        $normalized = PhoneNumber::normalize($phoneInput);
        if ($normalized === null || strlen($normalized) < 10 || strlen($normalized) > 15) {
            throw ValidationException::withMessages([
                'phone' => ['Format nomor WhatsApp tidak valid.'],
            ]);
        }

        /** @var User|null $user */
        $user = $this->findUserByPhone($normalized, $phoneInput);
        if (! $user) {
            throw ValidationException::withMessages([
                'phone' => ['Nomor WhatsApp tidak terdaftar.'],
            ]);
        }

        if (Cache::has('pwd_otp_cooldown:'.$normalized)) {
            throw ValidationException::withMessages([
                'phone' => ['Tunggu sekitar satu menit sebelum mengirim ulang kode.'],
            ]);
        }

        if (RateLimiter::tooManyAttempts('pwd-otp-hour:'.$normalized, 5)) {
            throw ValidationException::withMessages([
                'phone' => ['Terlalu banyak permintaan kode. Coba lagi dalam satu jam.'],
            ]);
        }

        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $hash = hash('sha256', $otp);
        $resetToken = bin2hex(random_bytes(24));

        Cache::put($this->cacheCodeKey($normalized), $hash, now()->addSeconds(self::OTP_TTL_SECONDS));
        Cache::put($this->cacheResetPhoneKey($resetToken), $normalized, now()->addSeconds(self::RESET_TOKEN_TTL_SECONDS));
        Cache::forget($this->cacheAttemptsKey($normalized));

        $target = PhoneNumber::forFonnte($normalized);
        if (! is_string($target) || $target === '') {
            throw ValidationException::withMessages([
                'phone' => ['Format nomor WhatsApp tidak valid.'],
            ]);
        }

        $appName = config('app.name', 'BaytGo');
        $message = "Kode reset password {$appName}: *{$otp}*\n\nJangan bagikan kode ini kepada siapa pun. Berlaku 10 menit.";

        try {
            $this->fonnte->sendText($target, $message);
        } catch (RuntimeException $e) {
            Cache::forget($this->cacheCodeKey($normalized));
            Cache::forget($this->cacheResetPhoneKey($resetToken));
            throw ValidationException::withMessages([
                'phone' => [$e->getMessage()],
            ]);
        }

        Cache::put('pwd_otp_cooldown:'.$normalized, true, now()->addSeconds(self::SEND_COOLDOWN_SECONDS));
        RateLimiter::hit('pwd-otp-hour:'.$normalized, 3600);

        return [
            'reset_token' => $resetToken,
            'masked_phone' => $this->maskPhone($normalized),
        ];
    }

    /**
     * @throws ValidationException
     */
    public function consumeAndValidate(string $resetToken, string $otp): string
    {
        $mappedPhone = Cache::get($this->cacheResetPhoneKey($resetToken));
        if (! is_string($mappedPhone) || $mappedPhone === '' || PhoneNumber::normalize($mappedPhone) === null) {
            throw ValidationException::withMessages([
                'token' => ['Sesi reset tidak valid atau kedaluwarsa.'],
            ]);
        }
        $normalized = (string) PhoneNumber::normalize($mappedPhone);

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
        Cache::forget($this->cacheResetPhoneKey($resetToken));

        return $normalized;
    }

    private function cacheCodeKey(string $normalized): string
    {
        return 'pwd_otp_code:'.$normalized;
    }

    private function cacheAttemptsKey(string $normalized): string
    {
        return 'pwd_otp_attempts:'.$normalized;
    }

    private function cacheResetPhoneKey(string $resetToken): string
    {
        return 'pwd_otp_reset_phone:'.$resetToken;
    }

    private function maskPhone(string $normalized): string
    {
        if (strlen($normalized) <= 8) {
            return $normalized;
        }

        return substr($normalized, 0, 4).'****'.substr($normalized, -4);
    }

    private function findUserByPhone(string $normalized, string $phoneInput): ?User
    {
        $inputTrimmed = trim($phoneInput);
        $local08 = str_starts_with($normalized, '62') ? '0'.substr($normalized, 2) : $normalized;
        $local8 = str_starts_with($local08, '0') ? substr($local08, 1) : $local08;

        $direct = User::query()
            ->whereIn('phone', array_values(array_unique([$normalized, $inputTrimmed, $local08, $local8])))
            ->first();
        if ($direct) {
            return $direct;
        }

        // Fallback: ambil kandidat berdasar suffix lalu cocokkan via normalisasi PHP.
        $suffix = substr($normalized, -9);
        if ($suffix === false || $suffix === '') {
            return null;
        }

        $candidates = User::query()
            ->where('phone', 'like', '%'.$suffix)
            ->limit(50)
            ->get(['id', 'phone']);

        foreach ($candidates as $candidate) {
            if (PhoneNumber::normalize($candidate->phone) === $normalized) {
                return $candidate;
            }
        }

        return null;
    }
}
