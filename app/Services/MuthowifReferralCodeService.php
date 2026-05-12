<?php

namespace App\Services;

use App\Models\MuthowifProfile;
use Illuminate\Support\Str;

class MuthowifReferralCodeService
{
    /** Tugaskan kode unik saat profil disetujui / belum punya kode. */
    public function ensureAssigned(MuthowifProfile $profile): void
    {
        if (filled($profile->referral_code)) {
            return;
        }

        for ($i = 0; $i < 40; $i++) {
            $code = strtoupper(Str::random(8));
            if (! MuthowifProfile::query()->where('referral_code', $code)->exists()) {
                $profile->forceFill(['referral_code' => $code])->save();

                return;
            }
        }

        $fallback = 'MW'.strtoupper(Str::random(6));
        $profile->forceFill(['referral_code' => $fallback])->save();
    }
}
