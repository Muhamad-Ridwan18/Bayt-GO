<?php

namespace App\Services\Seo;

use App\Models\Article;
use App\Models\MuthowifProfile;
use Illuminate\Support\Carbon;

class SitemapGenerator
{
    public function generateIndex(): string
    {
        $types = ['home', 'categories', 'services', 'articles'];

        $content = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
        $content .= "<sitemapindex xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";

        foreach ($types as $type) {
            $count = $this->pageCount($type);

            for ($page = 1; $page <= $count; $page++) {
                $content .= "  <sitemap>\n";
                $content .= "    <loc>" . e(route('seo.sitemap.page', ['type' => $type, 'page' => $page], true)) . "</loc>\n";
                $content .= "    <lastmod>" . Carbon::now()->toIso8601String() . "</lastmod>\n";
                $content .= "  </sitemap>\n";
            }
        }

        $content .= '</sitemapindex>';

        return $content;
    }

    public function generatePage(string $type, int $page = 1): string
    {
        $items = match ($type) {
            'home' => $this->homeItems(),
            'categories' => $this->categoryItems(),
            'services' => $this->serviceItems($page),
            'articles' => $this->articleItems($page),
            default => [],
        };

        $content = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
        $content .= "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";

        foreach ($items as $item) {
            $content .= "  <url>\n";
            $content .= "    <loc>" . e($item['loc']) . "</loc>\n";
            $content .= "    <lastmod>" . e($item['lastmod']) . "</lastmod>\n";
            $content .= "    <changefreq>" . e($item['changefreq']) . "</changefreq>\n";
            $content .= "    <priority>" . e($item['priority']) . "</priority>\n";
            $content .= "  </url>\n";
        }

        $content .= '</urlset>';

        return $content;
    }

    public function pageCount(string $type): int
    {
        $total = match ($type) {
            'home' => 1,
            'categories' => count(config('seo.landing_pages', [])),
            'services' => MuthowifProfile::approved()->count(),
            'articles' => Article::published()->count(),
            default => 0,
        };

        if ($type === 'services' || $type === 'articles') {
            return max(1, (int) ceil($total / config('seo.sitemap.max_urls_per_file', 500)));
        }

        return 1;
    }

    protected function homeItems(): array
    {
        return [
            [
                'loc' => route('welcome', [], true),
                'lastmod' => Carbon::now()->toIso8601String(),
                'changefreq' => config('seo.sitemap.changefreq.home', 'daily'),
                'priority' => config('seo.sitemap.priorities.home', '1.0'),
            ],
        ];
    }

    protected function categoryItems(): array
    {
        $landingPages = config('seo.landing_pages', []);
        $items = [];

        foreach ($landingPages as $slug => $landing) {
            $items[] = [
                'loc' => route('seo.landing', ['keyword' => $slug], true),
                'lastmod' => Carbon::now()->toIso8601String(),
                'changefreq' => config('seo.sitemap.changefreq.categories', 'daily'),
                'priority' => config('seo.sitemap.priorities.categories', '0.9'),
            ];
        }

        return $items;
    }

    protected function serviceItems(int $page): array
    {
        $perPage = config('seo.sitemap.max_urls_per_file', 500);

        return MuthowifProfile::approved()
            ->orderByDesc('updated_at')
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get()
            ->map(fn ($profile) => [
                'loc' => route('layanan.show', $profile, true),
                'lastmod' => $profile->updated_at?->toIso8601String() ?? Carbon::now()->toIso8601String(),
                'changefreq' => config('seo.sitemap.changefreq.services', 'weekly'),
                'priority' => config('seo.sitemap.priorities.services', '0.8'),
            ])->toArray();
    }

    protected function articleItems(int $page): array
    {
        $perPage = config('seo.sitemap.max_urls_per_file', 500);

        return Article::published()
            ->orderByDesc('published_at')
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get()
            ->map(fn ($article) => [
                'loc' => route('articles.show', $article, true),
                'lastmod' => $article->published_at?->toIso8601String() ?? Carbon::now()->toIso8601String(),
                'changefreq' => config('seo.sitemap.changefreq.articles', 'monthly'),
                'priority' => config('seo.sitemap.priorities.articles', '0.6'),
            ])->toArray();
    }
}
