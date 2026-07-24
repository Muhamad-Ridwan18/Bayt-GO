<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\MuthowifProfile;
use App\Support\MarketplaceProfileCache;
use App\Support\StoredImageResponse;
use App\ViewModels\Layanan\LayananIndexPageData;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class MuthowifDirectoryController extends Controller
{
    private const MAX_RANGE_DAYS = 90;

    public function index(Request $request): View
    {
        return view('layanan.index', [
            'page' => LayananIndexPageData::make($request),
        ]);
    }

    public function show(Request $request, MuthowifProfile $publicProfile): View
    {
        $publicProfile = MarketplaceProfileCache::forShow($publicProfile);

        $startDate = (string) $request->query('start_date', '');
        $endDate = (string) $request->query('end_date', '');
        $bookingIntent = $this->bookingIntentForProfile($request, $publicProfile, $startDate, $endDate);

        return view('layanan.show', [
            'page' => \App\ViewModels\Layanan\LayananShowPageData::make(
                $request,
                $publicProfile,
                $bookingIntent,
                $startDate,
                $endDate,
            ),
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
}
