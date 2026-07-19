<?php

namespace App\Http\Controllers\Public;

use App\Enums\BookingStatus;
use App\Enums\SupportPackageCategory;
use App\Http\Controllers\Controller;
use App\Models\MuthowifSupportPackage;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SupportCatalogController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));
        $categoryRaw = trim((string) $request->query('category', ''));
        $category = SupportPackageCategory::tryFrom($categoryRaw);
        $startsAtRaw = trim((string) $request->query('starts_at', ''));

        $startsAt = null;
        $startsAtInput = '';
        $hasSearch = false;

        if ($startsAtRaw !== '') {
            try {
                $parsed = Carbon::parse($startsAtRaw);
                if ($parsed->lt(now())) {
                    $request->session()->now('error', __('layanan_pendukung.validation.starts_at_past'));
                } else {
                    $startsAt = $parsed;
                    $startsAtInput = $parsed->format('Y-m-d\TH:i');
                    $hasSearch = true;
                }
            } catch (\Throwable) {
                $request->session()->now('error', __('layanan_pendukung.validation.starts_at_invalid'));
            }
        }

        $baseCatalog = static fn (): Builder => MuthowifSupportPackage::query()
            ->where('is_active', true)
            ->whereHas('muthowifProfile', fn ($query) => $query->approved());

        $packages = new LengthAwarePaginator([], 0, 12, 1, [
            'path' => $request->url(),
            'query' => $request->query(),
        ]);

        if ($hasSearch && $startsAt !== null) {
            $day = $startsAt->toDateString();
            $blockingStatuses = array_map(
                static fn (BookingStatus $status) => $status->value,
                BookingStatus::blocksAvailability(),
            );

            $packages = $baseCatalog()
                ->whereDoesntHave('muthowifProfile.blockedDates', function ($blocked) use ($day): void {
                    $blocked->where('blocked_on', $day);
                })
                ->whereDoesntHave('muthowifProfile.bookings', function ($bookings) use ($day, $blockingStatuses): void {
                    $bookings->whereIn('status', $blockingStatuses)
                        ->where('starts_on', '<=', $day)
                        ->where('ends_on', '>=', $day);
                })
                ->with([
                    'muthowifProfile' => function ($query): void {
                        $query->with('user')
                            ->withMarketplaceStats()
                            ->withCount([
                                'bookings as completed_bookings_count' => static fn ($q) => $q->where('status', BookingStatus::Completed),
                            ]);
                    },
                ])
                ->when(
                    $category !== null,
                    fn ($query) => $query->where('category', $category),
                )
                ->when($q !== '', function ($query) use ($q): void {
                    $query->where(function ($inner) use ($q): void {
                        $inner->where('name', 'like', '%'.$q.'%')
                            ->orWhere('description', 'like', '%'.$q.'%')
                            ->orWhereHas('muthowifProfile.user', fn ($u) => $u->where('name', 'like', '%'.$q.'%'));
                    });
                })
                ->orderBy('sort_order')
                ->orderBy('name')
                ->paginate(12)
                ->withQueryString();
        }

        $catalogStats = [
            'packages' => $baseCatalog()->count(),
            'muthowifs' => $baseCatalog()->distinct()->count('muthowif_profile_id'),
            'avg_rating' => round((float) (DB::table('booking_reviews')
                ->whereIn('muthowif_profile_id', $baseCatalog()->select('muthowif_profile_id'))
                ->avg('rating') ?? 0), 1),
        ];

        return view('layanan-pendukung.index', [
            'packages' => $packages,
            'searchQuery' => $q,
            'activeCategory' => $category,
            'categories' => SupportPackageCategory::ordered(),
            'catalogStats' => $catalogStats,
            'hasSearch' => $hasSearch,
            'startsAt' => $startsAt,
            'startsAtInput' => $startsAtInput,
        ]);
    }

    public function show(Request $request, MuthowifSupportPackage $supportPackage): View
    {
        abort_unless($supportPackage->is_active, 404);

        $supportPackage->load([
            'muthowifProfile.user',
            'muthowifProfile.services',
        ]);
        $supportPackage->muthowifProfile?->loadCount('bookingReviews');
        $supportPackage->muthowifProfile?->loadAvg('bookingReviews', 'rating');

        abort_unless($supportPackage->muthowifProfile?->isApproved(), 404);

        return view('layanan-pendukung.show', [
            'package' => $supportPackage,
            'profile' => $supportPackage->muthowifProfile,
            'startsAtInput' => $this->resolveStartsAtInput($request),
        ]);
    }

    public function book(Request $request, MuthowifSupportPackage $supportPackage): View
    {
        abort_unless($supportPackage->is_active, 404);

        $supportPackage->load(['muthowifProfile.user']);
        abort_unless($supportPackage->muthowifProfile?->isApproved(), 404);

        return view('layanan-pendukung.book', [
            'package' => $supportPackage,
            'profile' => $supportPackage->muthowifProfile,
            'startsAtInput' => $this->resolveStartsAtInput($request),
        ]);
    }

    private function resolveStartsAtInput(Request $request): string
    {
        $raw = trim((string) $request->query('starts_at', ''));
        if ($raw === '') {
            return '';
        }

        try {
            $parsed = Carbon::parse($raw);
            if ($parsed->lt(now())) {
                return '';
            }

            return $parsed->format('Y-m-d\TH:i');
        } catch (\Throwable) {
            return '';
        }
    }
}
