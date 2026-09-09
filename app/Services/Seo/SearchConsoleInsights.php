<?php

namespace App\Services\Seo;

use App\Models\Article;
use App\Support\ArticleUrl;
use Illuminate\Support\Carbon;
use Throwable;

/**
 * Laporan pemantauan Search Console — hanya membaca, tidak mengubah konten.
 *
 * Sengaja tidak memutuskan apa pun secara otomatis: pada situs baru, ranking
 * belum stabil selama 3-6 bulan pertama, jadi menulis ulang artikel berdasarkan
 * data sedini ini justru mereset riwayat halaman.
 */
final class SearchConsoleInsights
{
    /** Data GSC belum final untuk 2-3 hari terakhir. */
    private const DATA_LAG_DAYS = 3;

    public function __construct(private readonly GoogleSearchConsoleClient $client) {}

    /**
     * @return array<string, mixed>
     */
    public function build(int $days = 7, bool $inspectIndexing = true): array
    {
        $end = Carbon::today()->subDays(self::DATA_LAG_DAYS);
        $start = $end->copy()->subDays($days - 1);
        $prevEnd = $start->copy()->subDay();
        $prevStart = $prevEnd->copy()->subDays($days - 1);

        $fmt = static fn (Carbon $d): string => $d->toDateString();

        $totals = $this->totals($fmt($start), $fmt($end));
        $previous = $this->totals($fmt($prevStart), $fmt($prevEnd));

        $pages = $this->client->searchAnalytics($fmt($start), $fmt($end), ['page'], 200);
        $queries = $this->client->searchAnalytics($fmt($start), $fmt($end), ['query'], 25);

        return [
            'range' => ['start' => $fmt($start), 'end' => $fmt($end), 'days' => $days],
            'totals' => $totals,
            'previous' => $previous,
            'top_pages' => $this->topPages($pages),
            'top_queries' => $this->topQueries($queries),
            'opportunities' => $this->opportunities($pages),
            'indexing' => $inspectIndexing ? $this->indexing() : null,
        ];
    }

    /**
     * @return array{clicks: int, impressions: int, ctr: float, position: float}
     */
    private function totals(string $start, string $end): array
    {
        $row = $this->client->searchAnalytics($start, $end)[0] ?? null;

        return [
            'clicks' => (int) round($row['clicks'] ?? 0),
            'impressions' => (int) round($row['impressions'] ?? 0),
            'ctr' => round(($row['ctr'] ?? 0) * 100, 2),
            'position' => round($row['position'] ?? 0, 1),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $pages
     * @return list<array<string, mixed>>
     */
    private function topPages(array $pages): array
    {
        usort($pages, static fn (array $a, array $b): int => $b['impressions'] <=> $a['impressions']);

        return array_values(array_map(static fn (array $row): array => [
            'url' => $row['keys'][0] ?? '-',
            'clicks' => (int) round($row['clicks']),
            'impressions' => (int) round($row['impressions']),
            'position' => round($row['position'], 1),
        ], array_slice($pages, 0, 5)));
    }

    /**
     * @param  list<array<string, mixed>>  $queries
     * @return list<array<string, mixed>>
     */
    private function topQueries(array $queries): array
    {
        usort($queries, static fn (array $a, array $b): int => $b['impressions'] <=> $a['impressions']);

        return array_values(array_map(static fn (array $row): array => [
            'query' => $row['keys'][0] ?? '-',
            'clicks' => (int) round($row['clicks']),
            'impressions' => (int) round($row['impressions']),
            'position' => round($row['position'], 1),
        ], array_slice($queries, 0, 5)));
    }

    /**
     * Halaman yang sudah diperlihatkan Google tapi jarang diklik.
     *
     * Ini kelompok paling berharga: peringkatnya sudah ada, jadi masalahnya di
     * title/meta, bukan di isi. Perbaikannya murah dan tidak menyentuh badan
     * artikel sehingga riwayat halaman tidak ikut direset.
     *
     * @param  list<array<string, mixed>>  $pages
     * @return list<array<string, mixed>>
     */
    private function opportunities(array $pages): array
    {
        $config = (array) config('services.google_search_console', []);
        $minImpressions = (int) ($config['opportunity_min_impressions'] ?? 30);
        $maxCtr = (float) ($config['opportunity_max_ctr'] ?? 2.0);

        $candidates = array_filter($pages, static function (array $row) use ($minImpressions, $maxCtr): bool {
            return $row['impressions'] >= $minImpressions
                && $row['position'] >= 4
                && $row['position'] <= 15
                && ($row['ctr'] * 100) < $maxCtr;
        });

        usort($candidates, static fn (array $a, array $b): int => $b['impressions'] <=> $a['impressions']);

        return array_values(array_map(static fn (array $row): array => [
            'url' => $row['keys'][0] ?? '-',
            'impressions' => (int) round($row['impressions']),
            'clicks' => (int) round($row['clicks']),
            'ctr' => round($row['ctr'] * 100, 2),
            'position' => round($row['position'], 1),
        ], array_slice($candidates, 0, 5)));
    }

    /**
     * Artikel terbit yang sudah melewati masa tenggang tapi belum terindeks.
     *
     * Nol impression paling sering berarti belum terindeks, bukan kontennya
     * lemah — jadi ini harus dicek sebelum menyalahkan kualitas tulisan.
     *
     * @return array<string, mixed>
     */
    private function indexing(): array
    {
        $config = (array) config('services.google_search_console', []);
        $graceDays = (int) ($config['indexing_grace_days'] ?? 14);
        $limit = (int) ($config['inspect_limit'] ?? 20);

        $articles = Article::query()
            ->published()
            ->where('published_at', '<=', Carbon::now()->subDays($graceDays))
            ->orderByDesc('published_at')
            ->limit($limit)
            ->get(['id', 'slug', 'published_at']);

        $missing = [];
        $checked = 0;
        $errors = 0;

        foreach ($articles as $article) {
            $url = ArticleUrl::show($article);

            try {
                $status = $this->client->inspect($url);
                $checked++;

                if (! $status['indexed']) {
                    $missing[] = [
                        'slug' => $article->slug,
                        'url' => $url,
                        'age_days' => (int) $article->published_at?->diffInDays(Carbon::now()),
                        'coverage' => $status['coverage'],
                    ];
                }
            } catch (Throwable $e) {
                // Kuota inspeksi habis atau URL tunggal bermasalah tidak boleh
                // menggagalkan seluruh laporan.
                $errors++;
            }
        }

        return [
            'checked' => $checked,
            'errors' => $errors,
            'grace_days' => $graceDays,
            'missing' => $missing,
        ];
    }

    /**
     * @param  array<string, mixed>  $report
     * @return array<string, mixed>
     */
    public function discordEmbed(array $report): array
    {
        $totals = $report['totals'];
        $previous = $report['previous'];

        $delta = static function (int|float $now, int|float $before): string {
            if ($before <= 0) {
                return $now > 0 ? ' (baru)' : '';
            }

            $pct = round((($now - $before) / $before) * 100);

            return sprintf(' (%s%d%%)', $pct >= 0 ? '+' : '', $pct);
        };

        $fields = [[
            'name' => 'Ringkasan '.$report['range']['days'].' hari',
            'value' => sprintf(
                "Klik **%d**%s\nImpression **%d**%s\nCTR **%s%%** · Posisi rata-rata **%s**",
                $totals['clicks'],
                $delta($totals['clicks'], $previous['clicks']),
                $totals['impressions'],
                $delta($totals['impressions'], $previous['impressions']),
                $totals['ctr'],
                $totals['position'] > 0 ? $totals['position'] : '-',
            ),
            'inline' => false,
        ]];

        if ($report['top_queries'] !== []) {
            $fields[] = [
                'name' => 'Kueri teratas',
                'value' => $this->lines($report['top_queries'], static fn (array $r): string => sprintf(
                    '`%s` — %d impr, pos %s',
                    mb_substr((string) $r['query'], 0, 48),
                    $r['impressions'],
                    $r['position'],
                )),
                'inline' => false,
            ];
        }

        if ($report['opportunities'] !== []) {
            $fields[] = [
                'name' => 'Peluang: sudah muncul, jarang diklik',
                'value' => "Perbaiki title & meta saja, jangan tulis ulang isinya.\n".$this->lines(
                    $report['opportunities'],
                    fn (array $r): string => sprintf(
                        '%s — pos %s, CTR %s%%, %d impr',
                        $this->shorten((string) $r['url']),
                        $r['position'],
                        $r['ctr'],
                        $r['impressions'],
                    ),
                ),
                'inline' => false,
            ];
        }

        $indexing = $report['indexing'];

        if (is_array($indexing)) {
            $missing = $indexing['missing'];
            $fields[] = [
                'name' => 'Indexing',
                'value' => $missing === []
                    ? sprintf('Semua %d artikel yang dicek sudah terindeks.', $indexing['checked'])
                    : sprintf(
                        "%d dari %d artikel belum terindeks setelah %d hari:\n%s",
                        count($missing),
                        $indexing['checked'],
                        $indexing['grace_days'],
                        $this->lines($missing, static fn (array $r): string => sprintf(
                            '`%s` — %d hari, %s',
                            mb_substr((string) $r['slug'], 0, 44),
                            $r['age_days'],
                            $r['coverage'],
                        )),
                    ),
                'inline' => false,
            ];
        }

        return [
            'embeds' => [[
                'title' => 'Laporan SEO BaytGo',
                'url' => 'https://search.google.com/search-console',
                'description' => sprintf('Periode %s s/d %s', $report['range']['start'], $report['range']['end']),
                'color' => $totals['impressions'] > 0 ? 3447003 : 9807270,
                'fields' => $fields,
                'footer' => ['text' => 'Google Search Console · hanya pemantauan'],
                'timestamp' => Carbon::now()->toIso8601String(),
            ]],
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function lines(array $rows, callable $formatter): string
    {
        $text = implode("\n", array_map(static fn (array $r): string => '• '.$formatter($r), $rows));

        // Discord memotong field di 1024 karakter.
        return mb_substr($text, 0, 1000);
    }

    private function shorten(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH) ?: $url;

        return mb_strlen($path) > 46 ? mb_substr($path, 0, 45).'…' : $path;
    }
}
