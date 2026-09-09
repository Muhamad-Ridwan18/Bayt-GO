<?php

namespace Tests\Feature;

use App\Models\Article;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArticleLocaleUrlTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string, array<string, string>>  $extraTranslations
     */
    private function makeArticle(string $slug, array $extraTranslations = []): Article
    {
        return Article::query()->create([
            'slug' => $slug,
            'is_published' => true,
            'is_featured' => false,
            'sort_order' => 0,
            'published_at' => now()->subDay(),
            'translations' => array_merge([
                'id' => [
                    'title' => 'Judul Indonesia '.$slug,
                    'excerpt' => 'Ringkasan Indonesia.',
                    'category' => 'Umroh',
                    'author' => 'BaytGo',
                    'body' => '<p>Isi artikel Indonesia.</p>',
                    'body_json' => '',
                    'body_md' => '',
                ],
            ], $extraTranslations),
        ]);
    }

    /**
     * @return array<string, array<string, string>>
     */
    private function englishBlock(string $slug): array
    {
        return [
            'en' => [
                'title' => 'English Title '.$slug,
                'excerpt' => 'English summary.',
                'category' => 'Umrah',
                'author' => 'BaytGo',
                'body' => '<p>English article body.</p>',
                'body_json' => '',
                'body_md' => '',
            ],
        ];
    }

    public function test_english_url_404s_when_article_has_no_english_translation(): void
    {
        $article = $this->makeArticle('hanya-indonesia');

        $this->get(route('articles.show', $article->slug))->assertOk();
        $this->get(route('en.articles.show', $article->slug))->assertNotFound();
    }

    public function test_english_url_serves_english_content_when_translation_exists(): void
    {
        $article = $this->makeArticle('dwibahasa', $this->englishBlock('dwibahasa'));

        $this->get(route('en.articles.show', $article->slug))
            ->assertOk()
            ->assertSee('English Title dwibahasa')
            ->assertDontSee('Judul Indonesia dwibahasa');
    }

    public function test_indonesian_url_stays_indonesian_even_when_session_locale_is_english(): void
    {
        $article = $this->makeArticle('terkunci', $this->englishBlock('terkunci'));

        $this->withSession(['locale' => 'en'])
            ->get(route('articles.show', $article->slug))
            ->assertOk()
            ->assertSee('Judul Indonesia terkunci')
            ->assertDontSee('English Title terkunci');
    }

    public function test_bilingual_article_cross_links_both_languages_with_hreflang(): void
    {
        $article = $this->makeArticle('dua-bahasa', $this->englishBlock('dua-bahasa'));

        $idUrl = route('articles.show', $article->slug);
        $enUrl = route('en.articles.show', $article->slug);

        $this->get($idUrl)
            ->assertOk()
            ->assertSee('rel="canonical" href="'.$idUrl.'"', false)
            ->assertSee('hreflang="id" href="'.$idUrl.'"', false)
            ->assertSee('hreflang="en" href="'.$enUrl.'"', false)
            ->assertSee('hreflang="x-default" href="'.$idUrl.'"', false);

        $this->get($enUrl)
            ->assertOk()
            ->assertSee('rel="canonical" href="'.$enUrl.'"', false)
            ->assertSee('hreflang="en" href="'.$enUrl.'"', false);
    }

    public function test_monolingual_article_announces_only_indonesian(): void
    {
        $article = $this->makeArticle('satu-bahasa');

        $this->get(route('articles.show', $article->slug))
            ->assertOk()
            ->assertSee('hreflang="id"', false)
            ->assertDontSee('hreflang="en"', false);
    }

    public function test_english_index_lists_only_translated_articles(): void
    {
        $this->makeArticle('tanpa-inggris');
        $this->makeArticle('dengan-inggris', $this->englishBlock('dengan-inggris'));

        $this->get(route('en.articles.index'))
            ->assertOk()
            ->assertSee('English Title dengan-inggris')
            ->assertDontSee('Judul Indonesia tanpa-inggris');

        $this->get(route('articles.index'))
            ->assertOk()
            ->assertSee('Judul Indonesia tanpa-inggris');
    }

    public function test_sitemap_lists_english_url_only_for_translated_articles(): void
    {
        $bilingual = $this->makeArticle('sitemap-dwibahasa', $this->englishBlock('sitemap-dwibahasa'));
        $indonesianOnly = $this->makeArticle('sitemap-indonesia');

        $this->get('/sitemap-articles-1.xml')
            ->assertOk()
            ->assertSee(route('articles.show', $bilingual->slug), false)
            ->assertSee(route('en.articles.show', $bilingual->slug), false)
            ->assertSee(route('articles.show', $indonesianOnly->slug), false)
            ->assertDontSee(route('en.articles.show', $indonesianOnly->slug), false);
    }

    public function test_language_switcher_sends_reader_to_the_translated_url(): void
    {
        $article = $this->makeArticle('pindah-bahasa', $this->englishBlock('pindah-bahasa'));

        $enUrl = route('en.articles.show', $article->slug);

        $this->get(route('articles.show', $article->slug))
            ->assertOk()
            ->assertSee('next='.urlencode($enUrl), false);

        $this->get(route('locale.switch', ['locale' => 'en']).'?next='.urlencode($enUrl))
            ->assertRedirect($enUrl);
    }

    public function test_locale_switch_ignores_offsite_next_targets(): void
    {
        $this->get(route('locale.switch', ['locale' => 'en']).'?next='.urlencode('https://evil.example.com/phish'))
            ->assertRedirect(route('welcome'));
    }
}
