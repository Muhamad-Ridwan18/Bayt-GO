<?php

namespace App\Console\Commands;

use App\Services\Seo\GoogleSearchConsoleClient;
use App\Services\Seo\SearchConsoleInsights;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class SeoSearchConsoleReport extends Command
{
    protected $signature = 'seo:gsc-report
                            {--days=7 : Panjang periode yang dibandingkan dengan periode sebelumnya}
                            {--no-inspect : Lewati cek indexing (hemat kuota URL Inspection)}
                            {--dry-run : Tampilkan di terminal tanpa mengirim ke Discord}';

    protected $description = 'Ambil performa Search Console dan status indexing artikel, lalu laporkan ke Discord';

    public function handle(GoogleSearchConsoleClient $client, SearchConsoleInsights $insights): int
    {
        if (! $client->isConfigured()) {
            $this->error('Search Console belum dikonfigurasi. Set GSC_SITE_URL dan kredensial service account.');

            return self::FAILURE;
        }

        $days = max(1, (int) $this->option('days'));

        try {
            $report = $insights->build($days, ! $this->option('no-inspect'));
        } catch (Throwable $e) {
            $this->error('Gagal mengambil data: '.$e->getMessage());
            Log::warning('seo.gsc_report.failed', ['message' => $e->getMessage()]);

            return self::FAILURE;
        }

        $this->render($report);

        if ($this->option('dry-run')) {
            $this->newLine();
            $this->comment('dry-run — tidak dikirim ke Discord.');

            return self::SUCCESS;
        }

        return $this->send($insights->discordEmbed($report));
    }

    /**
     * @param  array<string, mixed>  $report
     */
    private function render(array $report): void
    {
        $t = $report['totals'];
        $p = $report['previous'];

        $this->info(sprintf('Periode %s s/d %s', $report['range']['start'], $report['range']['end']));
        $this->table(
            ['Metrik', 'Periode ini', 'Periode sebelumnya'],
            [
                ['Klik', $t['clicks'], $p['clicks']],
                ['Impression', $t['impressions'], $p['impressions']],
                ['CTR (%)', $t['ctr'], $p['ctr']],
                ['Posisi rata-rata', $t['position'] ?: '-', $p['position'] ?: '-'],
            ],
        );

        if ($report['opportunities'] !== []) {
            $this->newLine();
            $this->line('<info>Peluang perbaikan title & meta</info>');
            $this->table(
                ['URL', 'Posisi', 'CTR (%)', 'Impression'],
                array_map(static fn (array $r): array => [
                    $r['url'], $r['position'], $r['ctr'], $r['impressions'],
                ], $report['opportunities']),
            );
        }

        $indexing = $report['indexing'];

        if (! is_array($indexing)) {
            return;
        }

        $this->newLine();

        if ($indexing['missing'] === []) {
            $this->line(sprintf('<info>Indexing: %d artikel dicek, semuanya terindeks.</info>', $indexing['checked']));
        } else {
            $this->line(sprintf(
                '<comment>Indexing: %d dari %d artikel belum terindeks setelah %d hari.</comment>',
                count($indexing['missing']),
                $indexing['checked'],
                $indexing['grace_days'],
            ));
            $this->table(
                ['Slug', 'Umur (hari)', 'Status Google'],
                array_map(static fn (array $r): array => [
                    $r['slug'], $r['age_days'], $r['coverage'],
                ], $indexing['missing']),
            );
        }

        if ($indexing['errors'] > 0) {
            $this->warn(sprintf('%d URL gagal diinspeksi (kemungkinan kuota harian habis).', $indexing['errors']));
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function send(array $payload): int
    {
        $webhook = trim((string) config('services.google_search_console.discord_webhook_url', ''));

        if ($webhook === '') {
            $this->warn('SEO_DISCORD_WEBHOOK_URL belum diset — laporan tidak dikirim.');

            return self::SUCCESS;
        }

        $response = Http::timeout(20)->retry(2, 500, throw: false)->post($webhook, $payload);

        if ($response->failed()) {
            $this->error('Gagal kirim ke Discord: HTTP '.$response->status());
            Log::warning('seo.gsc_report.discord_failed', ['status' => $response->status()]);

            return self::FAILURE;
        }

        $this->newLine();
        $this->info('Laporan terkirim ke Discord.');

        return self::SUCCESS;
    }
}
