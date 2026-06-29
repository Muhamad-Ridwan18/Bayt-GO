<?php

namespace App\Services;

use App\Support\WhatsAppNotifySettings;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class FonnteService
{
    public function sendText(string $target, string $message, ?string $countryCallingCode = null): void
    {
        $this->sendTextWithGateway(
            WhatsAppNotifySettings::token() ?? '',
            WhatsAppNotifySettings::apiUrl(),
            WhatsAppNotifySettings::sessionId(),
            WhatsAppNotifySettings::countryCode(),
            $target,
            $message,
            $countryCallingCode,
        );
    }

    public function sendTextWithGateway(
        string $token,
        string $apiUrl,
        ?string $sessionId,
        string $defaultCountryCode,
        string $target,
        string $message,
        ?string $countryCallingCode = null,
    ): void {
        if ($token === '') {
            throw new RuntimeException('Token WhatsApp belum diatur.');
        }

        $cc = $countryCallingCode !== null && $countryCallingCode !== ''
            ? $countryCallingCode
            : $defaultCountryCode;

        $payload = $this->buildPayload([
            'target' => $target,
            'message' => $message,
            'countryCode' => $cc,
        ], $sessionId);

        try {
            /** @var Response $response */
            $response = Http::timeout(30)
                ->withHeaders($this->authHeaders($token))
                ->asForm()
                ->post($apiUrl, $payload);
        } catch (ConnectionException $e) {
            Log::warning('Fonnte connection failed', ['exception' => $e->getMessage()]);
            throw new RuntimeException('Tidak dapat terhubung ke layanan WhatsApp. Coba lagi.');
        }

        $this->assertSuccessfulResponse($response);
    }

    /**
     * Kirim teks + lampiran dari URL publik (harus dapat diakses internet; localhost tidak valid).
     * Untuk PDF/non-image, set $filename agar nama file terbaca di penerima.
     *
     * @see https://docs.fonnte.com/api-send-message/
     *
     * @param  string|null  $countryCallingCode  Lihat {@see sendText()}.
     */
    public function sendMessageWithPublicFileUrl(string $target, string $message, string $publicFileUrl, ?string $filename = null, ?string $countryCallingCode = null): void
    {
        $cc = $this->resolveCountryCode($countryCallingCode);

        $token = $this->requireToken();
        $url = WhatsAppNotifySettings::apiUrl();

        $payload = $this->buildPayload([
            'target' => $target,
            'message' => $message,
            'url' => $publicFileUrl,
            'countryCode' => $cc,
        ]);

        if ($filename !== null && $filename !== '') {
            $payload['filename'] = $filename;
        }

        try {
            /** @var Response $response */
            $response = Http::timeout(45)
                ->withHeaders($this->authHeaders($token))
                ->asForm()
                ->post($url, $payload);
        } catch (ConnectionException $e) {
            Log::warning('Fonnte connection failed', ['exception' => $e->getMessage()]);
            throw new RuntimeException('Tidak dapat terhubung ke layanan WhatsApp. Coba lagi.');
        }

        $this->assertSuccessfulResponse($response);
    }

    /**
     * Upload file langsung ke /send (multipart) — tidak perlu URL publik.
     * WSM auto-detect: .jpg/.png → image, .pdf → document.
     *
     * @param  string|null  $countryCallingCode  Lihat {@see sendText()}.
     */
    public function sendMessageWithFileUpload(
        string $target,
        string $message,
        string $fileContents,
        string $filename,
        ?string $countryCallingCode = null,
    ): void {
        if ($fileContents === '') {
            throw new RuntimeException('Berkas lampiran kosong atau tidak dapat dibaca.');
        }

        $token = $this->requireToken();
        $url = WhatsAppNotifySettings::apiUrl();
        $cc = $this->resolveCountryCode($countryCallingCode);

        $payload = $this->buildPayload([
            'target' => $target,
            'countryCode' => $cc,
            'message' => $message,
            'filename' => $filename,
        ]);

        try {
            /** @var Response $response */
            $response = Http::timeout(60)
                ->withHeaders($this->authHeaders($token))
                ->attach('file', $fileContents, $filename)
                ->post($url, $payload);
        } catch (ConnectionException $e) {
            Log::warning('Fonnte connection failed', ['exception' => $e->getMessage()]);
            throw new RuntimeException('Tidak dapat terhubung ke layanan WhatsApp. Coba lagi.');
        }

        $this->assertSuccessfulResponse($response);
    }

    private function requireToken(): string
    {
        $token = WhatsAppNotifySettings::token();
        if ($token === null || $token === '') {
            throw new RuntimeException('Token WhatsApp belum diatur.');
        }

        return $token;
    }

    private function resolveCountryCode(?string $countryCallingCode): string
    {
        return $countryCallingCode !== null && $countryCallingCode !== ''
            ? $countryCallingCode
            : WhatsAppNotifySettings::countryCode();
    }

    /**
     * WSM /send (alias Fonnte): session otomatis dari API key — sessionId tidak wajib.
     * FONNTE_SESSION_ID hanya override opsional (mis. endpoint native /api/message/send).
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function buildPayload(array $payload, ?string $sessionId = null): array
    {
        $sessionId ??= WhatsAppNotifySettings::sessionId();
        if (is_string($sessionId) && $sessionId !== '') {
            $payload['sessionId'] = $sessionId;
        }

        return $payload;
    }

    /**
     * Fonnte: Authorization: {token}
     * WSM: Authorization: {apiKey} atau Bearer {apiKey} — keduanya didukung.
     *
     * @return array<string, string>
     */
    private function authHeaders(string $token): array
    {
        return ['Authorization' => $token];
    }

    private function assertSuccessfulResponse(Response $response): void
    {
        if (! $response->successful()) {
            $detail = $this->extractErrorMessage($response);
            Log::warning('Fonnte HTTP error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new RuntimeException(
                'Gagal menghubungi layanan WhatsApp.'.($detail !== '' ? ' '.$detail : '')
            );
        }

        $data = $response->json();
        if (! is_array($data)) {
            return;
        }

        $ok = $data['status'] ?? $data['Status'] ?? $data['success'] ?? null;
        if ($ok === true || $ok === 'true') {
            return;
        }

        if ($ok === null && $response->status() === 202) {
            return;
        }

        $reason = $data['reason'] ?? $data['Reason'] ?? $data['message'] ?? 'Tidak diketahui';
        if (is_array($reason)) {
            $reason = json_encode($reason, JSON_UNESCAPED_UNICODE) ?: 'Tidak diketahui';
        }

        Log::warning('Fonnte send failed', ['reason' => $reason, 'body' => $data]);
        throw new RuntimeException('WhatsApp: '.(string) $reason);
    }

    private function extractErrorMessage(Response $response): string
    {
        $data = $response->json();
        if (! is_array($data)) {
            return '';
        }

        $message = $data['message'] ?? $data['reason'] ?? $data['Reason'] ?? '';
        if (is_string($message) && $message !== '') {
            return $message;
        }

        return '';
    }
}
