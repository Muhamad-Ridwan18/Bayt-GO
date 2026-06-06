<?php

namespace App\Support;

use App\Events\CompanyApproved;
use App\Models\User;

final class CompanyApprovedBroadcast
{
    public static function notify(User|string $user): void
    {
        $model = $user instanceof User
            ? $user
            : User::query()->find((string) $user);

        if ($model !== null) {
            ReverbBroadcast::send(new CompanyApproved($model), 'company_approved');
        }
    }

    public static function afterResponse(User|string $user): void
    {
        self::notify($user);
    }
}
