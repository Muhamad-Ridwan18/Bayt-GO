<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\MuthowifProfile;
use App\Support\MarketplaceProfileCache;
use App\Support\MarketplaceSearchCache;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Support\StoredImageResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\MessageBag;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class MuthowifDirectoryController extends Controller
{
    private const MAX_RANGE_DAYS = 90;

    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));
        $startRaw = $request->query('start_date');
        $endRaw = $request->query('end_date');

        $hasDateSearch = filled($startRaw);

        if (! $hasDateSearch) {
            return view('layanan.index', [
                'profiles' => $this->emptyPaginator($request),
                'searchQuery' => $q,
                'startDate' => '',
                'endDate' => '',
                'hasDateSearch' => false,
                'dateErrors' => null,
                'rangeLabel' => null,
            ]);
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
            return view('layanan.index', [
                'profiles' => $this->emptyPaginator($request),
                'searchQuery' => $q,
                'startDate' => (string) $startRaw,
                'endDate' => (string) ($endRaw ?? ''),
                'hasDateSearch' => true,
                'dateErrors' => $validator->errors(),
                'rangeLabel' => null,
            ]);
        }

        $start = Carbon::parse($startRaw)->startOfDay();
        $end = Carbon::parse($endEffective)->startOfDay();

        if ($start->lt(now()->startOfDay())) {
            return view('layanan.index', [
                'profiles' => $this->emptyPaginator($request),
                'searchQuery' => $q,
                'startDate' => $start->toDateString(),
                'endDate' => $end->toDateString(),
                'hasDateSearch' => true,
                'dateErrors' => new MessageBag(['start_date' => ['Tanggal mulai tidak boleh sebelum hari ini.']]),
                'rangeLabel' => null,
            ]);
        }

        if ($start->diffInDays($end) > self::MAX_RANGE_DAYS) {
            return view('layanan.index', [
                'profiles' => $this->emptyPaginator($request),
                'searchQuery' => $q,
                'startDate' => $start->toDateString(),
                'endDate' => $end->toDateString(),
                'hasDateSearch' => true,
                'dateErrors' => new MessageBag(['end_date' => ['Rentang maksimal '.self::MAX_RANGE_DAYS.' hari.']]),
                'rangeLabel' => null,
            ]);
        }

        $startStr = $start->toDateString();
        $endStr = $end->toDateString();

        $profiles = MarketplaceSearchCache::paginate($request, $startStr, $endStr, $q)
            ->withQueryString();

        return view('layanan.index', [
            'profiles' => $profiles,
            'searchQuery' => $q,
            'startDate' => $startStr,
            'endDate' => $endStr,
            'hasDateSearch' => true,
            'dateErrors' => null,
            'rangeLabel' => $start->format('d/m/Y').' – '.$end->format('d/m/Y'),
        ]);
    }

    public function show(Request $request, MuthowifProfile $publicProfile): View
    {
        $publicProfile = MarketplaceProfileCache::forShow($publicProfile);

        $startDate = (string) $request->query('start_date', '');
        $endDate = (string) $request->query('end_date', '');
        $bookingIntent = $this->bookingIntentForProfile($request, $publicProfile, $startDate, $endDate);

        return view('layanan.show', [
            'profile' => $publicProfile,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'bookingIntent' => $bookingIntent,
        ]);
    }

    public function booking(Request $request, MuthowifProfile $publicProfile): View
    {
        $publicProfile->load([
            'user',
            'services.addOns',
        ]);
        $publicProfile->loadCount(['bookingReviews']);
        $publicProfile->loadAvg('bookingReviews', 'rating');

        $startDate = (string) $request->query('start_date', '');
        $endDate = (string) $request->query('end_date', '');
        $bookingIntent = $this->bookingIntentForProfile($request, $publicProfile, $startDate, $endDate);

        return view('layanan.book', [
            'page' => \App\ViewModels\Layanan\LayananBookPageData::make(
                $request,
                $publicProfile,
                $bookingIntent,
                $startDate,
                $endDate,
            ),
        ]);
    }

    /**
     * @return array{can_submit: bool, reason: string|null, start: ?string, end: ?string}
     */
    private function bookingIntentForProfile(Request $request, MuthowifProfile $profile, string $startDate, string $endDate): array
    {
        $empty = ['can_submit' => false, 'reason' => null, 'start' => null, 'end' => null];

        $user = $request->user();
        if (! $user?->isCustomer()) {
            return array_merge($empty, [
                'reason' => $user ? 'not_customer' : 'guest',
                'start' => $startDate !== '' ? $startDate : null,
                'end' => $endDate !== '' ? $endDate : null,
            ]);
        }

        if ($startDate === '') {
            return array_merge($empty, ['reason' => 'missing_dates']);
        }

        $endEffective = $endDate !== '' ? $endDate : $startDate;

        $validator = Validator::make(
            ['start_date' => $startDate, 'end_date' => $endEffective],
            [
                'start_date' => ['required', 'date'],
                'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            ],
            ['end_date.after_or_equal' => 'Tanggal selesai harus sama atau setelah tanggal mulai.']
        );

        if ($validator->fails()) {
            return array_merge($empty, ['reason' => 'invalid_dates', 'start' => $startDate, 'end' => $endDate]);
        }

        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endEffective)->startOfDay();

        if ($start->lt(now()->startOfDay())) {
            return array_merge($empty, ['reason' => 'past_start', 'start' => $startDate, 'end' => $endEffective]);
        }

        if ($start->diffInDays($end) > self::MAX_RANGE_DAYS) {
            return array_merge($empty, ['reason' => 'range_too_long', 'start' => $startDate, 'end' => $endEffective]);
        }

        if (! $profile->isJadwalAvailableForRange($start, $end)) {
            return array_merge($empty, [
                'reason' => 'jadwal_tidak_tersedia',
                'start' => $start->toDateString(),
                'end' => $end->toDateString(),
            ]);
        }

        return [
            'can_submit' => true,
            'reason' => null,
            'start' => $start->toDateString(),
            'end' => $end->toDateString(),
        ];
    }

    public function portfolioIndex(Request $request, MuthowifProfile $publicProfile): View
    {
        $publicProfile->load(['user']);

        $portfolios = $publicProfile->portfolios()
            ->with('images')
            ->orderBy('sort_order')
            ->orderByDesc('created_at')
            ->paginate(9)
            ->withQueryString();

        return view('layanan.portfolio.index', [
            'profile' => $publicProfile,
            'portfolios' => $portfolios,
        ]);
    }

    public function photo(MuthowifProfile $publicProfile): Response
    {
        if (! filled($publicProfile->photo_path)) {
            abort(404);
        }

        if (MuthowifProfile::photoPathIsExternalUrl($publicProfile->photo_path)) {
            return redirect()->away($publicProfile->photo_path, 302);
        }

        return StoredImageResponse::fromDisk('local', $publicProfile->photo_path, visibility: 'public');
    }

    public function portfolioPhoto(\App\Models\MuthowifPortfolio $portfolio): Response
    {
        $coverPath = $portfolio->coverImagePath();
        if (! is_string($coverPath) || $coverPath === '') {
            abort(404);
        }

        return StoredImageResponse::fromDisk('local', $coverPath, visibility: 'public');
    }

    public function portfolioImage(\App\Models\MuthowifPortfolioImage $image): Response
    {
        $image->loadMissing('portfolio.muthowifProfile');
        if (! $image->portfolio?->muthowifProfile?->isApproved()) {
            abort(404);
        }

        return StoredImageResponse::fromDisk('local', $image->path, visibility: 'public');
    }

    private function emptyPaginator(Request $request): LengthAwarePaginator
    {
        return new LengthAwarePaginator([], 0, 12, 1, [
            'path' => $request->url(),
            'query' => $request->query(),
            'pageName' => 'page',
        ]);
    }

}
