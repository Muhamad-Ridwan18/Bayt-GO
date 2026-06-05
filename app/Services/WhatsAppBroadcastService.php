<?php

namespace App\Services;

use App\Models\MuthowifProfile;
use App\Support\IntlPhone;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class WhatsAppBroadcastService
{
    private const SEND_DELAY_MICROSECONDS = 300_000;

    public function __construct(
        private readonly FonnteService $fonnte
    ) {}

    public function whatsappConfigured(): bool
    {
        $token = config('services.fonnte.token');

        return $token !== null && $token !== '';
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
    public function send(string $message, array $muthowifProfileIds, string $freeNumbersText): array
    {
        $resolved = $this->resolveRecipients($muthowifProfileIds, $freeNumbersText);

        $sent = 0;
        $failed = 0;
        $failures = [];

        foreach ($resolved['recipients'] as $index => $recipient) {
            if ($index > 0) {
                usleep(self::SEND_DELAY_MICROSECONDS);
            }

            try {
                $this->fonnte->sendText(
                    $recipient['dial']['target'],
                    $message,
                    $recipient['dial']['country_calling_code'],
                );
                $sent++;
            } catch (RuntimeException $e) {
                $failed++;
                $failures[] = [
                    'label' => $recipient['label'],
                    'reason' => $e->getMessage(),
                ];
                Log::warning('WhatsApp broadcast failed for recipient', [
                    'label' => $recipient['label'],
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return [
            'sent' => $sent,
            'failed' => $failed,
            'skipped' => $resolved['skipped'],
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

                $phone = trim((string) $profile->phone);
                if ($phone === '') {
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

                $key = $dial['country_calling_code'].'-'.$dial['target'];
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

            $key = $dial['country_calling_code'].'-'.$dial['target'];
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
