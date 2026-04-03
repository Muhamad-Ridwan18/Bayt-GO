<?php

namespace App\Policies;

use App\Models\MuthowifBlockedDate;
use App\Models\User;

class MuthowifBlockedDatePolicy
{
    public function delete(User $user, MuthowifBlockedDate $blockedDate): bool
    {
        if (! $user->isVerifiedMuthowif() || ! $user->muthowifProfile) {
            return false;
        }

        return $blockedDate->muthowif_profile_id === $user->muthowifProfile->id;
    }
}
