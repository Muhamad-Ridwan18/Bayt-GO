<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class ExpoPushNotificationService
{
    private const ENDPOINT = 'https://exp.host/--/api/v2/push/send';

    /**
     * @param  list<string>  $tokens
     * @param  array<string, mixed>  $data
     */
    public function send(array $tokens, string $title, string $body, array $data = []): void
    {
        $tokens = array_values(array_unique(array_filter($tokens)));
        if ($tokens === [] || ! config('services.expo_push.enabled', true)) {
            return;
        }

        $messages = array_map(static fn (string $token) => [
            'to' => $token,
            'title' => $title,
            'body' => $body,
            'sound' => 'default',
            'data' => $data,
            'priority' => 'high',
        ], $tokens);

        foreach (array_chunk($messages, 100) as $chunk) {
            try {
                $response = Http::timeout(10)
                    ->acceptJson()
                    ->post(self::ENDPOINT, $chunk);

                if (! $response->successful()) {
                    Log::warning('expo_push.failed', [
                        'status' => $response->status(),
                        'body' => $response->json(),
                    ]);
                }
            } catch (\Throwable $e) {
                Log::warning('expo_push.exception', ['message' => $e->getMessage()]);
            }
        }
    }
}
