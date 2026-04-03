<?php

namespace App\Policies;

use App\Models\MuthowifService;
use App\Models\User;

class MuthowifServicePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isVerifiedMuthowif();
    }

    public function create(User $user): bool
    {
        return $user->isVerifiedMuthowif();
    }

    public function update(User $user, MuthowifService $muthowifService): bool
    {
        return $this->ownsProfile($user, $muthowifService);
    }

    public function delete(User $user, MuthowifService $muthowifService): bool
    {
        return $this->ownsProfile($user, $muthowifService);
    }

    private function ownsProfile(User $user, MuthowifService $service): bool
    {
        if (! $user->isVerifiedMuthowif() || ! $user->muthowifProfile) {
            return false;
        }

        return $service->muthowif_profile_id === $user->muthowifProfile->id;
    }
}
