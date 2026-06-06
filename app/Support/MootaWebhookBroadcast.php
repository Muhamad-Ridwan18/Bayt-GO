<?php

namespace App\Support;

use App\Events\MootaWebhookRealtimeBroadcast;
use App\Models\MootaWebhookHistory;

final class MootaWebhookBroadcast
{
    public static function notify(MootaWebhookHistory|string $history): void
    {
        $model = $history instanceof MootaWebhookHistory
            ? $history
            : MootaWebhookHistory::query()->find((string) $history);

        if ($model !== null) {
            ReverbBroadcast::send(new MootaWebhookRealtimeBroadcast($model), 'moota_webhook');
        }
    }

    public static function afterResponse(MootaWebhookHistory|string $history): void
    {
        self::notify($history);
    }
}
