<?php

namespace App\Services;

use App\Enums\AffiliateStatus;
use App\Models\Affiliate;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AffiliateRegistrationService
{
    public function register(User $user, ?string $preferredCode = null): Affiliate
    {
        if ($user->isAdmin()) {
            throw ValidationException::withMessages([
                'affiliate' => ['Admin tidak dapat mendaftar sebagai affiliate.'],
            ]);
        }

        return DB::transaction(function () use ($user, $preferredCode): Affiliate {
            $existing = Affiliate::query()
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->first();

            if ($existing !== null) {
                return $existing;
            }

            $code = $this->resolveUniqueCode($preferredCode, $user);

            return Affiliate::query()->create([
                'user_id' => $user->id,
                'code' => $code,
                'status' => AffiliateStatus::Active,
                'available_balance' => 0,
                'activated_at' => now(),
            ]);
        });
    }

    public function resolveUniqueCode(?string $preferredCode, User $user): string
    {
        $normalized = $this->normalizeCode($preferredCode);

        if ($normalized !== null && ! Affiliate::query()->where('code', $normalized)->exists()) {
            return $normalized;
        }

        $base = $this->normalizeCode($user->name) ?? 'AFF';
        $base = substr(preg_replace('/[^A-Z0-9]/', '', $base) ?: 'AFF', 0, 8);

        for ($i = 0; $i < 20; $i++) {
            $candidate = $base.strtoupper(Str::random(4));
            if (! Affiliate::query()->where('code', $candidate)->exists()) {
                return $candidate;
            }
        }

        return 'AFF'.strtoupper(Str::random(10));
    }

    public function normalizeCode(?string $code): ?string
    {
        if ($code === null) {
            return null;
        }

        $normalized = strtoupper(trim($code));
        $normalized = preg_replace('/[^A-Z0-9]/', '', $normalized) ?? '';

        if ($normalized === '' || strlen($normalized) < 3 || strlen($normalized) > 32) {
            return null;
        }

        return $normalized;
    }
}
