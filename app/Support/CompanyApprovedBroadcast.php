<?php

namespace App\Support;

use App\Events\CompanyApproved;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class CompanyApprovedBroadcast
{
    public static function afterResponse(User|string $user): void
    {
        $userId = (string) ($user instanceof User ? $user->getKey() : $user);
        if ($userId === '') {
            return;
        }

        DB::afterCommit(static function () use ($userId): void {
            dispatch(static function () use ($userId): void {
                $fresh = User::query()->find($userId);
                if ($fresh !== null) {
                    broadcast(new CompanyApproved($fresh));
                }
            })->afterResponse();
        });
    }
}
