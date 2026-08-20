<?php

namespace Tests\Feature;

use App\Models\Article;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ArticleApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_check_slug_existence(): void
    {
        Article::query()->create([
            'slug' => 'slug-cek',
            'is_published' => true,
            'is_featured' => false,
            'sort_order' => 0,
            'published_at' => now(),
            'translations' => [
                'id' => [
                    'title' => 'Cek',
                    'excerpt' => '',
                    'category' => '',
                    'author' => '',
                    'body' => '<p>Isi</p>',
                    'body_json' => '',
                    'body_md' => '',
                ],
            ],
        ]);

        $this->withToken('testing-articles-token')
            ->getJson('/api/articles?slug=slug-cek')
            ->assertOk()
            ->assertJsonPath('exists', true)
            ->assertJsonPath('data.slug', 'slug-cek');

        $this->withToken('testing-articles-token')
            ->getJson('/api/articles?slug=slug-belum-ada')
            ->assertOk()
            ->assertJsonPath('exists', false)
            ->assertJsonPath('data', null);
    }

    public function test_rejects_missing_token(): void
    {
        $this->postJson('/api/articles', [
            'title' => 'Judul artikel uji',
            'body' => '<p>Isi</p>',
        ])->assertUnauthorized();
    }

    public function test_rejects_invalid_token(): void
    {
        $this->withToken('wrong-token')
            ->postJson('/api/articles', [
                'title' => 'Judul artikel uji',
                'body' => '<p>Isi</p>',
            ])
            ->assertUnauthorized();
    }

    public function test_creates_article_from_flat_json(): void
    {
        $response = $this->withToken('testing-articles-token')
            ->postJson('/api/articles', [
                'title' => 'Panduan Umroh Pertama',
                'title_en' => 'First Umrah Guide',
                'excerpt' => 'Ringkasan singkat',
                'category' => 'Umroh',
                'author' => 'BaytGo',
                'body' => '<p>Isi artikel</p>',
                'is_published' => true,
                'is_featured' => false,
            ]);

        $response
            ->assertCreated()
            ->assertJsonPath('created', true)
            ->assertJsonPath('data.slug', 'panduan-umroh-pertama')
            ->assertJsonPath('data.translations.id.title', 'Panduan Umroh Pertama')
            ->assertJsonPath('data.translations.en.title', 'First Umrah Guide');
    }

    public function test_post_with_existing_slug_upserts(): void
    {
        Article::query()->create([
            'slug' => 'artikel-lama',
            'is_published' => true,
            'is_featured' => false,
            'sort_order' => 0,
            'published_at' => now(),
            'translations' => [
                'id' => [
                    'title' => 'Lama',
                    'excerpt' => '',
                    'category' => '',
                    'author' => '',
                    'body' => '<p>Lama</p>',
                    'body_json' => '',
                    'body_md' => '',
                ],
            ],
        ]);

        $this->withToken('testing-articles-token')
            ->postJson('/api/articles', [
                'slug' => 'artikel-lama',
                'title' => 'Artikel Diperbarui',
                'body' => '<p>Baru</p>',
            ])
            ->assertOk()
            ->assertJsonPath('created', false)
            ->assertJsonPath('data.translations.id.title', 'Artikel Diperbarui');

        $this->assertSame(1, Article::query()->count());
    }

    public function test_converts_markdown_body(): void
    {
        $this->withToken('testing-articles-token')
            ->postJson('/api/articles', [
                'title' => 'Artikel Markdown',
                'body_md' => "## Judul\n\nParagraf **tebal**.",
            ])
            ->assertCreated()
            ->assertJsonPath('data.translations.id.body_md', "## Judul\n\nParagraf **tebal**.");

        $body = Article::query()->first()?->translations['id']['body'] ?? '';
        $this->assertStringContainsString('<strong>tebal</strong>', $body);
    }

    public function test_accepts_json_without_json_content_type(): void
    {
        $payload = json_encode([
            'title' => 'Artikel Tanpa Content Type JSON',
            'body_md' => 'Isi markdown',
        ], JSON_THROW_ON_ERROR);

        $this->call(
            'POST',
            '/api/articles',
            [],
            [],
            [],
            [
                'HTTP_AUTHORIZATION' => 'Bearer testing-articles-token',
                'CONTENT_TYPE' => 'text/plain',
            ],
            $payload,
        )->assertCreated()
            ->assertJsonPath('data.translations.id.title', 'Artikel Tanpa Content Type JSON');
    }

    public function test_accepts_double_encoded_json(): void
    {
        $inner = json_encode([
            'title' => 'Artikel Double Encode',
            'body' => '<p>Isi</p>',
        ], JSON_THROW_ON_ERROR);

        $this->call(
            'POST',
            '/api/articles',
            [],
            [],
            [],
            [
                'HTTP_AUTHORIZATION' => 'Bearer testing-articles-token',
                'CONTENT_TYPE' => 'application/json',
            ],
            json_encode($inner, JSON_THROW_ON_ERROR),
        )->assertCreated()
            ->assertJsonPath('data.translations.id.title', 'Artikel Double Encode');
    }

    public function test_accepts_n8n_wrapped_data_object(): void
    {
        $this->withToken('testing-articles-token')
            ->postJson('/api/articles', [
                'data' => [
                    'title' => 'Artikel Nested Data',
                    'body_md' => 'Isi',
                ],
            ])
            ->assertCreated()
            ->assertJsonPath('data.translations.id.title', 'Artikel Nested Data');
    }

    public function test_accepts_json_array_wrapper(): void
    {
        $this->withToken('testing-articles-token')
            ->postJson('/api/articles', [
                [
                    'title' => 'Artikel Array Wrapper',
                    'body_md' => 'Isi',
                ],
            ])
            ->assertCreated()
            ->assertJsonPath('data.translations.id.title', 'Artikel Array Wrapper');
    }

    public function test_downloads_image_url(): void
    {
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==', true);
        Http::fake([
            'https://cdn.example.com/cover.png' => Http::response($png, 200, ['Content-Type' => 'image/png']),
        ]);

        $this->withToken('testing-articles-token')
            ->postJson('/api/articles', [
                'title' => 'Artikel Gambar',
                'body' => '<p>Isi</p>',
                'image_url' => 'https://cdn.example.com/cover.png',
            ])
            ->assertCreated();

        $body = Article::query()->first()?->translations['id']['body'] ?? '';
        $this->assertStringContainsString('<img', $body);
        $this->assertStringContainsString('storage/articles/images/', $body);
    }

    public function test_skips_invalid_image_url_without_failing(): void
    {
        Http::fake([
            'https://example.com/not-image' => Http::response('<html>page</html>', 200, ['Content-Type' => 'text/html']),
        ]);

        $this->withToken('testing-articles-token')
            ->postJson('/api/articles', [
                'title' => 'Artikel Tanpa Cover Valid',
                'body' => '<p>Isi</p>',
                'image_url' => 'https://example.com/not-image',
            ])
            ->assertCreated()
            ->assertJsonPath('created', true);

        $body = Article::query()->first()?->translations['id']['body'] ?? '';
        $this->assertStringNotContainsString('<img', $body);
    }

    public function test_uses_fallback_image_urls(): void
    {
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==', true);
        Http::fake([
            'https://example.com/page.html' => Http::response('<html>nope</html>', 200, ['Content-Type' => 'text/html']),
            'https://cdn.example.com/real.png' => Http::response($png, 200, ['Content-Type' => 'application/octet-stream']),
        ]);

        $this->withToken('testing-articles-token')
            ->postJson('/api/articles', [
                'title' => 'Artikel Fallback Gambar',
                'body' => '<p>Isi</p>',
                'image_url' => 'https://example.com/page.html',
                'image_urls' => [
                    'https://example.com/page.html',
                    'https://cdn.example.com/real.png',
                ],
            ])
            ->assertCreated();

        $body = Article::query()->first()?->translations['id']['body'] ?? '';
        $this->assertStringContainsString('<img', $body);
    }

    public function test_lists_recent_articles_for_dedup(): void
    {
        Article::query()->create([
            'slug' => 'artikel-satu',
            'is_published' => true,
            'is_featured' => false,
            'sort_order' => 0,
            'published_at' => now(),
            'translations' => [
                'id' => [
                    'title' => 'Artikel Satu',
                    'excerpt' => '',
                    'category' => 'Umroh',
                    'author' => 'BaytGo',
                    'body' => '<p>Satu</p>',
                    'body_json' => '',
                    'body_md' => '',
                ],
            ],
        ]);

        Article::query()->create([
            'slug' => 'artikel-dua',
            'is_published' => false,
            'is_featured' => false,
            'sort_order' => 0,
            'published_at' => null,
            'translations' => [
                'id' => [
                    'title' => 'Artikel Dua',
                    'excerpt' => '',
                    'category' => 'Muthowif',
                    'author' => 'BaytGo',
                    'body' => '<p>Dua</p>',
                    'body_json' => '',
                    'body_md' => '',
                ],
            ],
        ]);

        $this->withToken('testing-articles-token')
            ->getJson('/api/articles?limit=10')
            ->assertOk()
            ->assertJsonPath('meta.count', 2)
            ->assertJsonFragment(['slug' => 'artikel-satu', 'title' => 'Artikel Satu'])
            ->assertJsonFragment(['slug' => 'artikel-dua', 'title' => 'Artikel Dua']);
    }
}
