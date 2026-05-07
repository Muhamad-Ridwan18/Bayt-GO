<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use JsonException;

class MootaWebhookHistory extends Model
{
    protected $table = 'moota_webhook_histories';

    /** Batas aman untuk tampilan per baris (browser + broadcast). */
    private const PAYLOAD_EXPAND_MAX_CHARS = 45000;

    private const PAYLOAD_LIST_PREVIEW_CHARS = 1800;

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
     * Snapshot untuk feed admin (halaman + Echo). Selalu sertakan teks body yang bisa dibaca,
     * dengan fallback ke `payload_raw` bila kolom JSON kosong atau gagal parse.
     *
     * @return array<string, mixed>
     */
    public function toRealtimeSnapshot(
        ?int $listPreviewChars = null,
        ?int $expandChars = null,
    ): array {
        $listLimit = $listPreviewChars ?? self::PAYLOAD_LIST_PREVIEW_CHARS;
        $expandLimit = min($expandChars ?? self::PAYLOAD_EXPAND_MAX_CHARS, self::PAYLOAD_EXPAND_MAX_CHARS);

        $display = $this->buildPayloadDisplay($expandLimit);

        $payloadForSummary = is_array($this->payload) && $this->payload !== [] ? $this->payload : null;
        if ($payloadForSummary === null && filled($this->payload_raw)) {
            $decoded = json_decode((string) $this->payload_raw, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $payloadForSummary = $decoded;
            }
        }

        $mutation = self::summarizeFirstMutation($payloadForSummary);

        return [
            'id' => $this->getKey(),
            'created_at' => $this->created_at?->timezone(config('app.timezone'))->format('d/m/Y H:i:s'),
            'source_ip' => $this->source_ip,
            'signature_verified' => $this->signature_verified,
            'parse_error' => $this->parse_error,
            'x_moota_user' => $this->x_moota_user,
            'x_moota_webhook' => $this->x_moota_webhook,
            'mutation_summary' => $mutation,
            'payload_preview' => Str::limit($display['text'], $listLimit, '…'),
            'payload_expand' => $display['text'],
            'payload_meta' => [
                'source' => $display['source'],
                'bytes' => $display['bytes'],
                'truncated' => $display['truncated'],
            ],
            'request_meta' => [
                'user_agent' => $this->user_agent ? Str::limit((string) $this->user_agent, 500, '…') : null,
                'signature_header' => $this->signature_header ? Str::limit((string) $this->signature_header, 180, '…') : null,
            ],
        ];
    }

    /**
     * @return array{text: string, truncated: bool, bytes: int, source: string}
     */
    public function buildPayloadDisplay(int $maxChars): array
    {
        if (is_array($this->payload) && $this->payload !== []) {
            try {
                $text = json_encode($this->payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
                $len = strlen($text);

                return [
                    'text' => $this->limitUtf8Bytes($text, $maxChars),
                    'truncated' => $len > $maxChars,
                    'bytes' => $len,
                    'source' => 'parsed_json',
                ];
            } catch (JsonException) {
                // lanjut ke body mentah
            }
        }

        $raw = (string) ($this->payload_raw ?? '');
        if ($raw === '') {
            return ['text' => '—', 'truncated' => false, 'bytes' => 0, 'source' => 'empty'];
        }

        $decoded = json_decode($raw, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            try {
                $text = json_encode($decoded, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
                $len = strlen($text);

                return [
                    'text' => $this->limitUtf8Bytes($text, $maxChars),
                    'truncated' => $len > $maxChars,
                    'bytes' => $len,
                    'source' => 'raw_json_pretty',
                ];
            } catch (JsonException) {
                // fallback literal
            }
        }

        $len = strlen($raw);

        return [
            'text' => $this->limitUtf8Bytes($raw, $maxChars),
            'truncated' => $len > $maxChars,
            'bytes' => $len,
            'source' => 'raw_text',
        ];
    }

    private function limitUtf8Bytes(string $text, int $maxChars): string
    {
        if (strlen($text) <= $maxChars) {
            return $text;
        }

        return Str::limit($text, $maxChars, '…');
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
        $pd = is_array($row['payment_detail'] ?? null) ? $row['payment_detail'] : [];
        $blob = json_encode($row, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '';

        return [
            'mutation_id' => $row['mutation_id'] ?? $row['token'] ?? null,
            'amount' => $row['amount'] ?? null,
            'type' => $row['type'] ?? null,
            'order_id' => isset($pd['order_id']) && is_string($pd['order_id']) ? $pd['order_id'] : null,
            'trx_id' => isset($pd['trx_id']) && is_string($pd['trx_id']) ? $pd['trx_id'] : null,
            'payment_detail_status' => isset($pd['status']) && is_string($pd['status']) ? $pd['status'] : null,
            'booking_code' => self::extractBkCodeFromHaystack($blob),
            'note' => isset($row['note']) ? Str::limit((string) $row['note'], 120, '…') : null,
            'description' => isset($row['description']) ? Str::limit((string) $row['description'], 100, '…') : null,
        ];
    }

    private static function extractBkCodeFromHaystack(string $haystack): ?string
    {
        return preg_match('/\b(BK-BYTG[0-9]+)\b/', $haystack, $m) ? $m[1] : null;
    }
}
