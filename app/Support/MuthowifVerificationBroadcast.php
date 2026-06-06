<?php

namespace App\Support;

use App\Events\MuthowifVerificationUpdated;
use App\Models\MuthowifProfile;

final class MuthowifVerificationBroadcast
{
    public static function notify(MuthowifProfile|string $profile): void
    {
        $model = $profile instanceof MuthowifProfile
            ? $profile
            : MuthowifProfile::query()->find((string) $profile);

        if ($model !== null) {
            ReverbBroadcast::send(new MuthowifVerificationUpdated($model), 'muthowif_verification');
        }
    }

    public static function afterResponse(MuthowifProfile|string $profile): void
    {
        self::notify($profile);
    }
}
