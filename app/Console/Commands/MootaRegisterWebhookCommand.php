<?php

namespace App\Console\Commands;

use App\Services\Moota\MootaApiClient;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Mendaftarkan URL webhook mutasi ke Moota melalui API (POST /api/v2/integration/webhook).
 *
 * @see https://moota.gitbook.io/technical-docs/untitled
 */
class MootaRegisterWebhookCommand extends Command
{
    protected $signature = 'moota:register-webhook
                            {--url= : URL penuh endpoint (default: route webhooks.moota dari APP_URL)}
                            {--kinds=credit : Filter mutasi: credit|debit|both}
                            {--scope=all : Rekening: all (kosongkan bank_account_id) atau config (pakai MOOTA_BANK_ACCOUNT_ID)}
                            {--generate-secret : Buat secret_token acak; cetak baris MOOTA_WEBHOOK_SIGNING_SECRET untuk .env}
                            {--dry-run : Hanya tampilkan payload, tidak memanggil API}';

    protected $description = 'Daftarkan webhook mutasi Moota ke URL aplikasi Anda (otomatis via API).';

    public function handle(MootaApiClient $client): int
    {
        if (! $client->isAuthConfigured()) {
            $this->error('Isi MOOTA_API_EMAIL dan MOOTA_API_PASSWORD di .env terlebih dahulu.');

            return self::FAILURE;
        }

        $targetUrl = $this->option('url');
        $targetUrl = is_string($targetUrl) && $targetUrl !== ''
            ? $targetUrl
            : route('webhooks.moota', absolute: true);

        if (! str_starts_with($targetUrl, 'https://')) {
            $this->warn('URL webhook tidak memakai HTTPS. Moota hanya bisa mengirim dari internet; pastikan domin publik + TLS.');
        }

        if (Str::contains($targetUrl, ['localhost', '127.0.0.1', '.test', '.local'])) {
            $this->warn('URL sepertinya tidak dapat dijangkau dari internet (localhost/d.test). Gunakan tunnel (ngrok, dll.) dan --url=https://....');
        }

        $kinds = (string) $this->option('kinds');
        if (! in_array($kinds, ['credit', 'debit', 'both'], true)) {
            $this->error('Opsi --kinds harus credit, debit, atau both.');

            return self::FAILURE;
        }

        $scope = (string) $this->option('scope');
        $bankAccountIdField = '';
        if ($scope === 'config') {
            $ids = $client->bankAccountIds();
            if ($ids === []) {
                $this->error('scope=config membutuhkan MOOTA_BANK_ACCOUNT_ID di .env.');

                return self::FAILURE;
            }
            $bankAccountIdField = implode(',', $ids);
        } elseif ($scope !== 'all') {
            $this->error('Opsi --scope harus all atau config.');

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');

        $secret = trim((string) config('services.moota.signing_secret', ''));
        if (! $dryRun && $secret === '') {
            if ($this->option('generate-secret')) {
                $secret = bin2hex(random_bytes(32));
                $this->newLine();
                $this->line('Tambahkan ke <fg=cyan>.env</> (verifikasi signature webhook membutuhkan ini):');
                $this->line('MOOTA_WEBHOOK_SIGNING_SECRET='.$secret);
                $this->newLine();
            } else {
                $this->error('MOOTA_WEBHOOK_SIGNING_SECRET kosong. Isi di .env agar sama dengan secret_token di Moota, atau jalankan dengan --generate-secret.');

                return self::FAILURE;
            }
        }

        if ($dryRun && $secret === '') {
            $secret = '__ISI_MOOTA_WEBHOOK_SIGNING_SECRET__';
        }

        $start = (int) config('services.moota.webhook_unique_start', 0);
        $end = (int) config('services.moota.webhook_unique_end', 999);

        $payload = [
            'url' => $targetUrl,
            'bank_account_id' => $bankAccountIdField,
            'kinds' => $kinds,
            'secret_token' => $secret,
            'start_unique_code' => $start,
            'end_unique_code' => $end,
        ];

        $this->line('URL webhook: <fg=cyan>'.$targetUrl.'</>');
        $this->line('Kinds: <fg=cyan>'.$kinds.'</>');
        $this->line('bank_account_id: <fg=cyan>'.($bankAccountIdField === '' ? '(semua rekening di akun Moota)' : $bankAccountIdField).'</>');
        $this->line('Filter kode unik: <fg=cyan>'.$start.' … '.$end.'</>');

        if ($this->option('dry-run')) {
            $this->newLine();
            $this->line('<fg=yellow>[dry-run]</> Payload: '.json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

            return self::SUCCESS;
        }

        try {
            $duplicates = $this->webhooksMatchingUrl($client, $targetUrl);
            if ($duplicates !== []) {
                $this->warn('Sudah ada webhook Moota dengan URL yang sama:');
                foreach ($duplicates as $row) {
                    $wid = is_string($row['webhook_id'] ?? null) ? $row['webhook_id'] : (string) ($row['token'] ?? $row['id'] ?? '?');
                    $this->line(' • webhook_id: '.$wid.' | kinds: '.(string) ($row['kinds'] ?? '?'));
                }
                $this->newLine();
                $this->info('Tidak membuat duplikasi. Hapus webhook lama di dashboard Moota bila perlu, lalu jalankan lagi.');

                return self::SUCCESS;
            }

            $response = $client->createIntegrationWebhook($payload);
            $this->newLine();
            $this->info('Webhook berhasil didaftarkan di Moota.');
            $this->line(json_encode($response, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
        } catch (RuntimeException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->newLine();
        $this->line('Pastikan <fg=yellow>MOOTA_WEBHOOK_IPS</> mencakup IP pengirim Moota, dan (jika pakai HTTPS) sertifikat valid.');

        return self::SUCCESS;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function webhooksMatchingUrl(MootaApiClient $client, string $targetUrl): array
    {
        $matches = [];
        $page = 1;
        $lastPage = 1;

        do {
            /** @var array<string, mixed> $json */
            $json = $client->listIntegrationWebhooks([
                'url' => $targetUrl,
                'page' => $page,
                'per_page' => 50,
            ]);
            $lastPage = max(1, (int) data_get($json, 'last_page', 1));
            /** @var mixed $data */
            $data = data_get($json, 'data', []);
            if (! is_array($data)) {
                break;
            }
            foreach ($data as $row) {
                if (! is_array($row)) {
                    continue;
                }
                $u = isset($row['url']) && is_string($row['url']) ? $row['url'] : '';
                if ($u === $targetUrl) {
                    $matches[] = $row;
                }
            }
            $page++;
        } while ($page <= $lastPage);

        return $matches;
    }
}
