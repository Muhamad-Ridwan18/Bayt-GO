<?php

namespace App\Support;

use App\Enums\MuthowifVerificationStatus;
use App\Models\Article;
use App\Models\Campaign;
use App\Models\MuthowifPortfolioImage;
use App\Models\MuthowifProfile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

final class WelcomePageCache
{
    public const KEY = 'welcome:page_data:v1';

    public static function forget(): void
    {
        Cache::forget(self::KEY);
    }

    /**
     * @return array{
     *   featuredMuthowifs: \Illuminate\Database\Eloquent\Collection,
     *   latestArticles: \Illuminate\Database\Eloquent\Collection,
     *   landingPages: Collection,
     *   latestServices: \Illuminate\Database\Eloquent\Collection,
     *   galleryImages: \Illuminate\Database\Eloquent\Collection,
     *   activeCampaigns: \Illuminate\Database\Eloquent\Collection
     * }
     */
    public static function data(): array
    {
        $seconds = max(30, (int) config('welcome.cache_seconds', 120));

        return Cache::remember(self::KEY, now()->addSeconds($seconds), function (): array {
            $featuredMuthowifs = MuthowifProfile::query()
                ->with(['user:id,name', 'services:id,muthowif_profile_id,daily_price'])
                ->approved()
                ->hasPublishedServices()
                ->withMarketplaceStats()
                ->orderByMarketplaceRanking()
                ->limit(14)
                ->get();

            $latestArticles = Article::query()
                ->published()
                ->orderByDesc('published_at')
                ->limit(3)
                ->get();

            $landingPages = collect(config('seo.landing_pages', []))
                ->map(fn ($landing, $slug) => array_merge($landing, ['slug' => $slug]));

            $latestServices = MuthowifProfile::query()
                ->approved()
                ->with('user:id,name')
                ->orderByDesc('updated_at')
                ->limit(4)
                ->get();

            $galleryLimit = max(6, (int) config('welcome.gallery_limit', 30));
            $galleryPool = MuthowifPortfolioImage::query()
                ->join('muthowif_portfolios', 'muthowif_portfolio_images.muthowif_portfolio_id', '=', 'muthowif_portfolios.id')
                ->join('muthowif_profiles', 'muthowif_portfolios.muthowif_profile_id', '=', 'muthowif_profiles.id')
                ->where('muthowif_profiles.verification_status', MuthowifVerificationStatus::Approved)
                ->select('muthowif_portfolio_images.id', 'muthowif_portfolio_images.path')
                ->orderByDesc('muthowif_portfolio_images.created_at')
                ->limit($galleryLimit * 2)
                ->get();

            $galleryImages = $galleryPool->shuffle()->take($galleryLimit)->values();

            $activeCampaigns = Campaign::query()
                ->active()
                ->orderBy('sort_order')
                ->orderByDesc('start_date')
                ->get();

            return [
                'featuredMuthowifs' => $featuredMuthowifs,
                'latestArticles' => $latestArticles,
                'landingPages' => $landingPages,
                'latestServices' => $latestServices,
                'galleryImages' => $galleryImages,
                'activeCampaigns' => $activeCampaigns,
            ];
        });
    }
}
