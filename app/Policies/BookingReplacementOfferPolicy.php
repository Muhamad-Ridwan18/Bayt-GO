<?php

namespace App\Policies;

use App\Enums\EmergencyReportStatus;
use App\Enums\ReplacementOfferStatus;
use App\Models\BookingReplacementOffer;
use App\Models\User;

class BookingReplacementOfferPolicy
{
    public function respondToOffer(User $user, BookingReplacementOffer $offer): bool
    {
        if (! $user->isVerifiedMuthowif() || ! $user->muthowifProfile) {
            return false;
        }

        return (string) $offer->muthowif_profile_id === (string) $user->muthowifProfile->id
            && $offer->status === ReplacementOfferStatus::Offered
            && $offer->report?->recruitment_open === true
            && $offer->report?->status === EmergencyReportStatus::Verified;
    }
}
