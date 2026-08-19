<?php

namespace Tests\Feature;

use App\Models\Article;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ArticleApiTest extends TestCase
{
    use RefreshDatabase;

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
}
