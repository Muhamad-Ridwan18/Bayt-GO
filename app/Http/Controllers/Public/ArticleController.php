<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\MuthowifProfile;
use App\Support\ArticleUrl;
use App\Support\SeoMetaOverrides;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ArticleController extends Controller
{
    private const PER_PAGE = 12;

    public function index(): View
    {
        $articles = Article::query()
            ->published()
            ->translatedIn(app()->getLocale())
            ->ordered()
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('articles.index', [
            'articles' => $articles,
            'title' => SeoMetaOverrides::title('articles', __('articles.index_title')),
            'metaDescription' => SeoMetaOverrides::description('articles', __('seo.articles_description')),
            'canonical' => $articles->currentPage() > 1
                ? $articles->url($articles->currentPage())
                : ArticleUrl::index(),
            'alternates' => ArticleUrl::indexAlternates(),
        ]);
    }

    public function show(string $slug): View
    {
        $article = Article::query()
            ->published()
            ->where('slug', $slug)
            ->first();

        if ($article === null) {
            throw (new ModelNotFoundException)->setModel(Article::class, [$slug]);
        }

        $locale = app()->getLocale();

        // Tanpa terjemahan asli, URL bahasa ini hanya akan menyajikan ulang bahasa lain.
        if (! $article->hasTranslation($locale)) {
            throw (new ModelNotFoundException)->setModel(Article::class, [$slug]);
        }

        $title = $article->localized('title');
        $author = $article->localized('author') ?: 'BaytGo';
        $metaDescription = $article->seoDescription();
        $coverImage = $article->coverImageUrl();
        $canonicalUrl = ArticleUrl::show($article, $locale);
        $relatedArticles = $this->relatedArticlesFor($article);
        $relatedServices = $this->relatedServicesForArticle($article);

        $articleSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'BlogPosting',
            'mainEntityOfPage' => [
                '@type' => 'WebPage',
                '@id' => $canonicalUrl,
            ],
            'headline' => Str::limit($title, 110, ''),
            'description' => $metaDescription,
            'inLanguage' => app()->getLocale(),
            'datePublished' => $article->published_at?->toIso8601String(),
            'dateModified' => ($article->updated_at ?? $article->published_at)?->toIso8601String(),
            'author' => [
                '@type' => 'Organization',
                'name' => $author,
                'url' => url('/'),
            ],
            'publisher' => [
                '@type' => 'Organization',
                'name' => config('app.name', 'BaytGo'),
                'logo' => [
                    '@type' => 'ImageObject',
                    'url' => asset('images/logo.png'),
                ],
            ],
            'url' => $canonicalUrl,
            'articleSection' => $article->localized('category') ?: __('seo.article_default_section'),
            'wordCount' => str_word_count(strip_tags($article->localized('body'))),
        ];

        if ($coverImage !== null) {
            $articleSchema['image'] = [$coverImage];
        }

        $breadcrumbSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                [
                    '@type' => 'ListItem',
                    'position' => 1,
                    'name' => __('nav.home'),
                    'item' => route('welcome'),
                ],
                [
                    '@type' => 'ListItem',
                    'position' => 2,
                    'name' => __('articles.index_title'),
                    'item' => ArticleUrl::index($locale),
                ],
                [
                    '@type' => 'ListItem',
                    'position' => 3,
                    'name' => $title,
                    'item' => $canonicalUrl,
                ],
            ],
        ];

        return view('articles.show', [
            'article' => $article,
            'title' => $title,
            'metaDescription' => $metaDescription,
            'ogImage' => $coverImage,
            'schema' => [$articleSchema, $breadcrumbSchema],
            'relatedArticles' => $relatedArticles,
            'relatedServices' => $relatedServices,
            'canonical' => $canonicalUrl,
            'alternates' => ArticleUrl::showAlternates($article),
        ]);
    }

    /**
     * @return Collection<int, Article>
     */
    private function relatedArticlesFor(Article $article): Collection
    {
        $category = Str::lower(trim($article->localized('category')));

        $candidates = Article::query()
            ->published()
            ->translatedIn(app()->getLocale())
            ->whereKeyNot($article->getKey())
            ->ordered()
            ->limit(24)
            ->get();

        if ($candidates->isEmpty()) {
            return collect();
        }

        return $candidates
            ->sortByDesc(function (Article $candidate) use ($category): int {
                $sameCategory = $category !== ''
                    && Str::lower(trim($candidate->localized('category'))) === $category;

                return ($sameCategory ? 100 : 0) + ($candidate->is_featured ? 10 : 0);
            })
            ->take(4)
            ->values();
    }

    private function relatedServicesForArticle(Article $article)
    {
        $text = Str::lower($article->localized('title').' '.$article->localized('excerpt').' '.strip_tags($article->localized('body')));

        $query = MuthowifProfile::query()->approved();

        if (Str::contains($text, 'jakarta')) {
            $query->where('city', 'Jakarta');
        } elseif (Str::contains($text, 'madinah')) {
            $query->where('city', 'Madinah');
        } elseif (Str::contains($text, 'bahasa indonesia') || Str::contains($text, 'indonesia')) {
            $query->whereJsonContains('languages', 'Bahasa Indonesia');
        }

        $services = $query
            ->with(['user', 'services'])
            ->withMarketplaceStats()
            ->orderByMarketplaceRanking()
            ->limit(5)
            ->get();

        if ($services->isEmpty()) {
            return MuthowifProfile::query()
                ->approved()
                ->with(['user', 'services'])
                ->withMarketplaceStats()
                ->orderByMarketplaceRanking()
                ->limit(5)
                ->get();
        }

        return $services;
    }
}
