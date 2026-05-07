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
}
