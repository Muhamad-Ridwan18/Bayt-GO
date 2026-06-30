<?php

namespace App\Policies;

use App\Models\MuthowifSupportPackage;
use App\Models\User;

class MuthowifSupportPackagePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isVerifiedMuthowif();
    }

    public function create(User $user): bool
    {
        return $user->isVerifiedMuthowif();
    }

    public function update(User $user, MuthowifSupportPackage $package): bool
    {
        return $this->ownsPackage($user, $package);
    }

    public function delete(User $user, MuthowifSupportPackage $package): bool
    {
        return $this->ownsPackage($user, $package);
    }

    private function ownsPackage(User $user, MuthowifSupportPackage $package): bool
    {
        if (! $user->isVerifiedMuthowif() || ! $user->muthowifProfile) {
            return false;
        }

        return (string) $package->muthowif_profile_id === (string) $user->muthowifProfile->id;
    }
}
