<?php

namespace App\Services\Seo;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Search Console API dengan service account.
 *
 * `google/apiclient` menarik puluhan paket hanya untuk dua endpoint, sementara
 * yang dibutuhkan cuma JWT RS256 — openssl bawaan PHP sudah cukup.
 */
final class GoogleSearchConsoleClient
{
    private const TOKEN_CACHE_KEY = 'seo:gsc:access_token';

    /** Readonly sudah mencakup searchAnalytics dan urlInspection. */
    private const SCOPE = 'https://www.googleapis.com/auth/webmasters.readonly';

    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';

    public function isConfigured(): bool
    {
        return $this->siteUrl() !== '' && $this->credentials() !== null;
    }

    /** Properti domain memakai bentuk `sc-domain:baytgo.id`. */
    public function siteUrl(): string
    {
        return trim((string) config('services.google_search_console.site_url', ''));
    }

    /**
     * @param  list<string>  $dimensions  kosong = total agregat
     * @return list<array{keys: list<string>, clicks: float, impressions: float, ctr: float, position: float}>
     */
    public function searchAnalytics(
        string $startDate,
        string $endDate,
        array $dimensions = [],
        int $rowLimit = 100,
    ): array {
        $body = [
            'startDate' => $startDate,
            'endDate' => $endDate,
            'rowLimit' => $rowLimit,
            'dataState' => 'final',
        ];

        if ($dimensions !== []) {
            $body['dimensions'] = $dimensions;
        }

        $url = 'https://www.googleapis.com/webmasters/v3/sites/'
            .rawurlencode($this->siteUrl())
            .'/searchAnalytics/query';

        $rows = $this->post($url, $body)['rows'] ?? [];

        return array_values(array_map(static fn (array $row): array => [
            'keys' => array_map('strval', $row['keys'] ?? []),
            'clicks' => (float) ($row['clicks'] ?? 0),
            'impressions' => (float) ($row['impressions'] ?? 0),
            'ctr' => (float) ($row['ctr'] ?? 0),
            'position' => (float) ($row['position'] ?? 0),
        ], $rows));
    }

    /**
     * Status indexing satu URL. Kuota 2000 permintaan/hari per properti.
     *
     * @return array{indexed: bool, verdict: string, coverage: string, last_crawl: ?string}
     */
    public function inspect(string $inspectionUrl): array
    {
        $result = $this->post('https://searchconsole.googleapis.com/v1/urlInspection/index:inspect', [
            'inspectionUrl' => $inspectionUrl,
            'siteUrl' => $this->siteUrl(),
            'languageCode' => 'id-ID',
        ]);

        $status = $result['inspectionResult']['indexStatusResult'] ?? [];
        $verdict = (string) ($status['verdict'] ?? 'VERDICT_UNSPECIFIED');

        return [
            'indexed' => $verdict === 'PASS',
            'verdict' => $verdict,
            'coverage' => (string) ($status['coverageState'] ?? '-'),
            'last_crawl' => isset($status['lastCrawlTime']) ? (string) $status['lastCrawlTime'] : null,
        ];
    }

    /**
     * @param  array<string, mixed>  $body
     * @return array<string, mixed>
     */
    private function post(string $url, array $body): array
    {
        $response = Http::withToken($this->accessToken())
            ->timeout(30)
            ->retry(2, 750, throw: false)
            ->post($url, $body);

        if ($response->failed()) {
            // Token bisa dicabut di sisi Google; jangan sajikan yang basi di run berikutnya.
            if (in_array($response->status(), [401, 403], true)) {
                Cache::forget(self::TOKEN_CACHE_KEY);
            }

            throw new RuntimeException(sprintf(
                'Search Console API menolak permintaan (HTTP %d): %s',
                $response->status(),
                mb_substr((string) ($response->json('error.message') ?? $response->body()), 0, 300),
            ));
        }

        return (array) $response->json();
    }

    private function accessToken(): string
    {
        // Token Google berlaku 1 jam; disimpan 55 menit agar tidak kedaluwarsa di tengah run.
        return Cache::remember(self::TOKEN_CACHE_KEY, 3300, function (): string {
            $credentials = $this->credentials();

            if ($credentials === null) {
                throw new RuntimeException('Kredensial service account Search Console belum diset.');
            }

            $response = Http::asForm()->timeout(20)->post(self::TOKEN_URL, [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $this->buildJwt($credentials),
            ]);

            $token = (string) $response->json('access_token', '');

            if ($response->failed() || $token === '') {
                throw new RuntimeException(sprintf(
                    'Gagal menukar JWT dengan access token (HTTP %d): %s',
                    $response->status(),
                    mb_substr((string) ($response->json('error_description') ?? $response->body()), 0, 200),
                ));
            }

            return $token;
        });
    }

    /**
     * @param  array{client_email: string, private_key: string}  $credentials
     */
    private function buildJwt(array $credentials): string
    {
        $now = time();

        $payload = implode('.', [
            $this->base64Url(json_encode(['alg' => 'RS256', 'typ' => 'JWT'], JSON_THROW_ON_ERROR)),
            $this->base64Url(json_encode([
                'iss' => $credentials['client_email'],
                'scope' => self::SCOPE,
                'aud' => self::TOKEN_URL,
                'iat' => $now,
                'exp' => $now + 3600,
            ], JSON_THROW_ON_ERROR)),
        ]);

        $signature = '';

        if (! openssl_sign($payload, $signature, $credentials['private_key'], OPENSSL_ALGO_SHA256)) {
            throw new RuntimeException('Gagal menandatangani JWT — periksa private_key service account.');
        }

        return $payload.'.'.$this->base64Url($signature);
    }

    /**
     * Terima file JSON (disarankan) atau JSON yang di-base64 lewat env.
     *
     * @return array{client_email: string, private_key: string}|null
     */
    private function credentials(): ?array
    {
        $config = (array) config('services.google_search_console', []);

        $raw = '';
        $path = trim((string) ($config['credentials_path'] ?? ''));

        if ($path !== '') {
            $path = str_starts_with($path, '/') || preg_match('/^[A-Za-z]:/', $path) === 1
                ? $path
                : base_path($path);

            $raw = is_readable($path) ? (string) file_get_contents($path) : '';
        }

        if ($raw === '') {
            $encoded = trim((string) ($config['credentials_json'] ?? ''));
            // Private key mengandung newline, jadi base64 lebih aman ditaruh di .env.
            $raw = $encoded === '' ? '' : (string) (base64_decode($encoded, true) ?: $encoded);
        }

        if ($raw === '') {
            return null;
        }

        $decoded = json_decode($raw, true);

        if (! is_array($decoded)) {
            return null;
        }

        $email = trim((string) ($decoded['client_email'] ?? ''));
        $key = (string) ($decoded['private_key'] ?? '');

        return $email !== '' && $key !== ''
            ? ['client_email' => $email, 'private_key' => $key]
            : null;
    }

    private function base64Url(string $raw): string
    {
        return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
    }
}
