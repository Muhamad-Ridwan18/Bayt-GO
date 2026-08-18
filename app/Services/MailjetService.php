<?php

namespace App\Services;

use App\Support\MailjetSettings;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class MailjetService
{
    public function configured(): bool
    {
        return MailjetSettings::hasCredentials();
    }

    /**
     * @param  list<array{ContentType: string, Filename: string, Base64Content: string}>  $attachments
     * @return array<string, mixed>
     */
    public function sendText(
        string $toEmail,
        string $subject,
        string $text,
        ?string $fromEmail = null,
        ?string $fromName = null,
        array $attachments = [],
        ?string $html = null,
    ): array {
        if (! $this->configured()) {
            throw new RuntimeException('Mailjet belum dikonfigurasi. Simpan API key & secret di pengaturan admin.');
        }

        $apiKey = (string) MailjetSettings::apiKey();
        $secretKey = (string) MailjetSettings::secretKey();
        $apiUrl = MailjetSettings::apiUrl();

        $fromEmail = trim($fromEmail ?: (string) (MailjetSettings::fromAddress() ?? ''));
        $fromName = trim($fromName ?: (string) (MailjetSettings::fromName() ?? config('app.name', 'BaytGo')));

        if ($fromEmail === '' || $toEmail === '') {
            throw new RuntimeException('Alamat From dan To wajib diisi.');
        }

        $message = [
            'From' => [
                'Email' => $fromEmail,
                'Name' => $fromName !== '' ? $fromName : config('app.name', 'BaytGo'),
            ],
            'To' => [
                [
                    'Email' => $toEmail,
                ],
            ],
            'Subject' => $subject,
            'TextPart' => $text,
        ];

        if (filled($html)) {
            $message['HTMLPart'] = $html;
        }

        if ($attachments !== []) {
            $message['Attachments'] = array_values($attachments);
        }

        $payload = [
            'Messages' => [$message],
        ];

        try {
            $response = Http::timeout(30)
                ->withBasicAuth($apiKey, $secretKey)
                ->acceptJson()
                ->asJson()
                ->post($apiUrl, $payload);
        } catch (ConnectionException $e) {
            Log::warning('Mailjet connection failed', ['exception' => $e->getMessage()]);
            throw new RuntimeException('Tidak dapat terhubung ke Mailjet. Coba lagi.');
        }

        $data = $response->json();
        if (! is_array($data)) {
            $data = ['raw' => $response->body()];
        }

        if (! $response->successful()) {
            $detail = $this->extractErrorMessage($data);
            Log::warning('Mailjet send failed', [
                'status' => $response->status(),
                'body' => $data,
            ]);
            throw new RuntimeException(
                'Gagal mengirim email via Mailjet.'.($detail !== '' ? ' '.$detail : '')
            );
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function extractErrorMessage(array $data): string
    {
        $errorMessage = $data['ErrorMessage'] ?? null;
        if (is_string($errorMessage) && $errorMessage !== '') {
            return $errorMessage;
        }

        $messages = $data['Messages'] ?? null;
        if (is_array($messages)) {
            foreach ($messages as $message) {
                if (! is_array($message)) {
                    continue;
                }
                $errors = $message['Errors'] ?? null;
                if (! is_array($errors) || $errors === []) {
                    continue;
                }
                $first = $errors[0] ?? null;
                if (is_array($first) && is_string($first['ErrorMessage'] ?? null)) {
                    return (string) $first['ErrorMessage'];
                }
            }
        }

        return '';
    }
}
