<?php

namespace App\Support;

use App\Enums\MuthowifVerificationStatus;
use App\Models\Article;
use App\Models\Campaign;
use App\Models\MuthowifPortfolioImage;
use App\Models\MuthowifProfile;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

final class WelcomePageCache
{
    public const KEY = 'welcome:page_data:v2';

    /** @deprecated Bust legacy entries that cached serialized Eloquent collections. */
    private const LEGACY_KEY = 'welcome:page_data:v1';

    public static function forget(): void
    {
        Cache::forget(self::KEY);
        Cache::forget(self::LEGACY_KEY);
    }

    /**
     * @return array{
     *   featuredMuthowifs: EloquentCollection<int, MuthowifProfile>,
     *   latestArticles: EloquentCollection<int, Article>,
     *   landingPages: Collection<int, array<string, mixed>>,
     *   latestServices: EloquentCollection<int, MuthowifProfile>,
     *   galleryImages: EloquentCollection<int, MuthowifPortfolioImage>,
     *   activeCampaigns: EloquentCollection<int, Campaign>
     * }
     */
    public static function data(): array
    {
        $seconds = max(30, (int) config('welcome.cache_seconds', 120));

        /** @var array<string, mixed> $payload */
        $payload = Cache::remember(self::KEY, now()->addSeconds($seconds), static fn (): array => self::buildPayload());

        return self::hydrate($payload);
    }

    /**
     * Serializable snapshot (IDs + plain arrays only — never Eloquent models in cache).
     *
     * @return array<string, mixed>
     */
    private static function buildPayload(): array
    {
        $featuredMuthowifs = MuthowifProfile::query()
            ->with(['user:id,name', 'services:id,muthowif_profile_id,daily_price,name'])
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
            ->map(fn ($landing, $slug) => array_merge($landing, ['slug' => $slug]))
            ->values()
            ->all();

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
            'featuredMuthowifIds' => $featuredMuthowifs->modelKeys(),
            'latestArticleIds' => $latestArticles->modelKeys(),
            'landingPages' => $landingPages,
            'latestServiceIds' => $latestServices->modelKeys(),
            'galleryImageIds' => $galleryImages->modelKeys(),
            'activeCampaignIds' => $activeCampaigns->modelKeys(),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{
     *   featuredMuthowifs: EloquentCollection<int, MuthowifProfile>,
     *   latestArticles: EloquentCollection<int, Article>,
     *   landingPages: Collection<int, array<string, mixed>>,
     *   latestServices: EloquentCollection<int, MuthowifProfile>,
     *   galleryImages: EloquentCollection<int, MuthowifPortfolioImage>,
     *   activeCampaigns: EloquentCollection<int, Campaign>
     * }
     */
    private static function hydrate(array $payload): array
    {
        /** @var list<int|string> $featuredIds */
        $featuredIds = self::idList($payload['featuredMuthowifIds'] ?? []);
        /** @var list<int|string> $articleIds */
        $articleIds = self::idList($payload['latestArticleIds'] ?? []);
        /** @var list<int|string> $serviceIds */
        $serviceIds = self::idList($payload['latestServiceIds'] ?? []);
        /** @var list<int|string> $galleryIds */
        $galleryIds = self::idList($payload['galleryImageIds'] ?? []);
        /** @var list<int|string> $campaignIds */
        $campaignIds = self::idList($payload['activeCampaignIds'] ?? []);

        $featuredMuthowifs = self::orderedModels(
            $featuredIds,
            MuthowifProfile::query()
                ->with(['user:id,name', 'services:id,muthowif_profile_id,daily_price,name'])
                ->whereIn((new MuthowifProfile)->getQualifiedKeyName(), $featuredIds)
                ->get(),
        );

        $latestArticles = self::orderedModels(
            $articleIds,
            Article::query()
                ->published()
                ->whereIn((new Article)->getQualifiedKeyName(), $articleIds)
                ->get(),
        );

        $latestServices = self::orderedModels(
            $serviceIds,
            MuthowifProfile::query()
                ->approved()
                ->with('user:id,name')
                ->whereIn((new MuthowifProfile)->getQualifiedKeyName(), $serviceIds)
                ->get(),
        );

        $galleryImages = self::orderedModels(
            $galleryIds,
            MuthowifPortfolioImage::query()
                ->select('id', 'path', 'muthowif_portfolio_id')
                ->with([
                    'portfolio:id,muthowif_profile_id,title',
                    'portfolio.muthowifProfile:id,slug,user_id',
                    'portfolio.muthowifProfile.user:id,name',
                ])
                ->whereIn((new MuthowifPortfolioImage)->getQualifiedKeyName(), $galleryIds)
                ->get(),
        );

        $activeCampaigns = self::orderedModels(
            $campaignIds,
            Campaign::query()
                ->active()
                ->whereIn((new Campaign)->getQualifiedKeyName(), $campaignIds)
                ->get(),
        );

        $landingRaw = $payload['landingPages'] ?? [];
        $landingPages = collect(is_array($landingRaw) ? $landingRaw : []);

        return [
            'featuredMuthowifs' => $featuredMuthowifs,
            'latestArticles' => $latestArticles,
            'landingPages' => $landingPages,
            'latestServices' => $latestServices,
            'galleryImages' => $galleryImages,
            'activeCampaigns' => $activeCampaigns,
        ];
    }

    /**
     * @return list<int|string>
     */
    private static function idList(mixed $ids): array
    {
        if (! is_array($ids)) {
            return [];
        }

        return array_values(array_filter($ids, static fn ($id) => is_int($id) || (is_string($id) && $id !== '')));
    }

    /**
     * @template TModel of Model
     *
     * @param  list<int|string>  $orderedIds
     * @param  EloquentCollection<int, TModel>  $models
     * @return EloquentCollection<int, TModel>
     */
    private static function orderedModels(array $orderedIds, EloquentCollection $models): EloquentCollection
    {
        if ($orderedIds === []) {
            return new EloquentCollection;
        }

        $rank = array_flip($orderedIds);

        return $models
            ->sortBy(static fn (Model $model): int => $rank[$model->getKey()] ?? PHP_INT_MAX)
            ->values();
    }
}
