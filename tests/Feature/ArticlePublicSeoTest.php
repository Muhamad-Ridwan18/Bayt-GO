<?php

namespace Tests\Feature;

use App\Models\Article;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArticlePublicSeoTest extends TestCase
{
    use RefreshDatabase;

    public function test_article_show_includes_seo_og_schema_and_related(): void
    {
        $main = Article::query()->create([
            'slug' => 'panduan-umroh',
            'is_published' => true,
            'is_featured' => true,
            'sort_order' => 0,
            'published_at' => now()->subDay(),
            'translations' => [
                'id' => [
                    'title' => 'Panduan Umroh Lengkap',
                    'excerpt' => 'Ringkasan panduan umroh untuk jamaah pemula di BaytGo.',
                    'category' => 'Umroh',
                    'author' => 'BaytGo',
                    'body' => '<p><img src="/storage/articles/images/cover.jpg" alt=""></p><p>Isi artikel umroh.</p>',
                    'body_json' => '',
                    'body_md' => '',
                ],
            ],
        ]);

        Article::query()->create([
            'slug' => 'tips-muthowif',
            'is_published' => true,
            'is_featured' => false,
            'sort_order' => 1,
            'published_at' => now()->subHours(2),
            'translations' => [
                'id' => [
                    'title' => 'Tips Memilih Muthowif',
                    'excerpt' => 'Cara memilih pendamping ibadah.',
                    'category' => 'Umroh',
                    'author' => 'BaytGo',
                    'body' => '<p>Isi tips.</p>',
                    'body_json' => '',
                    'body_md' => '',
                ],
            ],
        ]);

        $response = $this->get(route('articles.show', $main->slug));

        $response
            ->assertOk()
            ->assertSee('og:type" content="article"', false)
            ->assertSee('og:image" content="'.url('/storage/articles/images/cover.jpg').'"', false)
            ->assertSee('"@type": "BlogPosting"', false)
            ->assertSee('"@type": "BreadcrumbList"', false)
            ->assertSee(__('articles.related_articles_title'), false)
            ->assertSee('Tips Memilih Muthowif');
    }
}
