<?php

namespace Tests\Feature\Api;

use App\Models\Article;
use App\Services\Articles\EditorialQa;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArticleEditorialQaTest extends TestCase
{
    use RefreshDatabase;

    private const TOKEN = 'testing-articles-token';

    /**
     * Artikel yang memenuhi seluruh kriteria: subjudul spesifik, panjang, pembuka
     * langsung, excerpt ideal, ada sumber, dan keyword dipakai wajar.
     */
    private function strongBody(): string
    {
        // Paragraf sengaja tidak menyebut keyword utama, supaya jumlah kemunculannya
        // tetap wajar seperti artikel sungguhan dan tidak terdeteksi keyword stuffing.
        $paragraph = 'Pendamping ibadah yang berpengalaman membantu jamaah memahami rukun umroh, '
            .'mengatur ritme ibadah harian, dan menjaga stamina selama berada di Tanah Suci. '
            .'Pilihan yang tepat memberi rasa aman bagi jamaah pemula maupun lansia. ';

        return "Memilih muthowif menentukan kenyamanan ibadah Anda sejak hari pertama.\n\n"
            ."## Menakar pengalaman lapangan muthowif\n\n"
            .str_repeat($paragraph, 8)."\n\n"
            ."## Menyesuaikan gaya pendampingan dengan kondisi jamaah\n\n"
            .str_repeat($paragraph, 8)."\n\n"
            ."## Tanda muthowif yang layak dipercaya\n\n"
            .str_repeat($paragraph, 8)."\n\n"
            ."## Sumber\n\n"
            .'- [Kementerian Agama](https://kemenag.go.id/panduan)'."\n"
            .'- [Panduan resmi umroh](https://haji.kemenag.go.id/v5/informasi)'."\n";
    }

    /**
     * Excerpt di luar rentang ideal: cukup untuk draft, belum cukup untuk
     * terbit otomatis.
     *
     * @return array<string, mixed>
     */
    private function middlingOverrides(): array
    {
        return [
            'excerpt' => 'Panduan singkat memilih pendamping ibadah yang tepat untuk jamaah umroh pemula.',
        ];
    }

    private function strongExcerpt(): string
    {
        return 'Panduan memilih muthowif yang tepat untuk jamaah umroh pemula, '
            .'mulai dari menakar pengalaman lapangan hingga tanda pendamping yang layak dipercaya.';
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Cara Memilih Muthowif untuk Jamaah Pemula',
            'excerpt' => $this->strongExcerpt(),
            'category' => 'Muthowif',
            'author' => 'BaytGo',
            'body_md' => $this->strongBody(),
            'keywords' => 'muthowif',
            'auto_publish' => true,
        ], $overrides);
    }

    public function test_evaluate_endpoint_scores_without_saving_the_article(): void
    {
        $response = $this->withToken(self::TOKEN)
            ->postJson('/api/articles/evaluate', $this->payload())
            ->assertOk()
            ->assertJsonPath('qa.decision', EditorialQa::DECISION_PUBLISH);

        $this->assertGreaterThanOrEqual(85, $response->json('qa.score'));
        $this->assertSame(0, Article::query()->count());
    }

    public function test_strong_article_is_published_automatically(): void
    {
        $this->withToken(self::TOKEN)
            ->postJson('/api/articles', $this->payload())
            ->assertCreated()
            ->assertJsonPath('qa.decision', EditorialQa::DECISION_PUBLISH)
            ->assertJsonPath('data.is_published', true);

        $article = Article::query()->firstOrFail();
        $this->assertTrue($article->is_published);
        $this->assertNotNull($article->published_at);
    }

    public function test_middling_article_is_saved_as_draft(): void
    {
        $response = $this->withToken(self::TOKEN)
            ->postJson('/api/articles', $this->payload($this->middlingOverrides()))
            ->assertCreated()
            ->assertJsonPath('qa.decision', EditorialQa::DECISION_DRAFT)
            ->assertJsonPath('data.is_published', false);

        $score = $response->json('qa.score');
        $this->assertGreaterThanOrEqual(75, $score);
        $this->assertLessThan(85, $score);
    }

    public function test_thin_article_is_blocked_and_kept_unpublished(): void
    {
        $this->withToken(self::TOKEN)
            ->postJson('/api/articles', $this->payload([
                'body_md' => 'Artikel pendek tanpa struktur apa pun.',
                'excerpt' => '',
            ]))
            ->assertCreated()
            ->assertJsonPath('qa.decision', EditorialQa::DECISION_BLOCKED)
            ->assertJsonPath('qa.hard_fail', true)
            ->assertJsonPath('data.is_published', false);
    }

    public function test_risky_claim_blocks_publication_regardless_of_quality(): void
    {
        $response = $this->withToken(self::TOKEN)
            ->postJson('/api/articles', $this->payload([
                'body_md' => $this->strongBody()."\n\nKami jamin 100% jamaah pasti berangkat.\n",
            ]))
            ->assertCreated()
            ->assertJsonPath('qa.hard_fail', true)
            ->assertJsonPath('qa.decision', EditorialQa::DECISION_BLOCKED)
            ->assertJsonPath('data.is_published', false);

        $this->assertNotEmpty($response->json('qa.hard_fail_reasons'));
    }

    public function test_qa_reports_whether_the_english_url_will_exist(): void
    {
        $this->withToken(self::TOKEN)
            ->postJson('/api/articles/evaluate', $this->payload())
            ->assertOk()
            ->assertJsonPath('qa.metrics.english_ready', false);

        $this->withToken(self::TOKEN)
            ->postJson('/api/articles/evaluate', $this->payload([
                'title_en' => 'How to Choose a Muthowif',
                'body_md_en' => $this->strongBody(),
            ]))
            ->assertOk()
            ->assertJsonPath('qa.metrics.english_ready', true);
    }

    private function publishArticle(string $slug, string $title): Article
    {
        return Article::query()->create([
            'slug' => $slug,
            'is_published' => true,
            'is_featured' => false,
            'sort_order' => 0,
            'published_at' => now()->subDay(),
            'translations' => [
                'id' => [
                    'title' => $title,
                    'excerpt' => '',
                    'category' => 'Umroh',
                    'author' => 'BaytGo',
                    'body' => '<p>Isi artikel lama.</p>',
                    'body_json' => '',
                    'body_md' => '',
                ],
            ],
        ]);
    }

    public function test_internal_links_to_real_pages_earn_full_marks(): void
    {
        $this->publishArticle('panduan-umroh-pemula', 'Panduan Umroh Pemula');

        $body = $this->strongBody()
            ."\n\nBaca juga [panduan umroh pemula](/artikel/panduan-umroh-pemula) "
            ."dan telusuri [direktori muthowif](/layanan).\n";

        $response = $this->withToken(self::TOKEN)
            ->postJson('/api/articles/evaluate', $this->payload(['body_md' => $body]))
            ->assertOk()
            ->assertJsonPath('qa.metrics.internal_links', 2)
            ->assertJsonPath('qa.metrics.internal_article_links', 1)
            ->assertJsonPath('qa.metrics.internal_commercial_links', 1)
            ->assertJsonPath('qa.metrics.broken_internal_links', []);

        // Artikel identik tanpa tautan kehilangan tepat bobot kriteria ini.
        $withoutLinks = $this->withToken(self::TOKEN)
            ->postJson('/api/articles/evaluate', $this->payload())
            ->assertOk();

        $this->assertSame(10, $response->json('qa.score') - $withoutLinks->json('qa.score'));
    }

    public function test_links_to_nonexistent_articles_are_reported_as_broken(): void
    {
        $body = $this->strongBody()
            ."\n\nBaca juga [artikel hantu](/artikel/tidak-pernah-ada) dan [direktori](/layanan).\n";

        $this->withToken(self::TOKEN)
            ->postJson('/api/articles/evaluate', $this->payload(['body_md' => $body]))
            ->assertOk()
            ->assertJsonPath('qa.metrics.broken_internal_links', ['tidak-pernah-ada']);
    }

    public function test_links_to_unpublished_articles_count_as_broken(): void
    {
        $draft = $this->publishArticle('masih-draft', 'Masih Draft');
        $draft->update(['is_published' => false]);

        $body = $this->strongBody()."\n\nLihat [draft](/artikel/masih-draft).\n";

        $this->withToken(self::TOKEN)
            ->postJson('/api/articles/evaluate', $this->payload(['body_md' => $body]))
            ->assertOk()
            ->assertJsonPath('qa.metrics.broken_internal_links', ['masih-draft']);
    }

    public function test_link_targets_expose_published_articles_and_commercial_pages(): void
    {
        $this->publishArticle('panduan-umroh-pemula', 'Panduan Umroh Pemula');
        $this->publishArticle('masih-draft', 'Masih Draft')->update(['is_published' => false]);

        $targets = $this->withToken(self::TOKEN)
            ->getJson('/api/articles?limit=50')
            ->assertOk()
            ->json('link_targets');

        $kinds = array_column($targets, 'kind');
        $paths = array_column($targets, 'path');

        $this->assertContains('/artikel/panduan-umroh-pemula', $paths);
        $this->assertNotContains('/artikel/masih-draft', $paths, 'Artikel draft tidak boleh ditawarkan sebagai tujuan tautan.');
        $this->assertContains('/layanan', $paths);
        $this->assertContains('landing', $kinds);
    }

    public function test_manual_ingest_without_auto_publish_keeps_previous_behaviour(): void
    {
        $this->withToken(self::TOKEN)
            ->postJson('/api/articles', [
                'title' => 'Artikel manual tanpa QA',
                'body' => '<p>Isi singkat yang normalnya diblokir QA.</p>',
            ])
            ->assertCreated()
            ->assertJsonMissingPath('qa')
            ->assertJsonPath('data.is_published', true);
    }

    public function test_raising_the_minimum_score_blocks_a_previously_acceptable_draft(): void
    {
        config()->set('articles.qa.min_score', 90);

        $this->withToken(self::TOKEN)
            ->postJson('/api/articles', $this->payload($this->middlingOverrides()))
            ->assertCreated()
            ->assertJsonPath('qa.decision', EditorialQa::DECISION_BLOCKED)
            ->assertJsonPath('qa.hard_fail', false)
            ->assertJsonPath('data.is_published', false);
    }
}
