<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\ReplacementOfferStatus;
use App\Models\BookingReplacementOffer;
use App\Models\User;

final class MuthowifEmergencyOfferCounts
{
    public static function pendingOfferedCountForUser(?User $user): int
    {
        if ($user === null || ! $user->isVerifiedMuthowif()) {
            return 0;
        }

        $profile = $user->muthowifProfile;
        if ($profile === null) {
            return 0;
        }

        return BookingReplacementOffer::query()
            ->where('muthowif_profile_id', $profile->getKey())
            ->where('status', ReplacementOfferStatus::Offered)
            ->count();
    }
}
