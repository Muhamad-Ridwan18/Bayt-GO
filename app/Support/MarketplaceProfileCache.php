<?php

namespace App\Support;

use App\Enums\BookingStatus;
use App\Enums\MuthowifVerificationStatus;
use App\Models\BookingReview;
use App\Models\MuthowifBlockedDate;
use App\Models\MuthowifPortfolio;
use App\Models\MuthowifProfile;
use App\Models\MuthowifService;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

final class MarketplaceProfileCache
{
    public static function resolvePublic(string $value): MuthowifProfile
    {
        $seconds = max(60, (int) config('marketplace.profile_resolve_cache_seconds', 600));
        $cacheKey = Str::isUuid($value)
            ? 'muthowif:public:id:'.$value
            : 'muthowif:public:slug:'.$value;

        /** @var array<string, mixed>|null $attrs */
        $attrs = Cache::get($cacheKey);
        if (is_array($attrs) && $attrs !== []) {
            return self::profileFromAttributes($attrs);
        }

        $query = MuthowifProfile::query()
            ->where('verification_status', MuthowifVerificationStatus::Approved);

        if (Str::isUuid($value)) {
            $query->whereKey($value);
        } else {
            $query->where('slug', $value);
        }

        $profile = $query->firstOrFail();
        Cache::put($cacheKey, $profile->getAttributes(), now()->addSeconds($seconds));

        return $profile;
    }

    public static function forShow(MuthowifProfile $profile): MuthowifProfile
    {
        $seconds = max(60, (int) config('marketplace.profile_show_cache_seconds', 600));
        $cacheKey = sprintf(
            'muthowif:show:%s:%s',
            $profile->getKey(),
            $profile->updated_at?->getTimestamp() ?? 0,
        );

        /** @var array<string, mixed>|null $snapshot */
        $snapshot = Cache::get($cacheKey);
        if (is_array($snapshot) && ($snapshot['profile']['id'] ?? null) === $profile->getKey()) {
            return self::hydrateShowProfile($snapshot);
        }

        $loaded = self::loadShowRelations($profile);
        Cache::put($cacheKey, self::exportShowProfile($loaded), now()->addSeconds($seconds));

        return $loaded;
    }

    public static function forget(MuthowifProfile $profile): void
    {
        if (filled($profile->slug)) {
            Cache::forget('muthowif:public:slug:'.$profile->slug);
        }

        Cache::forget('muthowif:public:id:'.$profile->getKey());
    }

    private static function loadShowRelations(MuthowifProfile $profile): MuthowifProfile
    {
        $portfolioPreviewLimit = max(1, (int) config('marketplace.profile_portfolio_preview_limit', 3));
        $portfolioImagesLimit = max(1, (int) config('marketplace.profile_portfolio_images_limit', 12));
        $blockedDatesLimit = max(1, (int) config('marketplace.profile_blocked_dates_limit', 60));
        $today = now()->toDateString();

        $profile->load([
            'user',
            'services.addOns',
            'portfolios' => fn ($q) => $q
                ->with(['images' => fn ($images) => $images->orderBy('sort_order')->limit($portfolioImagesLimit)])
                ->orderBy('sort_order')
                ->orderByDesc('created_at')
                ->limit($portfolioPreviewLimit),
            'bookingReviews' => fn ($q) => $q
                ->with('customer:id,name')
                ->latest()
                ->limit(10),
            'blockedDates' => fn ($q) => $q
                ->where('blocked_on', '>=', $today)
                ->orderBy('blocked_on')
                ->limit($blockedDatesLimit),
        ]);
        $profile->loadCount([
            'bookings as confirmed_bookings_count' => static fn ($q) => $q->where('status', BookingStatus::Confirmed),
            'bookingReviews',
            'portfolios',
            'blockedDates' => static fn ($q) => $q->where('blocked_on', '>=', $today),
        ]);
        $profile->loadAvg('bookingReviews', 'rating');

        return $profile;
    }

    /**
     * @return array<string, mixed>
     */
    private static function exportShowProfile(MuthowifProfile $profile): array
    {
        return [
            'profile' => $profile->getAttributes(),
            'user' => $profile->user?->getAttributes(),
            'services' => $profile->services->map(static fn (MuthowifService $service): array => [
                'service' => $service->getAttributes(),
                'add_ons' => $service->addOns->map->getAttributes()->values()->all(),
            ])->values()->all(),
            'portfolios' => $profile->portfolios->map(static fn (MuthowifPortfolio $portfolio): array => [
                'portfolio' => $portfolio->getAttributes(),
                'images' => $portfolio->images->map->getAttributes()->values()->all(),
            ])->values()->all(),
            'booking_reviews' => $profile->bookingReviews->map(static fn (BookingReview $review): array => [
                'review' => $review->getAttributes(),
                'customer' => $review->customer?->getAttributes(),
            ])->values()->all(),
            'blocked_dates' => $profile->blockedDates->map->getAttributes()->values()->all(),
            'counts' => [
                'confirmed_bookings_count' => (int) ($profile->confirmed_bookings_count ?? 0),
                'booking_reviews_count' => (int) ($profile->booking_reviews_count ?? 0),
                'portfolios_count' => (int) ($profile->portfolios_count ?? 0),
                'blocked_dates_count' => (int) ($profile->blocked_dates_count ?? 0),
            ],
            'booking_reviews_avg_rating' => $profile->booking_reviews_avg_rating,
        ];
    }

    /**
     * @param  array<string, mixed>  $snapshot
     */
    private static function hydrateShowProfile(array $snapshot): MuthowifProfile
    {
        /** @var array<string, mixed> $profileAttrs */
        $profileAttrs = $snapshot['profile'];
        $profile = self::profileFromAttributes($profileAttrs);

        if (is_array($snapshot['user'] ?? null)) {
            $user = (new User)->newFromBuilder($snapshot['user']);
            $user->exists = true;
            $profile->setRelation('user', $user);
        }

        $services = collect($snapshot['services'] ?? [])->map(static function (array $row): MuthowifService {
            $service = (new MuthowifService)->newFromBuilder($row['service']);
            $service->exists = true;
            $addOns = collect($row['add_ons'] ?? [])->map(static function (array $attrs) {
                $model = new \App\Models\MuthowifServiceAddOn;
                $model = $model->newFromBuilder($attrs);
                $model->exists = true;

                return $model;
            });
            $service->setRelation('addOns', new EloquentCollection($addOns->all()));

            return $service;
        });
        $profile->setRelation('services', new EloquentCollection($services->all()));

        $portfolios = collect($snapshot['portfolios'] ?? [])->map(static function (array $row): MuthowifPortfolio {
            $portfolio = (new MuthowifPortfolio)->newFromBuilder($row['portfolio']);
            $portfolio->exists = true;
            $images = collect($row['images'] ?? [])->map(static function (array $attrs) {
                $image = new \App\Models\MuthowifPortfolioImage;
                $image = $image->newFromBuilder($attrs);
                $image->exists = true;

                return $image;
            });
            $portfolio->setRelation('images', new EloquentCollection($images->all()));

            return $portfolio;
        });
        $profile->setRelation('portfolios', new EloquentCollection($portfolios->all()));

        $reviews = collect($snapshot['booking_reviews'] ?? [])->map(static function (array $row): BookingReview {
            $review = (new BookingReview)->newFromBuilder($row['review']);
            $review->exists = true;
            if (is_array($row['customer'] ?? null)) {
                $customer = (new User)->newFromBuilder($row['customer']);
                $customer->exists = true;
                $review->setRelation('customer', $customer);
            }

            return $review;
        });
        $profile->setRelation('bookingReviews', new EloquentCollection($reviews->all()));

        $blockedDates = collect($snapshot['blocked_dates'] ?? [])->map(static function (array $attrs): MuthowifBlockedDate {
            $date = (new MuthowifBlockedDate)->newFromBuilder($attrs);
            $date->exists = true;

            return $date;
        });
        $profile->setRelation('blockedDates', new EloquentCollection($blockedDates->all()));

        $counts = $snapshot['counts'] ?? [];
        $profile->confirmed_bookings_count = $counts['confirmed_bookings_count'] ?? 0;
        $profile->booking_reviews_count = $counts['booking_reviews_count'] ?? 0;
        $profile->portfolios_count = $counts['portfolios_count'] ?? 0;
        $profile->blocked_dates_count = $counts['blocked_dates_count'] ?? 0;
        $profile->booking_reviews_avg_rating = $snapshot['booking_reviews_avg_rating'] ?? null;

        return $profile;
    }

    /**
     * @param  array<string, mixed>  $attrs
     */
    private static function profileFromAttributes(array $attrs): MuthowifProfile
    {
        $profile = (new MuthowifProfile)->newFromBuilder($attrs);
        $profile->exists = true;

        return $profile;
    }
}
