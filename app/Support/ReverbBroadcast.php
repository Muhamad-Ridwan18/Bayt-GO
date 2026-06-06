<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;

/**
 * Broadcast sinkron ke Reverb (ShouldBroadcastNow).
 * Hindari dispatch()->afterResponse() — sering tidak jalan di PHP-FPM.
 */
final class ReverbBroadcast
{
    public static function send(object $event, string $context = 'reverb'): void
    {
        $connection = config('broadcasting.default');
        if ($connection === null || $connection === 'null') {
            return;
        }

        try {
            broadcast($event);
        } catch (\Throwable $e) {
            Log::warning($context.'.broadcast_failed', [
                'event' => $event::class,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
