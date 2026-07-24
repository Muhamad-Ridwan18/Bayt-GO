<?php

namespace App\ViewModels\Dashboard;

use App\Enums\PaymentStatus;
use App\Models\MuthowifBooking;
use App\Models\User;
use App\Support\CustomerDashboardCache;
use App\Support\WelcomePageCache;
use App\ViewModels\Public\WelcomePageData;
use Illuminate\Support\Facades\Route;

final class CustomerDashboardPageData
{
    /**
     * @param  list<array{value: int, label: string, href: ?string}>  $statTiles
     * @param  array{service_label: string, guide_name: string, date_range: string, payment_label: string, payment_paid: bool, photo_url: ?string, href: string}|null  $nextTrip
     */
    public function __construct(
        public readonly WelcomePageData $homePage,
        public readonly array $statTiles,
        public readonly ?array $nextTrip,
        public readonly string $bookingsIndexUrl,
        public readonly string $layananIndexUrl,
        public readonly bool $hasSupportIndex,
    ) {}

    /**
     * @param  array<string, mixed>|null  $welcomeCache
     */
    public static function for(User $user, ?array $welcomeCache = null): self
    {
        $welcomeCache ??= WelcomePageCache::data();
        $stats = CustomerDashboardCache::stats($user);

        $guideCards = __('dashboard.customer_guide_cards');
        $guideCards = is_array($guideCards) ? $guideCards : [];

        $homePage = WelcomePageData::fromCache(
            cache: [
                'featuredMuthowifs' => collect($welcomeCache['featuredMuthowifs'] ?? [])->take(8),
                'latestArticles' => $welcomeCache['latestArticles'] ?? collect(),
                'activeCampaigns' => $welcomeCache['activeCampaigns'] ?? collect(),
                'galleryImages' => $welcomeCache['galleryImages'] ?? collect(),
            ],
            heroName: $user->name,
            showLandingChrome: true,
            guideCards: $guideCards,
            muthowifLimit: 8,
            galleryLimit: 8,
            articleLimit: 4,
        );

        $bookingsUrl = route('bookings.index');
        $hasSupport = Route::has('support.index');
        $supportUrl = $hasSupport ? route('support.index') : null;

        return new self(
            homePage: $homePage,
            statTiles: [
                [
                    'value' => (int) $stats['activeBookingCount'],
                    'label' => __('dashboard.customer_stat_active'),
                    'href' => $bookingsUrl,
                ],
                [
                    'value' => (int) $stats['supportOpenCount'],
                    'label' => __('dashboard.customer_stat_support'),
                    'href' => $supportUrl,
                ],
                [
                    'value' => (int) $stats['upcomingTripCount'],
                    'label' => __('dashboard.customer_stat_upcoming'),
                    'href' => $bookingsUrl,
                ],
                [
                    'value' => (int) $stats['reviewsGivenCount'],
                    'label' => __('dashboard.customer_stat_reviews'),
                    'href' => null,
                ],
            ],
            nextTrip: self::mapNextTrip($stats['nextBooking'] ?? null),
            bookingsIndexUrl: $bookingsUrl,
            layananIndexUrl: route('layanan.index'),
            hasSupportIndex: $hasSupport,
        );
    }

    /**
     * @return array{service_label: string, guide_name: string, date_range: string, payment_label: string, payment_paid: bool, photo_url: ?string, href: string}|null
     */
    private static function mapNextTrip(?MuthowifBooking $booking): ?array
    {
        if ($booking === null) {
            return null;
        }

        $locale = app()->getLocale();
        $startStr = $booking->starts_on?->locale($locale)->translatedFormat('d M Y') ?? '';
        $endStr = $booking->ends_on?->locale($locale)->translatedFormat('d M Y') ?? '';
        $paid = $booking->payment_status === PaymentStatus::Paid;

        return [
            'service_label' => $booking->service_type->label(),
            'guide_name' => $booking->muthowifProfile?->user?->name ?? '—',
            'date_range' => trim($startStr.' — '.$endStr, ' —'),
            'payment_label' => $paid
                ? __('dashboard.customer_payment_paid')
                : $booking->payment_status->label(),
            'payment_paid' => $paid,
            'photo_url' => $booking->muthowifProfile?->photoUrl(),
            'href' => route('bookings.show', $booking),
        ];
    }
}
