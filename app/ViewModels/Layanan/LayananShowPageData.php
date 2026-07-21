<?php

namespace App\ViewModels\Layanan;

use App\Enums\MuthowifServiceType;
use App\Models\MuthowifProfile;
use App\Models\MuthowifService;
use App\Support\IndonesianNumber;
use Carbon\Carbon;
use Illuminate\Http\Request;

final class LayananShowPageData
{
    /**
     * @param  array{can_submit: bool, reason: string|null, start: ?string, end: ?string}  $bookingIntent
     * @param  array<string, mixed>  $bookQueryParams
     * @param  array<string, mixed>  $indexQuery
     * @param  array<string, mixed>  $muthowifSchema
     * @param  list<array{name: string, price: string}>  $addonCards
     * @param  list<array{date: string, note: ?string}>  $blockedDateRows
     */
    public function __construct(
        public readonly MuthowifProfile $profile,
        public readonly ?MuthowifService $group,
        public readonly ?MuthowifService $private,
        public readonly array $bookingIntent,
        public readonly int $reviewsCount,
        public readonly ?float $avgRating,
        public readonly int $confirmedBookings,
        public readonly int $blockedCount,
        public readonly ?string $searchRangeLabel,
        public readonly string $fallbackSvg,
        public readonly string $experienceStat,
        public readonly string $pilgrimStat,
        public readonly string $langStat,
        public readonly ?string $workLocation,
        public readonly ?float $minPrice,
        public readonly array $bookQueryParams,
        public readonly string $bookingPageUrl,
        public readonly ?string $groupBookUrl,
        public readonly ?string $privateBookUrl,
        public readonly bool $canBook,
        public readonly array $indexQuery,
        public readonly string $seoTitle,
        public readonly string $seoDesc,
        public readonly array $muthowifSchema,
        public readonly array $addonCards,
        public readonly array $blockedDateRows,
        public readonly string $muthowifName,
        public readonly array $languageList,
        public readonly string $startDate,
        public readonly string $endDate,
    ) {}

    /**
     * @param  array{can_submit: bool, reason: string|null, start: ?string, end: ?string}  $bookingIntent
     */
    public static function make(
        Request $request,
        MuthowifProfile $profile,
        array $bookingIntent,
        string $startDate,
        string $endDate,
    ): self {
        $group = $profile->services->firstWhere('type', MuthowifServiceType::Group);
        $private = $profile->services->firstWhere('type', MuthowifServiceType::PrivateJamaah);
        $muthowifName = (string) $profile->user->name;

        $reviewsCount = (int) ($profile->booking_reviews_count ?? 0);
        $avgRating = $profile->booking_reviews_avg_rating !== null
            ? round((float) $profile->booking_reviews_avg_rating, 1)
            : null;
        $confirmedBookings = (int) ($profile->confirmed_bookings_count ?? 0);
        $blockedCount = (int) ($profile->blocked_dates_count ?? $profile->blockedDates->count());

        $bookQueryParams = self::bookQueryParams($request, $startDate, $endDate);
        $indexQuery = array_filter([
            'start_date' => $startDate !== '' ? $startDate : null,
            'end_date' => $endDate !== '' ? $endDate : null,
            'q' => $request->query('q'),
            'service_type' => $request->query('service_type'),
            'pilgrim_count' => $request->query('pilgrim_count'),
        ], fn ($v) => filled($v));

        $bookingPageUrl = route('layanan.book', array_merge(['publicProfile' => $profile], $bookQueryParams));
        $canBook = ($bookingIntent['can_submit'] ?? false) && ($group || $private);

        $prices = collect([$group?->daily_price, $private?->daily_price])->filter();
        $minPrice = $prices->isNotEmpty() ? (float) $prices->min() : null;

        $workExperiences = $profile->workExperiencesForDisplay();
        $langList = $profile->languagesForDisplay();

        $blockedDateRows = $profile->blockedDates
            ->map(static fn ($bd): array => [
                'date' => $bd->blocked_on->format('d/m/Y'),
                'note' => filled($bd->note) ? (string) $bd->note : null,
            ])
            ->all();

        return new self(
            profile: $profile,
            group: $group,
            private: $private,
            bookingIntent: $bookingIntent,
            reviewsCount: $reviewsCount,
            avgRating: $avgRating,
            confirmedBookings: $confirmedBookings,
            blockedCount: $blockedCount,
            searchRangeLabel: self::searchRangeLabel($startDate, $endDate),
            fallbackSvg: self::fallbackAvatarSvg($muthowifName),
            experienceStat: $workExperiences[0] ?? '—',
            pilgrimStat: self::pilgrimStat($confirmedBookings),
            langStat: count($langList) > 0
                ? __('marketplace.show.stat_languages_count', ['count' => count($langList)])
                : '—',
            workLocation: $profile->workLocationLabel(),
            minPrice: $minPrice,
            bookQueryParams: $bookQueryParams,
            bookingPageUrl: $bookingPageUrl,
            groupBookUrl: $group
                ? route('layanan.book', array_merge(['publicProfile' => $profile], $bookQueryParams, ['service_type' => 'group']))
                : null,
            privateBookUrl: $private
                ? route('layanan.book', array_merge(['publicProfile' => $profile], $bookQueryParams, ['service_type' => 'private']))
                : null,
            canBook: $canBook,
            indexQuery: $indexQuery,
            seoTitle: 'Muthowif '.$muthowifName.' — Jasa Tour Guide Umroh & Haji Terpercaya',
            seoDesc: 'Pesan jasa Muthowif '.$muthowifName.' di Bayt-GO. Tour guide profesional terverifikasi untuk memandu ibadah Umroh & Haji Anda secara khusyuk dan aman.',
            muthowifSchema: self::schema($profile, $muthowifName, $minPrice, $reviewsCount, $avgRating),
            addonCards: self::addonCards($group, $private),
            blockedDateRows: $blockedDateRows,
            muthowifName: $muthowifName,
            languageList: $langList,
            startDate: $startDate,
            endDate: $endDate,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private static function bookQueryParams(Request $request, string $startDate, string $endDate): array
    {
        return array_filter([
            'start_date' => $startDate !== '' ? $startDate : null,
            'end_date' => $endDate !== '' ? $endDate : null,
            'service_type' => is_string($request->query('service_type')) && in_array($request->query('service_type'), ['group', 'private'], true)
                ? $request->query('service_type')
                : null,
            'pilgrim_count' => is_numeric($request->query('pilgrim_count')) && (int) $request->query('pilgrim_count') > 0
                ? (string) (int) $request->query('pilgrim_count')
                : null,
        ], fn ($v) => filled($v));
    }

    private static function searchRangeLabel(string $startDate, string $endDate): ?string
    {
        if ($startDate === '') {
            return null;
        }

        try {
            $endEff = $endDate !== '' ? $endDate : $startDate;

            return Carbon::parse($startDate)->format('d/m/Y').' – '.Carbon::parse($endEff)->format('d/m/Y');
        } catch (\Throwable) {
            return null;
        }
    }

    private static function fallbackAvatarSvg(string $name): string
    {
        $initial = mb_substr($name, 0, 1);

        return 'data:image/svg+xml,'.rawurlencode(
            '<svg xmlns="http://www.w3.org/2000/svg" width="128" height="128"><rect fill="#e2e8f0" width="128" height="128"/><text x="50%" y="55%" dominant-baseline="middle" text-anchor="middle" font-size="48" fill="#475569">'
            .htmlspecialchars($initial, ENT_XML1 | ENT_QUOTES, 'UTF-8')
            .'</text></svg>'
        );
    }

    private static function pilgrimStat(int $confirmedBookings): string
    {
        if ($confirmedBookings >= 500) {
            return __('marketplace.show.stat_pilgrims_count', ['count' => '500']);
        }

        if ($confirmedBookings > 0) {
            return __('marketplace.show.stat_pilgrims_few', ['count' => $confirmedBookings]);
        }

        return '—';
    }

    /**
     * @return list<array{name: string, price: string}>
     */
    private static function addonCards(?MuthowifService $group, ?MuthowifService $private): array
    {
        $allAddons = collect();
        if ($private) {
            $allAddons = $allAddons->merge($private->addOns);
        }
        if ($group) {
            $allAddons = $allAddons->merge($group->addOns ?? collect());
        }

        return $allAddons->unique('id')->values()->map(static fn ($addon): array => [
            'name' => (string) $addon->name,
            'price' => IndonesianNumber::formatThousands((string) (int) $addon->price),
        ])->all();
    }

    /**
     * @return array<string, mixed>
     */
    private static function schema(
        MuthowifProfile $profile,
        string $muthowifName,
        ?float $minPrice,
        int $reviewsCount,
        ?float $avgRating,
    ): array {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'LocalBusiness',
            'name' => $muthowifName,
            'image' => $profile->photoUrl(),
            'description' => 'Jasa Muthowif profesional & tour guide ibadah Umroh dan Haji oleh '.$muthowifName.' di Bayt-GO. Bandingkan rating, ulasan, dan pesan langsung.',
            'url' => route('layanan.show', $profile),
            'priceRange' => $minPrice ? 'IDR '.number_format($minPrice, 0, ',', '.') : 'Hubungi Kontak',
            'address' => [
                '@type' => 'PostalAddress',
                'addressCountry' => 'ID',
            ],
        ];

        if ($reviewsCount > 0 && $avgRating !== null) {
            $schema['aggregateRating'] = [
                '@type' => 'AggregateRating',
                'ratingValue' => number_format((float) $avgRating, 1),
                'reviewCount' => $reviewsCount,
                'bestRating' => '5',
                'worstRating' => '1',
            ];
        }

        return $schema;
    }
}
