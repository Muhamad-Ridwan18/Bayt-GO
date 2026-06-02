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
                            {--scope=config : Rekening: config (MOOTA_BANK_ACCOUNT_ID) atau all (semua rekening dari API /api/v2/bank)}
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

        $dryRun = (bool) $this->option('dry-run');

        $scope = (string) $this->option('scope');
        /** @var list<string> $bankAccountIds */
        $bankAccountIds = [];
        if ($scope === 'config') {
            $bankAccountIds = $client->bankAccountIds();
            if ($bankAccountIds === []) {
                $this->error('scope=config membutuhkan MOOTA_BANK_ACCOUNT_ID di .env (pisahkan koma/spasi jika lebih dari satu).');

                return self::FAILURE;
            }
        } elseif ($scope === 'all') {
            $bankAccountIds = $client->allBankAccountIdsFromApi();
            if ($bankAccountIds === []) {
                $this->error('scope=all: tidak ada rekening dari API Moota (GET /api/v2/bank). Tambahkan rekening di dashboard Moota, atau gunakan --scope=config dengan MOOTA_BANK_ACCOUNT_ID.');

                return self::FAILURE;
            }
        } else {
            $this->error('Opsi --scope harus config atau all.');

            return self::FAILURE;
        }

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

        $this->line('URL webhook: <fg=cyan>'.$targetUrl.'</>');
        $this->line('Kinds: <fg=cyan>'.$kinds.'</>');
        $this->line('Rekening (satu webhook per ID): <fg=cyan>'.implode(', ', $bankAccountIds).'</>'.($scope === 'all' ? ' <fg=gray>(dari API)</>' : ' <fg=gray>(MOOTA_BANK_ACCOUNT_ID)</>'));
        $this->line('Filter kode unik: <fg=cyan>'.$start.' … '.$end.'</>');

        if ($dryRun) {
            $this->newLine();
            $this->line('<fg=yellow>[dry-run]</> Satu permintaan POST per rekening (bank_account_id tunggal):');
            foreach ($bankAccountIds as $bankId) {
                $payload = [
                    'url' => $targetUrl,
                    'bank_account_id' => $bankId,
                    'kinds' => $kinds,
                    'secret_token' => $secret,
                    'start_unique_code' => $start,
                    'end_unique_code' => $end,
                ];
                $this->line(json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
                $this->newLine();
            }

            return self::SUCCESS;
        }

        $created = 0;
        $skipped = 0;

        foreach ($bankAccountIds as $bankId) {
            $payload = [
                'url' => $targetUrl,
                'bank_account_id' => $bankId,
                'kinds' => $kinds,
                'secret_token' => $secret,
                'start_unique_code' => $start,
                'end_unique_code' => $end,
            ];

            $existing = $this->webhooksForUrlAndBankAccount($client, $targetUrl, $bankId);
            if ($existing !== []) {
                $row = $existing[0];
                $wid = is_string($row['webhook_id'] ?? null) ? $row['webhook_id'] : (string) ($row['token'] ?? $row['id'] ?? '?');
                $this->warn('Lewati '.$bankId.': sudah ada webhook untuk URL + akun ini (webhook_id: '.$wid.').');
                $skipped++;

                continue;
            }

            try {
                $response = $client->createIntegrationWebhook($payload);
                $this->info('Terdaftar: '.$bankId);
                $this->line(json_encode($response, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
                $created++;
            } catch (RuntimeException $e) {
                $this->error($bankId.': '.$e->getMessage());

                return self::FAILURE;
            }
        }

        if ($created === 0 && $skipped > 0) {
            $this->newLine();
            $this->info('Tidak ada webhook baru; semua rekening sudah punya webhook untuk URL ini.');

            return self::SUCCESS;
        }

        $this->newLine();
        $this->line('Pastikan <fg=yellow>config/services.php → moota.webhook_ips</> mencakup IP pengirim Moota, dan (jika pakai HTTPS) sertifikat valid.');

        return self::SUCCESS;
    }

    /**
     * Webhook yang sama URL **dan** akun bank (Moota tidak menerima beberapa ID sekaligus dalam satu POST).
     *
     * @return list<array<string, mixed>>
     */
    private function webhooksForUrlAndBankAccount(MootaApiClient $client, string $targetUrl, string $bankAccountId): array
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
                $bid = isset($row['bank_account_id']) && is_string($row['bank_account_id']) ? trim($row['bank_account_id']) : '';
                if ($u === $targetUrl && $bid === trim($bankAccountId)) {
                    $matches[] = $row;
                }
            }
            $page++;
        } while ($page <= $lastPage);

        return $matches;
    }
}
