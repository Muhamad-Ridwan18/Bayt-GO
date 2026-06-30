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
        $token = $this->normalizeGatewayToken($token);
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
        ], $sessionId, $apiUrl);

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
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function buildPayload(array $payload, ?string $sessionId = null, ?string $apiUrl = null): array
    {
        $apiUrl ??= WhatsAppNotifySettings::apiUrl();

        if (! $this->usesFonnteOfficialApi($apiUrl)) {
            $sessionId ??= WhatsAppNotifySettings::sessionId();
            if (is_string($sessionId) && $sessionId !== '') {
                $payload['sessionId'] = $sessionId;
            }
        }

        if (isset($payload['target'], $payload['countryCode'])) {
            $payload['target'] = $this->normalizeTargetDigits(
                (string) $payload['target'],
                (string) $payload['countryCode'],
            );
            unset($payload['countryCode']);
        }

        return $payload;
    }

    private function normalizeTargetDigits(string $target, string $countryCode): string
    {
        $digits = preg_replace('/\D+/', '', $target) ?? '';
        if ($digits === '') {
            return $target;
        }

        if (str_starts_with($digits, '0')) {
            return $countryCode.substr($digits, 1);
        }

        if (! str_starts_with($digits, $countryCode)) {
            return $countryCode.$digits;
        }

        return $digits;
    }

    private function normalizeGatewayToken(string $token): string
    {
        $token = trim($token);
        if (str_starts_with(strtolower($token), 'bearer ')) {
            $token = trim(substr($token, 7));
        }

        return $token;
    }

    private function usesFonnteOfficialApi(string $apiUrl): bool
    {
        return str_contains(strtolower($apiUrl), 'api.fonnte.com');
    }

    /**
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

        if (($data['success'] ?? null) === false) {
            $reason = $data['message'] ?? $data['reason'] ?? $data['Reason'] ?? 'Tidak diketahui';
            Log::warning('Fonnte send failed', ['reason' => $reason, 'body' => $data]);
            throw new RuntimeException('WhatsApp: '.(string) $reason);
        }

        $nested = $data['data'] ?? null;
        if (is_array($nested) && ($nested['success'] ?? null) === true) {
            return;
        }

        $ok = $data['status'] ?? $data['Status'] ?? $data['success'] ?? null;
        if ($ok === true || $ok === 'true' || $ok === 1 || $ok === '1') {
            return;
        }

        $detail = $data['detail'] ?? $data['Detail'] ?? null;
        if (is_string($detail) && stripos($detail, 'success') === 0) {
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
