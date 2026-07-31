<?php

namespace App\Support;

use App\Jobs\SendMailjetTextJob;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Email transaksi Mailjet (teks + lampiran), terpisah dari notifikasi WhatsApp biasa.
 */
final class DualChannelNotify
{
    /**
     * @param  list<array{ContentType: string, Filename: string, Base64Content: string}>  $attachments
     */
    public static function queueEmail(
        ?string $email,
        string $message,
        ?string $subject = null,
        bool $afterResponse = false,
        bool $sync = false,
        array $attachments = [],
    ): void {
        $email = self::normalizeEmail($email);
        if ($email === null) {
            return;
        }

        if (! MailjetSettings::hasCredentials() || ! filled(MailjetSettings::fromAddress())) {
            Log::debug('Mailjet notify skipped: kredensial/from belum dikonfigurasi.', [
                'to' => $email,
            ]);

            return;
        }

        $subject = trim((string) $subject);
        if ($subject === '') {
            $subject = self::subjectFromMessage($message);
        }

        if ($sync) {
            SendMailjetTextJob::dispatchSync($email, $subject, $message, false, $attachments);

            return;
        }

        if ($afterResponse) {
            SendMailjetTextJob::dispatchAfterResponse($email, $subject, $message, false, $attachments);

            return;
        }

        SendMailjetTextJob::dispatch($email, $subject, $message, false, $attachments);
    }

    public static function subjectFromMessage(string $message): string
    {
        $firstLine = trim((string) Str::of($message)->before("\n"));
        $firstLine = trim(str_replace(['*', '_', '~'], '', $firstLine));
        $firstLine = preg_replace('/\s+/u', ' ', $firstLine) ?? $firstLine;

        if ($firstLine === '') {
            return (string) config('app.name', 'BaytGo');
        }

        return Str::limit($firstLine, 120, '');
    }

    public static function normalizeEmail(?string $email): ?string
    {
        $email = strtolower(trim((string) $email));
        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        return $email;
    }
}
