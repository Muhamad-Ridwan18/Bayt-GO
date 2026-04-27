<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Log terstruktur alur pembayaran — filter di storage/logs: "[payment]"
 *
 * Nonaktifkan: PAYMENT_FLOW_LOG=false di .env
 */
final class PaymentFlowLog
{
    public static function enabled(): bool
    {
        return filter_var(env('PAYMENT_FLOW_LOG', true), FILTER_VALIDATE_BOOLEAN);
    }

    public static function info(string $step, array $context = []): void
    {
        if (! self::enabled()) {
            return;
        }

        Log::info('[payment] '.$step, $context);
    }

    public static function warning(string $step, array $context = []): void
    {
        if (! self::enabled()) {
            return;
        }

        Log::warning('[payment] '.$step, $context);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public static function dokuPayloadForLog(array $payload, int $maxJsonBytes = 16000): array
    {
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            return ['payload_json' => '[encode error]', 'top_keys' => array_keys($payload)];
        }

        $truncated = strlen($json) > $maxJsonBytes;

        return [
            'top_keys' => array_keys($payload),
            'payload_json' => $truncated ? Str::limit($json, $maxJsonBytes, '…[truncated]') : $json,
            'payload_bytes' => strlen($json),
            'payload_truncated' => $truncated,
        ];
    }
}
