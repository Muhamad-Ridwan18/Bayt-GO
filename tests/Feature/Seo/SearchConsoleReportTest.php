<?php

namespace Tests\Feature\Seo;

use App\Models\Article;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SearchConsoleReportTest extends TestCase
{
    use RefreshDatabase;

    private const WEBHOOK = 'https://discord.com/api/webhooks/1/uji';

    /** @var array<string, mixed> */
    private array $inspection = [];

    protected function setUp(): void
    {
        parent::setUp();

        // Token di-cache antar run; tanpa ini test kedua memakai token test pertama.
        Cache::forget('seo:gsc:access_token');

        config([
            'services.google_search_console.site_url' => 'sc-domain:baytgo.id',
            'services.google_search_console.credentials_json' => base64_encode($this->serviceAccountJson()),
            'services.google_search_console.credentials_path' => '',
            'services.google_search_console.discord_webhook_url' => self::WEBHOOK,
            'services.google_search_console.indexing_grace_days' => 14,
            'services.google_search_console.inspect_limit' => 20,
            'services.google_search_console.opportunity_min_impressions' => 30,
            'services.google_search_console.opportunity_max_ctr' => 2.0,
        ]);
    }

    public function test_it_reports_performance_and_sends_the_summary_to_discord(): void
    {
        $this->fakeGoogle();

        $this->artisan('seo:gsc-report', ['--no-inspect' => true])->assertSuccessful();

        Http::assertSent(function (Request $request): bool {
            if (! str_starts_with($request->url(), self::WEBHOOK)) {
                return false;
            }

            $field = $request->data()['embeds'][0]['fields'][0];

            // Periode ini 120 klik vs 80 sebelumnya = +50%.
            return str_contains($field['value'], 'Klik **120** (+50%)')
                && str_contains($field['value'], 'Impression **4000**');
        });
    }

    public function test_pages_that_rank_but_are_rarely_clicked_become_opportunities(): void
    {
        $this->fakeGoogle();

        $this->artisan('seo:gsc-report', ['--no-inspect' => true])->assertSuccessful();

        Http::assertSent(function (Request $request): bool {
            if (! str_starts_with($request->url(), self::WEBHOOK)) {
                return false;
            }

            $text = (string) json_encode($request->data(), JSON_UNESCAPED_SLASHES);

            // Posisi 7,4 dengan CTR 0,5% -> layak diperbaiki title/meta-nya.
            $this->assertStringContainsString('/artikel/peluang-ctr', $text);
            // Posisi 2,1 sudah bagus dan tidak boleh diusik.
            $this->assertStringNotContainsString('/artikel/sudah-juara', $text);
            // Posisi 42 terlalu jauh; itu masalah konten, bukan title.
            $this->assertStringNotContainsString('/artikel/terlalu-dalam', $text);
            // Hanya 5 impression: terlalu sedikit untuk disimpulkan.
            $this->assertStringNotContainsString('/artikel/sepi', $text);

            return true;
        });
    }

    public function test_published_articles_still_missing_from_the_index_are_flagged(): void
    {
        $terindeks = $this->publishArticle('sudah-terindeks', now()->subDays(30));
        $hilang = $this->publishArticle('belum-terindeks', now()->subDays(40));
        $this->publishArticle('baru-terbit', now()->subDays(2));

        $this->inspection = [
            $terindeks->slug => ['verdict' => 'PASS', 'coverageState' => 'Submitted and indexed'],
            $hilang->slug => ['verdict' => 'NEUTRAL', 'coverageState' => 'Crawled - currently not indexed'],
        ];

        $this->fakeGoogle();

        $this->artisan('seo:gsc-report')->assertSuccessful();

        Http::assertSent(function (Request $request): bool {
            if (! str_starts_with($request->url(), self::WEBHOOK)) {
                return false;
            }

            $text = (string) json_encode($request->data());

            $this->assertStringContainsString('belum-terindeks', $text);
            $this->assertStringContainsString('Crawled - currently not indexed', $text);
            $this->assertStringNotContainsString('sudah-terindeks', $text);

            return true;
        });

        // Artikel yang baru 2 hari terbit belum lewat tenggang, jadi tidak boleh diinspeksi.
        Http::assertNotSent(fn (Request $r): bool => str_contains($r->url(), 'urlInspection')
            && str_contains((string) json_encode($r->data()), 'baru-terbit'));
    }

    public function test_it_stops_early_when_credentials_are_missing(): void
    {
        config(['services.google_search_console.credentials_json' => '']);
        Http::fake();

        $this->artisan('seo:gsc-report')->assertFailed();

        Http::assertNothingSent();
    }

    public function test_a_failing_inspection_does_not_abort_the_whole_report(): void
    {
        $this->publishArticle('inspeksi-error', now()->subDays(20));

        Http::fake(function (Request $request) {
            if (str_contains($request->url(), 'oauth2.googleapis.com')) {
                return Http::response(['access_token' => 'token-uji', 'expires_in' => 3600]);
            }

            if (str_contains($request->url(), 'searchAnalytics')) {
                return Http::response(['rows' => []]);
            }

            if (str_contains($request->url(), 'urlInspection')) {
                return Http::response(['error' => ['message' => 'Quota exceeded']], 429);
            }

            return Http::response('', 204);
        });

        $this->artisan('seo:gsc-report')->assertSuccessful();

        Http::assertSent(fn (Request $r): bool => str_starts_with($r->url(), self::WEBHOOK));
    }

    private function fakeGoogle(): void
    {
        Http::fake(function (Request $request) {
            if (str_contains($request->url(), 'oauth2.googleapis.com')) {
                return Http::response(['access_token' => 'token-uji', 'expires_in' => 3600]);
            }

            if (str_contains($request->url(), 'searchAnalytics')) {
                return Http::response(['rows' => $this->analyticsRows($request->data())]);
            }

            if (str_contains($request->url(), 'urlInspection')) {
                $url = (string) ($request->data()['inspectionUrl'] ?? '');
                $slug = basename(parse_url($url, PHP_URL_PATH) ?: '');

                $status = $this->inspection[$slug] ?? ['verdict' => 'PASS', 'coverageState' => 'Submitted and indexed'];

                return Http::response(['inspectionResult' => ['indexStatusResult' => $status]]);
            }

            return Http::response('', 204);
        });
    }

    /**
     * @param  array<string, mixed>  $body
     * @return list<array<string, mixed>>
     */
    private function analyticsRows(array $body): array
    {
        $dimensions = $body['dimensions'] ?? [];

        if ($dimensions === []) {
            // Periode sebelumnya selalu punya startDate lebih awal.
            $isCurrent = ($body['startDate'] ?? '') > now()->subDays(11)->toDateString();

            return [$isCurrent
                ? ['clicks' => 120, 'impressions' => 4000, 'ctr' => 0.03, 'position' => 12.4]
                : ['clicks' => 80, 'impressions' => 3000, 'ctr' => 0.026, 'position' => 14.1]];
        }

        if ($dimensions === ['query']) {
            return [
                ['keys' => ['muthowif jakarta'], 'clicks' => 40, 'impressions' => 900, 'ctr' => 0.044, 'position' => 8.2],
            ];
        }

        return [
            // Layak: sudah muncul di halaman satu tapi hampir tidak diklik.
            ['keys' => ['https://baytgo.id/artikel/peluang-ctr'], 'clicks' => 3, 'impressions' => 600, 'ctr' => 0.005, 'position' => 7.4],
            // Sudah juara, jangan disentuh.
            ['keys' => ['https://baytgo.id/artikel/sudah-juara'], 'clicks' => 90, 'impressions' => 500, 'ctr' => 0.18, 'position' => 2.1],
            // Terlalu dalam: itu masalah kedalaman konten, bukan title.
            ['keys' => ['https://baytgo.id/artikel/terlalu-dalam'], 'clicks' => 0, 'impressions' => 400, 'ctr' => 0.0, 'position' => 42.0],
            // Impression terlalu sedikit untuk disimpulkan.
            ['keys' => ['https://baytgo.id/artikel/sepi'], 'clicks' => 0, 'impressions' => 5, 'ctr' => 0.0, 'position' => 9.0],
        ];
    }

    private function publishArticle(string $slug, \DateTimeInterface $publishedAt): Article
    {
        return Article::query()->create([
            'slug' => $slug,
            'is_published' => true,
            'is_featured' => false,
            'sort_order' => 0,
            'published_at' => $publishedAt,
            'translations' => [
                'id' => [
                    'title' => 'Judul '.$slug,
                    'excerpt' => 'Ringkasan',
                    'category' => 'Umroh',
                    'author' => 'BaytGo',
                    'body' => '<p>Isi artikel.</p>',
                ],
            ],
        ]);
    }

    private static ?string $cachedJson = null;

    /**
     * Kunci dibuat saat test berjalan, bukan disimpan di repo — private key PEM
     * yang ter-commit akan memicu secret scanning saat push.
     */
    private function serviceAccountJson(): string
    {
        if (self::$cachedJson !== null) {
            return self::$cachedJson;
        }

        // Windows/Laragon tidak menyertakan openssl.cnf, dan tanpa itu
        // openssl_pkey_new() gagal. Config minimal ini cukup untuk RSA.
        $config = tempnam(sys_get_temp_dir(), 'gsc').'.cnf';
        file_put_contents($config, "[req]\ndistinguished_name = dn\n\n[dn]\n");

        $options = [
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
            'config' => $config,
        ];

        $key = openssl_pkey_new($options);

        if ($key === false || ! openssl_pkey_export($key, $privateKey, null, $options)) {
            @unlink($config);
            $this->markTestSkipped('OpenSSL tidak bisa membuat kunci RSA di lingkungan ini.');
        }

        @unlink($config);

        return self::$cachedJson = (string) json_encode([
            'client_email' => 'gsc@baytgo.iam.gserviceaccount.com',
            'private_key' => $privateKey,
        ]);
    }
}
