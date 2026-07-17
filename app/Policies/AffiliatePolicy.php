<?php

namespace App\Policies;

use App\Models\Affiliate;
use App\Models\User;

class AffiliatePolicy
{
    public function view(User $user, Affiliate $affiliate): bool
    {
        return $user->isAdmin() || $affiliate->user_id === $user->id;
    }

    public function manage(User $user, Affiliate $affiliate): bool
    {
        return $affiliate->user_id === $user->id;
    }

    public function register(User $user): bool
    {
        return ! $user->isAdmin() && $user->affiliate === null;
    }

    public function administer(User $user): bool
    {
        return $user->isAdmin();
    }
}
