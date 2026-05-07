<?php

namespace App\Services\Moota;

use App\Support\PaymentFlowLog;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

final class MootaApiClient
{
    private const CACHE_TOKEN_KEY = 'moota:v2_access_token';

    public function baseUrl(): string
    {
        return rtrim((string) config('services.moota.api_base_url', 'https://api.moota.co'), '/');
    }

    public function isConfigured(): bool
    {
        $email = trim((string) config('services.moota.api_email'));
        $password = (string) config('services.moota.api_password');
        /** @var array<int, string> $accounts */
        $accounts = config('services.moota.bank_account_ids', []);

        return $email !== '' && $password !== '' && $accounts !== [];
    }

    /**
     * Hanya kredensial API (tanpa MOOTA_BANK_ACCOUNT_ID). Dipakai untuk daftar webhook via API.
     */
    public function isAuthConfigured(): bool
    {
        $email = trim((string) config('services.moota.api_email'));
        $password = (string) config('services.moota.api_password');

        return $email !== '' && $password !== '';
    }

    /**
     * Create Transaction Moota hanya menerima satu `bank_account_id` per request.
     */
    public function resolveBankAccountIdForCharge(): string
    {
        /** @var array<int, string> $ids */
        $ids = config('services.moota.bank_account_ids', []);
        $ids = array_values(array_filter(array_map(trim(...), $ids)));
        if ($ids === []) {
            return '';
        }
        if (count($ids) === 1) {
            return $ids[0];
        }

        $mode = (string) config('services.moota.bank_account_pick', 'first');

        return match ($mode) {
            'round_robin', 'round-robin', 'roundrobin' => $this->pickBankAccountIdRoundRobin($ids),
            default => $ids[0],
        };
    }

    /**
     * Daftar ID rekening Moota (unik, tak kosong).
     *
     * @return list<string>
     */
    public function bankAccountIds(): array
    {
        /** @var array<int, string> $ids */
        $ids = config('services.moota.bank_account_ids', []);

        return array_values(array_filter(array_map(trim(...), $ids)));
    }

    /**
     * @param  non-empty-array<int, string>  $ids
     */
    private function pickBankAccountIdRoundRobin(array $ids): string
    {
        $cacheKey = 'moota:bank_account_rr';
        $i = Cache::increment($cacheKey);
        if ($i === false) {
            Cache::put($cacheKey, 1, now()->addYear());
            $i = 1;
        }

        $n = count($ids);

        return $ids[($i - 1) % $n];
    }

    private function loginAndToken(): string
    {
        $email = trim((string) config('services.moota.api_email'));
        $password = (string) config('services.moota.api_password');
        $body = [
            'email' => $email,
            'password' => $password,
            'scopes' => ['api'],
        ];

        $started = microtime(true);
        PaymentFlowLog::info('moota.api.login', ['base_url' => $this->baseUrl()]);
        $response = Http::timeout(45)->acceptJson()->asJson()->post($this->baseUrl().'/api/v2/auth/login', $body);
        $elapsedMs = (int) round((microtime(true) - $started) * 1000);

        if (! $response->successful()) {
            Log::warning('Moota API login gagal', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            PaymentFlowLog::warning('moota.api.login_error', ['status' => $response->status(), 'elapsed_ms' => $elapsedMs]);

            throw new RuntimeException('Login Moota gagal ('.$response->status().'). Periksa MOOTA_API_EMAIL / MOOTA_API_PASSWORD.');
        }

        /** @var array<string, mixed> $json */
        $json = $response->json() ?? [];
        $token = null;
        if (isset($json['access_token']) && is_string($json['access_token']) && $json['access_token'] !== '') {
            $token = $json['access_token'];
        } else {
            $nested = data_get($json, 'data.access_token');
            if (is_string($nested) && $nested !== '') {
                $token = $nested;
            }
        }

        if (! is_string($token) || $token === '') {
            PaymentFlowLog::warning('moota.api.login_no_token', ['elapsed_ms' => $elapsedMs]);

            throw new RuntimeException('Respons login Moota tidak berisi access_token.');
        }

        PaymentFlowLog::info('moota.api.login_ok', ['elapsed_ms' => $elapsedMs]);

        return $token;
    }

    public function bearerToken(): string
    {
        $minutes = max(5, min(720, (int) config('services.moota.token_cache_minutes', 55)));

        return Cache::remember(self::CACHE_TOKEN_KEY.'|'.$this->configFingerprint(), now()->addMinutes($minutes), function (): string {
            return $this->loginAndToken();
        });
    }

    public function forgetCachedToken(): void
    {
        Cache::forget(self::CACHE_TOKEN_KEY.'|'.$this->configFingerprint());
    }

    private function configFingerprint(): string
    {
        return hash('sha256', $this->baseUrl().'|'.(string) config('services.moota.api_email'));
    }

    /**
     * POST /api/v2/create-transaction
     *
     * @param  array<string, mixed>  $body
     * @return array<string, mixed>
     */
    public function createTransaction(array $body): array
    {
        $token = $this->bearerToken();
        $started = microtime(true);
        PaymentFlowLog::info('moota.api.create_transaction', [
            'order_id' => $body['order_id'] ?? null,
            'amount_total' => $body['total'] ?? null,
        ]);

        $response = Http::timeout(60)->acceptJson()->asJson()->withToken($token)
            ->post($this->baseUrl().'/api/v2/create-transaction', $body);

        $elapsedMs = (int) round((microtime(true) - $started) * 1000);

        if ($response->status() === 401) {
            $this->forgetCachedToken();
            $token = $this->bearerToken();
            $response = Http::timeout(60)->acceptJson()->asJson()->withToken($token)
                ->post($this->baseUrl().'/api/v2/create-transaction', $body);
            $elapsedMs = (int) round((microtime(true) - $started) * 1000);
        }

        /** @var array<string, mixed> $json */
        $json = $response->json() ?? [];

        if (! $response->successful()) {
            $message = (string) (data_get($json, 'message') ?: $response->body());
            Log::warning('Moota create-transaction gagal', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            PaymentFlowLog::warning('moota.api.create_transaction_error', [
                'status' => $response->status(),
                'elapsed_ms' => $elapsedMs,
                'message' => $message,
            ]);

            throw new RuntimeException('Buat transaksi Moota gagal: '.$message);
        }

        PaymentFlowLog::info('moota.api.create_transaction_ok', ['elapsed_ms' => $elapsedMs]);

        return $json;
    }

    /**
     * GET /api/v2/integration/webhook
     *
     * @param  array<string, scalar|null>  $query  e.g. url, bank_account_id, page, per_page
     * @return array<string, mixed>
     */
    public function listIntegrationWebhooks(array $query = []): array
    {
        $token = $this->bearerToken();
        $started = microtime(true);
        PaymentFlowLog::info('moota.api.list_integration_webhook', ['query' => $query]);

        $response = Http::timeout(45)->acceptJson()->withToken($token)
            ->get($this->baseUrl().'/api/v2/integration/webhook', $query);

        if ($response->status() === 401) {
            $this->forgetCachedToken();
            $token = $this->bearerToken();
            $response = Http::timeout(45)->acceptJson()->withToken($token)
                ->get($this->baseUrl().'/api/v2/integration/webhook', $query);
        }

        $elapsedMs = (int) round((microtime(true) - $started) * 1000);
        /** @var array<string, mixed> $json */
        $json = $response->json() ?? [];

        if (! $response->successful()) {
            $message = (string) (data_get($json, 'message') ?: $response->body());
            Log::warning('Moota list integration webhook gagal', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            PaymentFlowLog::warning('moota.api.list_integration_webhook_error', [
                'status' => $response->status(),
                'elapsed_ms' => $elapsedMs,
                'message' => $message,
            ]);

            throw new RuntimeException('Daftar webhook Moota gagal (HTTP '.$response->status().'): '.$message);
        }

        PaymentFlowLog::info('moota.api.list_integration_webhook_ok', ['elapsed_ms' => $elapsedMs]);

        return $json;
    }

    /**
     * POST /api/v2/integration/webhook — mendaftarkan URL mutasi (lihat Postman / docs Moota).
     *
     * @param  array<string, mixed>  $body  url, bank_account_id, kinds, secret_token, start_unique_code, end_unique_code
     * @return array<string, mixed>
     */
    public function createIntegrationWebhook(array $body): array
    {
        $token = $this->bearerToken();
        $started = microtime(true);
        PaymentFlowLog::info('moota.api.create_integration_webhook', ['url' => $body['url'] ?? null]);

        $response = Http::timeout(45)->acceptJson()->asJson()->withToken($token)
            ->post($this->baseUrl().'/api/v2/integration/webhook', $body);

        if ($response->status() === 401) {
            $this->forgetCachedToken();
            $token = $this->bearerToken();
            $response = Http::timeout(45)->acceptJson()->asJson()->withToken($token)
                ->post($this->baseUrl().'/api/v2/integration/webhook', $body);
        }

        $elapsedMs = (int) round((microtime(true) - $started) * 1000);
        /** @var array<string, mixed> $json */
        $json = $response->json() ?? [];

        if (! $response->successful()) {
            $message = (string) (data_get($json, 'message') ?: $response->body());
            Log::warning('Moota create integration webhook gagal', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            PaymentFlowLog::warning('moota.api.create_integration_webhook_error', [
                'status' => $response->status(),
                'elapsed_ms' => $elapsedMs,
                'message' => $message,
            ]);

            throw new RuntimeException('Buat webhook Moota gagal (HTTP '.$response->status().'): '.$message);
        }

        PaymentFlowLog::info('moota.api.create_integration_webhook_ok', ['elapsed_ms' => $elapsedMs]);

        return $json;
    }

    /**
     * GET /api/v2/bank — cache singkat untuk label UI pembayaran.
     *
     * @return array<string, array{label: string, bank_type: string, account_number: string, atas_nama: string}>
     */
    public function bankAccountDetailsByIdMap(): array
    {
        return Cache::remember(
            'moota:bank_account_details|'.$this->configFingerprint(),
            now()->addMinutes(10),
            function (): array {
                try {
                    return $this->fetchBankAccountDetailsMap();
                } catch (\Throwable $e) {
                    Log::warning('Moota list bank gagal', ['message' => $e->getMessage()]);
                    PaymentFlowLog::warning('moota.api.list_bank_failed', ['message' => $e->getMessage()]);

                    return [];
                }
            }
        );
    }

    /**
     * Semua `bank_id` dari GET /api/v2/bank` (untuk payload webhook; API mewajibkan kolom akun).
     *
     * @return list<string>
     */
    public function allBankAccountIdsFromApi(): array
    {
        $map = $this->bankAccountDetailsByIdMap();
        $ids = array_keys($map);
        sort($ids);

        return array_values(array_unique($ids));
    }

    /**
     * @param  list<string>  $orderedAccountIds  Urutan sama dengan .env / UI (__0, __1, …).
     * @return array<int, array{name: string, description: string}>
     */
    public function paymentLabelsForOrderedAccountIds(array $orderedAccountIds): array
    {
        if ($orderedAccountIds === []) {
            return [];
        }

        $map = $this->bankAccountDetailsByIdMap();
        $out = [];

        foreach ($orderedAccountIds as $i => $id) {
            $id = trim((string) $id);

            $row = $map[$id] ?? null;
            if ($row === null) {
                $out[$i] = [
                    'name' => __('bookings.payment.moota_account_title', ['n' => $i + 1]),
                    'description' => '',
                ];

                continue;
            }

            $name = $this->formatDisplayBankName($row);
            $holder = trim((string) ($row['atas_nama'] ?? ''));
            $account = $this->formatAccountNumberDisplay($row);

            $lines = [];
            if ($holder !== '') {
                $lines[] = __('bookings.payment.moota_account_holder_line', ['holder' => $holder]);
            }
            if ($account !== '') {
                $lines[] = __('bookings.payment.moota_account_number_line', ['account' => $account]);
            }

            $out[$i] = [
                'name' => $name,
                'description' => implode("\n", $lines),
            ];
        }

        return $out;
    }

    /**
     * @return array<string, array{label: string, bank_type: string, account_number: string, atas_nama: string}>
     */
    private function fetchBankAccountDetailsMap(): array
    {
        $token = $this->bearerToken();
        $byId = [];
        $page = 1;
        $lastPage = 1;

        do {
            $response = Http::timeout(45)->acceptJson()
                ->withToken($token)
                ->get($this->baseUrl().'/api/v2/bank', [
                    'page' => $page,
                    'per_page' => 50,
                ]);

            if ($response->status() === 401) {
                $this->forgetCachedToken();
                $token = $this->bearerToken();
                $response = Http::timeout(45)->acceptJson()
                    ->withToken($token)
                    ->get($this->baseUrl().'/api/v2/bank', [
                        'page' => $page,
                        'per_page' => 50,
                    ]);
            }

            if (! $response->successful()) {
                throw new RuntimeException('List bank Moota gagal (HTTP '.$response->status().').');
            }

            /** @var array<string, mixed> $json */
            $json = $response->json() ?? [];
            $lastPage = (int) data_get($json, 'last_page', 1);
            /** @var list<array<string, mixed>> $rows */
            $rows = data_get($json, 'data', []);
            if (! is_array($rows)) {
                $rows = [];
            }

            foreach ($rows as $row) {
                if (! is_array($row)) {
                    continue;
                }
                $bid = (string) ($row['bank_id'] ?? $row['token'] ?? '');
                if ($bid === '') {
                    continue;
                }
                $byId[$bid] = [
                    'label' => trim((string) ($row['label'] ?? '')),
                    'bank_type' => trim((string) ($row['bank_type'] ?? '')),
                    'account_number' => trim((string) ($row['account_number'] ?? '')),
                    'atas_nama' => trim((string) ($row['atas_nama'] ?? '')),
                ];
            }

            $page++;
        } while ($page <= $lastPage);

        return $byId;
    }

    /**
     * @param  array{label: string, bank_type: string, account_number: string, atas_nama: string}  $row
     */
    private function formatAccountNumberDisplay(array $row): string
    {
        return trim((string) ($row['account_number'] ?? ''));
    }

    /**
     * @param  array{label: string, bank_type: string, account_number: string, atas_nama: string}  $row
     */
    private function formatDisplayBankName(array $row): string
    {
        $label = trim((string) ($row['label'] ?? ''));
        if ($label !== '') {
            return $label;
        }

        $bt = (string) ($row['bank_type'] ?? '');

        return match ($bt) {
            'bca', 'bcaGiro', 'bcaSyariah' => 'BCA',
            'bni', 'bniBisnis', 'bniSyariah', 'bniBisnisSyariah' => 'BNI',
            'bri', 'briCms', 'briGiro', 'briSyariah', 'briSyariahCms' => 'BRI',
            'mandiriOnline', 'mandiriBisnis', 'mandiriMcm', 'mandiriMcm2' => 'Mandiri',
            'mandiriSyariah', 'mandiriSyariahBisnis', 'mandiriSyariahMcm' => 'Mandiri Syariah',
            'bsi', 'bsiGiro' => 'BSI',
            'muamalat' => 'Muamalat',
            'mayBank' => 'Maybank',
            default => $bt !== '' ? strtoupper($bt) : __('bookings.payment.moota_bank_fallback'),
        };
    }
}
