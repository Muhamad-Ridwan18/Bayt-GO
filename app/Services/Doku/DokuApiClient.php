<?php

namespace App\Services\Doku;

use App\Support\PaymentFlowLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

final class DokuApiClient
{
    public function baseUrl(): string
    {
        return config('services.doku.is_production', false)
            ? 'https://api.doku.com'
            : 'https://api-sandbox.doku.com';
    }

    /**
     * @param  non-empty-string  $requestTarget  Absolute path starting with / (e.g. /checkout/v1/payment)
     * @return array<string, mixed>
     */
    public function postJson(string $requestTarget, array $body): array
    {
        $clientId = (string) config('services.doku.client_id');
        $secret = (string) config('services.doku.secret_key');
        if ($clientId === '' || $secret === '') {
            throw new RuntimeException('DOKU_CLIENT_ID / DOKU_SECRET_KEY belum diatur.');
        }

        $requestId = (string) Str::uuid();
        $timestamp = gmdate('Y-m-d\TH:i:s').'Z';
        $json = json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            throw new RuntimeException('Gagal mengenkode body permintaan DOKU.');
        }

        $digest = DokuSignature::digestBody($json);
        $signature = DokuSignature::signRequest($clientId, $requestId, $timestamp, $requestTarget, $digest, $secret);

        $invoiceFromBody = null;
        $decodedPreview = json_decode($json, true);
        if (is_array($decodedPreview)) {
            $invoiceFromBody = data_get($decodedPreview, 'order.invoice_number');
        }

        $started = microtime(true);
        PaymentFlowLog::info('doku.api.post', [
            'path' => $requestTarget,
            'base_url' => $this->baseUrl(),
            'invoice_number' => is_string($invoiceFromBody) ? $invoiceFromBody : null,
            'body_bytes' => strlen($json),
        ]);

        $response = Http::timeout(60)
            ->withHeaders([
                'Client-Id' => $clientId,
                'Request-Id' => $requestId,
                'Request-Timestamp' => $timestamp,
                'Signature' => $signature,
                'Content-Type' => 'application/json',
            ])
            ->withBody($json, 'application/json')
            ->post($this->baseUrl().$requestTarget);

        $elapsedMs = (int) round((microtime(true) - $started) * 1000);

        if (! $response->successful()) {
            $raw = $response->body();
            $decoded = $response->json();
            $code = is_array($decoded) ? data_get($decoded, 'error.code') : null;
            $apiMessage = is_array($decoded) ? data_get($decoded, 'error.message') : null;
            Log::warning('DOKU API error', [
                'path' => $requestTarget,
                'status' => $response->status(),
                'body' => $raw,
            ]);
            PaymentFlowLog::warning('doku.api.error', [
                'path' => $requestTarget,
                'http_status' => $response->status(),
                'elapsed_ms' => $elapsedMs,
                'invoice_number' => is_string($invoiceFromBody) ? $invoiceFromBody : null,
            ]);

            if ($code === 'invalid_client_id' || $code === 'invalid_header_request') {
                $base = $this->baseUrl();
                throw new RuntimeException(
                    'DOKU tidak mengenali Client-Id di host ini ('.$base.'). '
                    .'Format BRN-… atau MCH-… normal untuk Jokul Checkout; yang penting pasangan Client ID + Secret Key '
                    .'dan lingkungan sama: sandbox (DOKU_IS_PRODUCTION=false → api-sandbox.doku.com) atau live (true → api.doku.com). '
                    .'Salin keduanya dari baris yang sama di Back Office (Accept Payment / Jokul). '
                    .'Banyak akun hanya punya kredensial production — kalau sandbox selalu ditolak, coba DOKU_IS_PRODUCTION=true dengan kredensial live (hati-hati transaksi nyata). '
                    .'Setelah ubah .env: php artisan config:clear.',
                );
            }

            $hint = is_string($apiMessage) && $apiMessage !== '' ? $apiMessage : 'Periksa log.';

            throw new RuntimeException('Gagal menghubungi DOKU: '.$hint);
        }

        $decoded = $response->json();
        if (! is_array($decoded)) {
            PaymentFlowLog::warning('doku.api.invalid_json', ['path' => $requestTarget, 'elapsed_ms' => $elapsedMs]);
            throw new RuntimeException('Respons DOKU tidak valid.');
        }

        PaymentFlowLog::info('doku.api.ok', [
            'path' => $requestTarget,
            'http_status' => $response->status(),
            'elapsed_ms' => $elapsedMs,
            'invoice_number' => is_string($invoiceFromBody) ? $invoiceFromBody : null,
            'response_top_keys' => array_keys($decoded),
        ]);

        /** @var array<string, mixed> $decoded */
        return $decoded;
    }
}
