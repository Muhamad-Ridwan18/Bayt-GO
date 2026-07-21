<?php

namespace App\ViewModels\Layanan;

use App\Support\MarketplaceSearchCache;
use App\Support\WelcomeLanding;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\MessageBag;

final class LayananIndexPageData
{
    private const MAX_RANGE_DAYS = 90;

    /**
     * @param  list<MarketplaceProfileCardData>  $profileCards
     */
    public function __construct(
        public readonly LengthAwarePaginator $profiles,
        public readonly string $searchQuery,
        public readonly string $startDate,
        public readonly string $endDate,
        public readonly bool $hasDateSearch,
        public readonly ?MessageBag $dateErrors,
        public readonly ?string $rangeLabel,
        public readonly string $listQueryString,
        public readonly string $heroBgUrl,
        public readonly string $seoTitle,
        public readonly string $seoDesc,
        public readonly bool $hasActiveFilters,
        public readonly array $profileCards,
    ) {}

    public static function make(Request $request): self
    {
        $q = trim((string) $request->query('q', ''));
        $startRaw = $request->query('start_date');
        $endRaw = $request->query('end_date');
        $hasDateSearch = filled($startRaw);

        if (! $hasDateSearch) {
            return self::build(
                request: $request,
                profiles: self::emptyPaginator($request),
                searchQuery: $q,
                startDate: '',
                endDate: '',
                hasDateSearch: false,
                dateErrors: null,
                rangeLabel: null,
            );
        }

        $endEffective = filled($endRaw) ? $endRaw : $startRaw;

        $validator = Validator::make(
            [
                'start_date' => $startRaw,
                'end_date' => $endEffective,
            ],
            [
                'start_date' => ['required', 'date'],
                'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            ],
            [
                'end_date.after_or_equal' => 'Tanggal selesai harus sama atau setelah tanggal mulai.',
            ]
        );

        if ($validator->fails()) {
            return self::build(
                request: $request,
                profiles: self::emptyPaginator($request),
                searchQuery: $q,
                startDate: (string) $startRaw,
                endDate: (string) ($endRaw ?? ''),
                hasDateSearch: true,
                dateErrors: $validator->errors(),
                rangeLabel: null,
            );
        }

        $start = Carbon::parse($startRaw)->startOfDay();
        $end = Carbon::parse($endEffective)->startOfDay();

        if ($start->lt(now()->startOfDay())) {
            return self::build(
                request: $request,
                profiles: self::emptyPaginator($request),
                searchQuery: $q,
                startDate: $start->toDateString(),
                endDate: $end->toDateString(),
                hasDateSearch: true,
                dateErrors: new MessageBag(['start_date' => ['Tanggal mulai tidak boleh sebelum hari ini.']]),
                rangeLabel: null,
            );
        }

        if ($start->diffInDays($end) > self::MAX_RANGE_DAYS) {
            return self::build(
                request: $request,
                profiles: self::emptyPaginator($request),
                searchQuery: $q,
                startDate: $start->toDateString(),
                endDate: $end->toDateString(),
                hasDateSearch: true,
                dateErrors: new MessageBag(['end_date' => ['Rentang maksimal '.self::MAX_RANGE_DAYS.' hari.']]),
                rangeLabel: null,
            );
        }

        $startStr = $start->toDateString();
        $endStr = $end->toDateString();
        $rangeLabel = $start->format('d/m/Y').' – '.$end->format('d/m/Y');

        $profiles = MarketplaceSearchCache::paginate($request, $startStr, $endStr, $q)
            ->withQueryString();

        return self::build(
            request: $request,
            profiles: $profiles,
            searchQuery: $q,
            startDate: $startStr,
            endDate: $endStr,
            hasDateSearch: true,
            dateErrors: null,
            rangeLabel: $rangeLabel,
        );
    }

    private static function build(
        Request $request,
        LengthAwarePaginator $profiles,
        string $searchQuery,
        string $startDate,
        string $endDate,
        bool $hasDateSearch,
        ?MessageBag $dateErrors,
        ?string $rangeLabel,
    ): self {
        $listQuery = array_filter([
            'start_date' => $startDate !== '' ? $startDate : null,
            'end_date' => $endDate !== '' ? $endDate : null,
            'q' => $searchQuery !== '' ? $searchQuery : null,
        ]);
        $listQueryString = http_build_query($listQuery);

        $seoTitle = __('layanan.page_title').' | Jasa Tour Guide Umroh & Haji Terpercaya';
        if ($searchQuery !== '') {
            $seoTitle = "Cari Jasa Tour Guide Umroh/Haji '".e($searchQuery)."' — Muthowif Terverifikasi";
        }

        $profileCards = [];
        if ($dateErrors === null || $dateErrors->isEmpty()) {
            foreach ($profiles as $profile) {
                $profileCards[] = MarketplaceProfileCardData::fromProfile($profile, $listQueryString, $rangeLabel);
            }
        }

        return new self(
            profiles: $profiles,
            searchQuery: $searchQuery,
            startDate: $startDate,
            endDate: $endDate,
            hasDateSearch: $hasDateSearch,
            dateErrors: $dateErrors,
            rangeLabel: $rangeLabel,
            listQueryString: $listQueryString,
            heroBgUrl: WelcomeLanding::resolvedHeroImageUrl(),
            seoTitle: $seoTitle,
            seoDesc: 'Temukan dan sewa jasa Muthowif profesional terverifikasi serta asisten tour guide ibadah Umroh & Haji terbaik di Bayt-GO. Bandingkan tarif harian, rating, dan ulasan.',
            hasActiveFilters: $startDate !== '' || $searchQuery !== '',
            profileCards: $profileCards,
        );
    }

    private static function emptyPaginator(Request $request): LengthAwarePaginator
    {
        return new LengthAwarePaginator([], 0, 12, 1, [
            'path' => $request->url(),
            'query' => $request->query(),
            'pageName' => 'page',
        ]);
    }
}
