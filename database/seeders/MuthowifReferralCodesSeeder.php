<?php

namespace Database\Seeders;

use App\Enums\MuthowifVerificationStatus;
use App\Models\MuthowifProfile;
use App\Services\MuthowifReferralCodeService;
use Illuminate\Database\Seeder;

/**
 * Menjamin setiap profil muthowif yang sudah disetujui memiliki referral_code unik.
 */
class MuthowifReferralCodesSeeder extends Seeder
{
    public function run(): void
    {
        $service = app(MuthowifReferralCodeService::class);

        MuthowifProfile::query()
            ->where('verification_status', MuthowifVerificationStatus::Approved)
            ->orderBy('created_at')
            ->each(function (MuthowifProfile $profile) use ($service): void {
                $service->ensureAssigned($profile->fresh());
            });
    }
}
