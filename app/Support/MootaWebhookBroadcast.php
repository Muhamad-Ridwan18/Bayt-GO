<?php

namespace App\Support;

use App\Events\MootaWebhookRealtimeBroadcast;
use App\Models\MootaWebhookHistory;
use Illuminate\Support\Facades\DB;

final class MootaWebhookBroadcast
{
    public static function afterResponse(MootaWebhookHistory|string $history): void
    {
        $historyId = (string) ($history instanceof MootaWebhookHistory ? $history->getKey() : $history);
        if ($historyId === '') {
            return;
        }

        DB::afterCommit(static function () use ($historyId): void {
            dispatch(static function () use ($historyId): void {
                $fresh = MootaWebhookHistory::query()->find($historyId);
                if ($fresh !== null) {
                    broadcast(new MootaWebhookRealtimeBroadcast($fresh));
                }
            })->afterResponse();
        });
    }
}
