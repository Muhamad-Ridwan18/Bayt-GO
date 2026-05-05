<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use JsonException;

class MootaWebhookHistory extends Model
{
    protected $table = 'moota_webhook_histories';

    protected $fillable = [
        'source_ip',
        'user_agent',
        'x_moota_user',
        'x_moota_webhook',
        'signature_header',
        'signature_verified',
        'payload',
        'payload_raw',
        'parse_error',
    ];

    protected function casts(): array
    {
        return [
            'signature_verified' => 'boolean',
            'payload' => 'array',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toRealtimeSnapshot(int $payloadPreviewLimit = 2000): array
    {
        return [
            'id' => $this->getKey(),
            'created_at' => $this->created_at?->timezone(config('app.timezone'))->format('d/m/Y H:i:s'),
            'source_ip' => $this->source_ip,
            'signature_verified' => $this->signature_verified,
            'parse_error' => $this->parse_error,
            'x_moota_user' => $this->x_moota_user,
            'x_moota_webhook' => $this->x_moota_webhook,
            'mutation_summary' => self::summarizeFirstMutation($this->payload),
            'payload_preview' => $this->truncatePayloadPreview($payloadPreviewLimit),
        ];
    }

    public function truncatePayloadPreview(int $limit = 2000): ?string
    {
        if ($this->payload === null || $this->payload === []) {
            return null;
        }

        try {
            $encoded = json_encode($this->payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } catch (JsonException) {
            return Str::limit((string) $this->payload_raw, $limit, '…');
        }

        return Str::limit($encoded, $limit, '…');
    }

    /**
     * @param  array<string, mixed>|null  $payload
     * @return array<string, mixed>|null
     */
    public static function summarizeFirstMutation(?array $payload): ?array
    {
        if ($payload === null || $payload === []) {
            return null;
        }

        if (isset($payload['mutation_id']) || isset($payload['amount']) || isset($payload['token'])) {
            /** @var array<string, mixed> $payload */
            return self::mapMutationSubset($payload);
        }

        if (isset($payload[0]) && is_array($payload[0])) {
            /** @var array<string, mixed> $first */
            $first = $payload[0];

            return self::mapMutationSubset($first);
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private static function mapMutationSubset(array $row): array
    {
        return [
            'mutation_id' => $row['mutation_id'] ?? $row['token'] ?? null,
            'amount' => $row['amount'] ?? null,
            'type' => $row['type'] ?? null,
            'note' => isset($row['note']) ? Str::limit((string) $row['note'], 120, '…') : null,
            'description' => isset($row['description']) ? Str::limit((string) $row['description'], 100, '…') : null,
        ];
    }
}
