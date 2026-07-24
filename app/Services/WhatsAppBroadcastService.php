<?php

namespace App\Services;

use App\Models\MuthowifProfile;
use App\Support\IntlPhone;
use App\Support\WhatsAppNotifySettings;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class WhatsAppBroadcastService
{
    /** Jeda antar penerima (detik). */
    private const SEND_STAGGER_SECONDS = 2;

    public function whatsappConfigured(): bool
    {
        return WhatsAppNotifySettings::hasToken();
    }

    /**
     * @param  list<string>  $muthowifProfileIds
     * @return array{
     *     sent: int,
     *     failed: int,
     *     skipped: int,
     *     failures: list<array{label: string, reason: string}>,
     *     invalid_numbers: list<string>
     * }
     */
    public function send(
        string $message,
        array $muthowifProfileIds,
        string $freeNumbersText,
        ?string $attachmentPublicUrl = null,
        ?string $idempotencyKey = null,
    ): array {
        $resolved = $this->resolveRecipients($muthowifProfileIds, $freeNumbersText);
        $text = $this->messageWithOptionalFileLink($message, $attachmentPublicUrl);
        $fonnte = app(FonnteService::class);

        $sent = 0;
        $failed = 0;
        $duplicateSkipped = 0;
        $failures = [];
        $total = count($resolved['recipients']);

        Log::info('whatsapp.broadcast.send.start', [
            'idempotency_key' => $idempotencyKey,
            'recipients' => $total,
            'message_len' => strlen($text),
        ]);

        foreach ($resolved['recipients'] as $index => $recipient) {
            $e164 = $this->normalizedE164($recipient['dial']);
            $fingerprint = hash('sha256', implode('|', [
                (string) $idempotencyKey,
                $e164,
                $text,
            ]));

            // Anti-duplikat: request/job yang sama tidak boleh kirim ulang ke nomor yang sama.
            if (! Cache::add('wa:broadcast:msg:'.$fingerprint, 1, now()->addMinutes(30))) {
                $duplicateSkipped++;
                Log::warning('whatsapp.broadcast.duplicate_skipped', [
                    'idempotency_key' => $idempotencyKey,
                    'e164' => $e164,
                    'label' => $recipient['label'],
                ]);

                continue;
            }

            try {
                $fonnte->sendText(
                    $recipient['dial']['target'],
                    $text,
                    $recipient['dial']['country_calling_code'],
                );
                $sent++;
            } catch (Throwable $e) {
                $failed++;
                $failures[] = [
                    'label' => $recipient['label'],
                    'reason' => $e->getMessage(),
                ];
                Cache::forget('wa:broadcast:msg:'.$fingerprint);
                Log::warning('whatsapp.broadcast.recipient_failed', [
                    'e164' => $e164,
                    'label' => $recipient['label'],
                    'exception' => $e->getMessage(),
                ]);
            }

            if ($index < $total - 1) {
                sleep(self::SEND_STAGGER_SECONDS);
            }
        }

        Log::info('whatsapp.broadcast.send.done', [
            'idempotency_key' => $idempotencyKey,
            'sent' => $sent,
            'failed' => $failed,
            'duplicate_skipped' => $duplicateSkipped,
        ]);

        return [
            'sent' => $sent,
            'failed' => $failed,
            'skipped' => $resolved['skipped'] + $duplicateSkipped,
            'failures' => $failures,
            'invalid_numbers' => $resolved['invalid_numbers'],
        ];
    }

    /**
     * @param  list<string>  $muthowifProfileIds
     * @return array{
     *     recipients: list<array{label: string, dial: array{target: string, country_calling_code: string}}>,
     *     skipped: int,
     *     invalid_numbers: list<string>
     * }
     */
    public function resolveRecipients(array $muthowifProfileIds, string $freeNumbersText): array
    {
        $recipients = [];
        $seenKeys = [];
        $skipped = 0;
        $invalidNumbers = [];

        $profileIds = array_values(array_unique(array_filter($muthowifProfileIds, fn ($id) => is_string($id) && $id !== '')));

        if ($profileIds !== []) {
            $profiles = MuthowifProfile::query()
                ->with('user')
                ->whereIn('id', $profileIds)
                ->get()
                ->keyBy('id');

            foreach ($profileIds as $profileId) {
                $profile = $profiles->get($profileId);
                if ($profile === null) {
                    $skipped++;

                    continue;
                }

                $phone = $profile->whatsAppPhone();
                if ($phone === null) {
                    $skipped++;
                    $invalidNumbers[] = ($profile->user?->name ?? 'Muthowif').' (tanpa nomor)';

                    continue;
                }

                $dial = IntlPhone::fonnteDial($phone);
                if ($dial === null) {
                    $skipped++;
                    $invalidNumbers[] = $phone.' ('.($profile->user?->name ?? 'Muthowif').')';

                    continue;
                }

                $key = $this->normalizedE164($dial);
                if (isset($seenKeys[$key])) {
                    continue;
                }

                $seenKeys[$key] = true;
                $recipients[] = [
                    'label' => trim(($profile->user?->name ?? 'Muthowif').' · '.$phone),
                    'dial' => $dial,
                ];
            }
        }

        foreach ($this->parseFreeNumbers($freeNumbersText) as $raw) {
            $dial = IntlPhone::fonnteDial($raw);
            if ($dial === null) {
                $invalidNumbers[] = $raw;

                continue;
            }

            $key = $this->normalizedE164($dial);
            if (isset($seenKeys[$key])) {
                continue;
            }

            $seenKeys[$key] = true;
            $recipients[] = [
                'label' => $raw,
                'dial' => $dial,
            ];
        }

        return [
            'recipients' => $recipients,
            'skipped' => $skipped,
            'invalid_numbers' => $invalidNumbers,
        ];
    }

    /**
     * @param  array{target: string, country_calling_code: string}  $dial
     */
    private function normalizedE164(array $dial): string
    {
        $cc = preg_replace('/\D+/', '', $dial['country_calling_code']) ?? '';
        $digits = preg_replace('/\D+/', '', $dial['target']) ?? '';

        if ($digits === '') {
            return $cc;
        }

        if (str_starts_with($digits, '0')) {
            return $cc.substr($digits, 1);
        }

        if ($cc !== '' && ! str_starts_with($digits, $cc)) {
            return $cc.$digits;
        }

        return $digits;
    }

    private function messageWithOptionalFileLink(string $message, ?string $fileUrl): string
    {
        $caption = trim($message);
        $url = trim((string) $fileUrl);

        if ($url === '') {
            return $caption;
        }

        $linkLine = __('admin.whatsapp_broadcast.file_link', ['url' => $url]);

        return $caption === '' ? $linkLine : $caption."\n\n".$linkLine;
    }

    /**
     * @return list<string>
     */
    private function parseFreeNumbers(string $text): array
    {
        $text = trim($text);
        if ($text === '') {
            return [];
        }

        $parts = preg_split('/[\s,;\n\r]+/', $text, -1, PREG_SPLIT_NO_EMPTY);

        if (! is_array($parts)) {
            return [];
        }

        return array_values(array_unique(array_filter(array_map(
            static fn (string $v): string => trim($v),
            $parts
        ), static fn (string $v): bool => $v !== '')));
    }
}
