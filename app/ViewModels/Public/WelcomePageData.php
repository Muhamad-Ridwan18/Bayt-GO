<?php

namespace App\ViewModels\Public;

use App\Models\Article;
use App\Models\Campaign;
use App\Models\MuthowifPortfolioImage;
use App\Models\MuthowifProfile;
use App\Models\User;
use App\Support\WelcomeLanding;
use App\Support\WelcomePageCache;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

final class WelcomePageData
{
    /**
     * @param  list<array{key: string, label: string, type: string, url: string, icon: string, category?: string}>  $categories
     * @param  list<array{name: string, href: string, photo: string, rating: string, location: ?string, tags: list<string>, price: string}>  $muthowifCards
     * @param  list<array{url: string, caption: string, href: ?string}>  $galleryItems
     * @param  list<array{title: string, excerpt: string, href: string, thumbnail: ?string}>  $articleCards
     * @param  list<array{title?: string, desc?: string, read?: string, fragment?: string}>  $guideCards
     * @param  list<array{title: string, desc: string}>  $workSteps
     * @param  list<array{q: string, a: string}>  $faqItems
     * @param  Collection<int, Campaign>  $campaigns
     * @param  array{defaultStartsAt: string, minStartsAt: string, minDate: string, rangeRequired: string, slotRequired: string}  $search
     * @param  array{title: string, description: string, schema: array<string, mixed>}  $seo
     */
    public function __construct(
        public readonly ?string $heroName,
        public readonly string $heroBgUrl,
        public readonly string $helpHref,
        public readonly bool $showLandingChrome,
        public readonly array $categories,
        public readonly array $search,
        public readonly Collection $campaigns,
        public readonly array $muthowifCards,
        public readonly array $galleryItems,
        public readonly array $articleCards,
        public readonly array $guideCards,
        public readonly array $workSteps,
        public readonly array $faqItems,
        public readonly string $layananIndexUrl,
        public readonly string $articlesIndexUrl,
        public readonly array $seo,
    ) {}

    public static function forWelcome(?User $user = null): self
    {
        $user ??= Auth::user();

        return self::fromCache(
            cache: WelcomePageCache::data(),
            heroName: $user?->name,
            showLandingChrome: true,
            guideCards: [],
            muthowifLimit: 14,
            galleryLimit: 16,
            articleLimit: 4,
        );
    }

    /**
     * Bridge for dashboard / legacy includes that still pass raw collections.
     *
     * @param  list<array{title?: string, desc?: string, read?: string, fragment?: string}>  $guideCards
     */
    public static function fromLegacy(
        ?string $heroName = null,
        array $guideCards = [],
        mixed $featuredMuthowifs = null,
        mixed $latestArticles = null,
        mixed $activeCampaigns = null,
        mixed $galleryImages = null,
        bool $showLandingChrome = true,
        int $muthowifLimit = 14,
    ): self {
        $featured = collect($featuredMuthowifs ?? []);
        $articles = collect($latestArticles ?? []);
        $campaigns = collect($activeCampaigns ?? []);
        $gallery = collect($galleryImages ?? []);

        if ($featured->isEmpty() || $articles->isEmpty() || $gallery->isEmpty() || $campaigns->isEmpty()) {
            $fresh = WelcomePageCache::data();
            if ($featured->isEmpty()) {
                $featured = collect($fresh['featuredMuthowifs']);
            }
            if ($articles->isEmpty()) {
                $articles = collect($fresh['latestArticles']);
            }
            if ($gallery->isEmpty()) {
                $gallery = collect($fresh['galleryImages']);
            }
            if ($campaigns->isEmpty()) {
                $campaigns = collect($fresh['activeCampaigns']);
            }
        }

        return self::fromCache(
            cache: [
                'featuredMuthowifs' => $featured,
                'latestArticles' => $articles,
                'activeCampaigns' => $campaigns,
                'galleryImages' => $gallery,
            ],
            heroName: $heroName,
            showLandingChrome: $showLandingChrome,
            guideCards: $guideCards,
            muthowifLimit: $muthowifLimit,
            galleryLimit: 16,
            articleLimit: 4,
        );
    }

    /**
     * @param  array<string, mixed>  $cache
     * @param  list<array{title?: string, desc?: string, read?: string, fragment?: string}>  $guideCards
     */
    public static function fromCache(
        array $cache,
        ?string $heroName = null,
        bool $showLandingChrome = true,
        array $guideCards = [],
        int $muthowifLimit = 14,
        int $galleryLimit = 8,
        int $articleLimit = 4,
    ): self {
        /** @var Collection<int, MuthowifProfile> $featured */
        $featured = collect($cache['featuredMuthowifs'] ?? [])->take($muthowifLimit);
        /** @var Collection<int, Article> $articles */
        $articles = collect($cache['latestArticles'] ?? [])->take($articleLimit);
        /** @var Collection<int, MuthowifPortfolioImage> $gallery */
        $gallery = collect($cache['galleryImages'] ?? [])
            ->shuffle()
            ->take($galleryLimit);
        /** @var Collection<int, Campaign> $campaigns */
        $campaigns = collect($cache['activeCampaigns'] ?? []);

        $workSteps = __('welcome.work_steps');
        $workSteps = is_array($workSteps) ? array_values($workSteps) : [];

        $faqItems = __('welcome.faq_items');
        $faqItems = is_array($faqItems) ? array_values($faqItems) : [];

        return new self(
            heroName: $heroName,
            heroBgUrl: WelcomeLanding::resolvedHeroImageUrl(),
            helpHref: self::resolveHelpHref(),
            showLandingChrome: $showLandingChrome,
            categories: self::buildCategories(),
            search: [
                'defaultStartsAt' => now()->addDay()->setTime(9, 0)->format('Y-m-d\TH:i'),
                'minStartsAt' => now()->format('Y-m-d\TH:i'),
                'minDate' => now()->toDateString(),
                'rangeRequired' => __('welcome.landing_pick_dates_required_range'),
                'slotRequired' => __('welcome.landing_pick_dates_required_slot'),
            ],
            campaigns: $campaigns,
            muthowifCards: $featured->map(fn (MuthowifProfile $profile) => self::mapMuthowifCard($profile))->values()->all(),
            galleryItems: $gallery->map(fn (MuthowifPortfolioImage $image) => self::mapGalleryItem($image))->values()->all(),
            articleCards: $articles->map(fn (Article $article) => self::mapArticleCard($article))->values()->all(),
            guideCards: $guideCards,
            workSteps: $workSteps,
            faqItems: $faqItems,
            layananIndexUrl: route('layanan.index'),
            articlesIndexUrl: route('articles.index'),
            seo: self::buildSeo(),
        );
    }

    /**
     * @return array{selected: array<string, mixed>, startsAt: string, rangeRequired: string, slotRequired: string}
     */
    public function searchAlpineConfig(): array
    {
        return [
            'selected' => $this->categories[0] ?? null,
            'startsAt' => $this->search['defaultStartsAt'],
            'rangeRequired' => $this->search['rangeRequired'],
            'slotRequired' => $this->search['slotRequired'],
        ];
    }

    public function hasCampaigns(): bool
    {
        return $this->campaigns->isNotEmpty();
    }

    public function hasMuthowifs(): bool
    {
        return $this->muthowifCards !== [];
    }

    public function hasGallery(): bool
    {
        return $this->galleryItems !== [];
    }

    public function hasArticles(): bool
    {
        return $this->articleCards !== [];
    }

    public function hasGuideCards(): bool
    {
        return $this->guideCards !== [];
    }

    /**
     * @return list<array{key: string, label: string, type: string, url: string, icon: string, category?: string}>
     */
    private static function buildCategories(): array
    {
        return [
            [
                'key' => 'umroh',
                'label' => __('dashboard.customer_cat_umroh'),
                'type' => 'layanan',
                'url' => route('layanan.index'),
                'icon' => 'umroh',
            ],
            [
                'key' => 'mobility',
                'label' => __('dashboard.customer_cat_wheelchair'),
                'type' => 'support',
                'category' => 'mobility',
                'url' => route('layanan-pendukung.index', ['category' => 'mobility']),
                'icon' => 'mobility',
            ],
            [
                'key' => 'umrah',
                'label' => __('dashboard.customer_cat_prayer'),
                'type' => 'support',
                'category' => 'umrah',
                'url' => route('layanan-pendukung.index', ['category' => 'umrah']),
                'icon' => 'prayer',
            ],
            [
                'key' => 'other',
                'label' => __('dashboard.customer_cat_photo'),
                'type' => 'support',
                'category' => 'other',
                'url' => route('layanan-pendukung.index', ['category' => 'other']),
                'icon' => 'photo',
            ],
            [
                'key' => 'ziarah',
                'label' => __('dashboard.customer_cat_raudho'),
                'type' => 'support',
                'category' => 'ziarah',
                'url' => route('layanan-pendukung.index', ['category' => 'ziarah']),
                'icon' => 'raudho',
            ],
        ];
    }

    /**
     * @return array{name: string, href: string, photo: string, rating: string, location: ?string, tags: list<string>, price: string}
     */
    private static function mapMuthowifCard(MuthowifProfile $profile): array
    {
        $minPrice = (int) round((float) ($profile->services->min('daily_price') ?? 0));
        $rating = $profile->booking_reviews_avg_rating ?? $profile->average_rating;
        $tags = collect($profile->services ?? [])
            ->map(function ($service) {
                if (isset($service->type) && is_object($service->type) && method_exists($service->type, 'label')) {
                    return $service->type->label();
                }

                return filled($service->name ?? null) ? (string) $service->name : null;
            })
            ->filter()
            ->unique()
            ->take(3)
            ->values()
            ->all();

        return [
            'name' => $profile->user->name ?? '—',
            'href' => route('layanan.show', $profile),
            'photo' => $profile->photoUrl(),
            'rating' => $rating !== null ? number_format((float) $rating, 1) : '—',
            'location' => $profile->workLocationLabel(),
            'tags' => $tags,
            'price' => $minPrice > 0 ? 'Rp '.number_format($minPrice, 0, ',', '.') : '—',
        ];
    }

    /**
     * @return array{url: string, caption: string, href: ?string}
     */
    private static function mapGalleryItem(MuthowifPortfolioImage $image): array
    {
        $profile = $image->portfolio?->muthowifProfile;

        return [
            'url' => $image->publicUrl(),
            'caption' => $image->portfolio?->title
                ?: ($profile?->user?->name ?? __('welcome.landing_gallery_title')),
            'href' => $profile ? route('layanan.show', $profile) : null,
        ];
    }

    /**
     * @return array{title: string, excerpt: string, href: string, thumbnail: ?string}
     */
    private static function mapArticleCard(Article $article): array
    {
        $body = $article->localized('body');
        $thumbnail = null;
        if (preg_match('/<img[^>]+src=["\']([^"\']+)["\']/', $body, $m)) {
            $thumbnail = $m[1];
        }

        return [
            'title' => $article->localized('title'),
            'excerpt' => $article->localized('excerpt'),
            'href' => route('articles.show', ['slug' => $article->slug]),
            'thumbnail' => $thumbnail,
        ];
    }

    private static function resolveHelpHref(): string
    {
        if (Auth::check() && Route::has('support.index')) {
            return route('support.index');
        }

        $contactRaw = (string) (config('app.contact_whatsapp') ?: config('app.contact_phone'));
        $contactDigits = preg_replace('/\D+/', '', $contactRaw) ?? '';
        if ($contactDigits !== '') {
            return 'https://wa.me/'.$contactDigits;
        }

        if (Route::has('login') && ! Auth::check()) {
            return route('login');
        }

        return route('layanan.index');
    }

    /**
     * @return array{title: string, description: string, schema: array<string, mixed>}
     */
    private static function buildSeo(): array
    {
        return [
            'title' => 'Jasa Tour Guide Ibadah Umroh & Haji | Muthowif Terpercaya',
            'description' => 'Temukan Muthowif terbaik dan jasa tour guide ibadah Umroh & Haji terpercaya di Bayt-GO. Bandingkan rating, ulasan, harga, dan pesan langsung asisten ibadah terverifikasi Anda secara mudah.',
            'schema' => [
                '@context' => 'https://schema.org',
                '@type' => 'WebSite',
                'name' => config('app.name', 'Bayt-GO'),
                'url' => url('/'),
                'description' => 'Platform penghubung Muthowif profesional terverifikasi & jasa tour guide ibadah Umroh dan Haji.',
                'potentialAction' => [
                    '@type' => 'SearchAction',
                    'target' => url('/layanan').'?q={search_term_string}',
                    'query-input' => 'required name=search_term_string',
                ],
            ],
        ];
    }
}
