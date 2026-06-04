<?php

namespace App\Support;

use App\Events\MuthowifVerificationUpdated;
use App\Models\MuthowifProfile;
use Illuminate\Support\Facades\DB;

final class MuthowifVerificationBroadcast
{
    public static function afterResponse(MuthowifProfile|string $profile): void
    {
        $id = (string) ($profile instanceof MuthowifProfile ? $profile->getKey() : $profile);
        if ($id === '') {
            return;
        }

        DB::afterCommit(static function () use ($id): void {
            dispatch(static function () use ($id): void {
                $row = MuthowifProfile::query()->find($id);
                if ($row !== null) {
                    broadcast(new MuthowifVerificationUpdated($row));
                }
            })->afterResponse();
        });
    }
}
