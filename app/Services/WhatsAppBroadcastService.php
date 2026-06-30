<?php

namespace App\Services;

use App\Jobs\SendWhatsAppTextJob;
use App\Models\MuthowifProfile;
use App\Support\IntlPhone;
use App\Support\WhatsAppNotifySettings;

class WhatsAppBroadcastService
{
    private const SEND_DELAY_MICROSECONDS = 300_000;

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
    ): array {
        $resolved = $this->resolveRecipients($muthowifProfileIds, $freeNumbersText);
        $text = $this->messageWithOptionalFileLink($message, $attachmentPublicUrl);

        $queued = 0;

        foreach ($resolved['recipients'] as $index => $recipient) {
            $job = SendWhatsAppTextJob::dispatch(
                $recipient['dial']['target'],
                $text,
                $recipient['dial']['country_calling_code'],
            );

            if ($index > 0) {
                $job->delay(now()->addMilliseconds($index * (self::SEND_DELAY_MICROSECONDS / 1000)));
            }

            $queued++;
        }

        return [
            'sent' => $queued,
            'failed' => 0,
            'skipped' => $resolved['skipped'],
            'failures' => [],
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
