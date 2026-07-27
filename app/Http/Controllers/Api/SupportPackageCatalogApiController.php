<?php

namespace App\Http\Controllers\Api;

use App\Enums\BookingStatus;
use App\Enums\SupportPackageCategory;
use App\Http\Controllers\Controller;
use App\Models\MuthowifProfile;
use App\Models\MuthowifSupportPackage;
use App\Support\ApiMediaUrl;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SupportPackageCatalogApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));
        $categoryRaw = trim((string) $request->query('category', ''));
        $category = SupportPackageCategory::tryFrom($categoryRaw)?->hubCategory();

        if ($category === null) {
            return response()->json([
                'message' => 'Kategori wajib diisi.',
                'errors' => ['category' => ['Pilih kategori layanan pendukung.']],
                'categories' => $this->categoriesPayload(),
            ], 422);
        }

        $startsAtRaw = trim((string) $request->query('starts_at', ''));
        $startsAt = null;
        $startsAtInput = '';
        $hasSearch = false;

        if ($startsAtRaw !== '') {
            try {
                $parsed = Carbon::parse($startsAtRaw);
                if ($parsed->lt(now())) {
                    return response()->json([
                        'message' => __('layanan_pendukung.validation.starts_at_past'),
                        'errors' => ['starts_at' => [__('layanan_pendukung.validation.starts_at_past')]],
                    ], 422);
                }
                $startsAt = $parsed;
                $startsAtInput = $parsed->format('Y-m-d\TH:i');
                $hasSearch = true;
            } catch (\Throwable) {
                return response()->json([
                    'message' => __('layanan_pendukung.validation.starts_at_invalid'),
                    'errors' => ['starts_at' => [__('layanan_pendukung.validation.starts_at_invalid')]],
                ], 422);
            }
        }

        $baseCatalog = static fn () => MuthowifSupportPackage::query()
            ->where('is_active', true)
            ->whereHas('muthowifProfile', fn ($query) => $query->approved());

        $data = [];
        $meta = [
            'current_page' => 1,
            'last_page' => 1,
            'per_page' => 12,
            'total' => 0,
        ];

        if ($hasSearch && $startsAt !== null) {
            $day = $startsAt->toDateString();
            $blockingStatuses = array_map(
                static fn (BookingStatus $status) => $status->value,
                BookingStatus::blocksAvailability(),
            );

            $query = $baseCatalog()
                ->whereDoesntHave('muthowifProfile.blockedDates', function ($blocked) use ($day): void {
                    $blocked->where('blocked_on', $day);
                })
                ->whereDoesntHave('muthowifProfile.bookings', function ($bookings) use ($day, $blockingStatuses): void {
                    $bookings->whereIn('status', $blockingStatuses)
                        ->where('starts_on', '<=', $day)
                        ->where('ends_on', '>=', $day);
                })
                ->with([
                    'muthowifProfile' => function ($profileQuery): void {
                        $profileQuery->with('user')
                            ->withMarketplaceStats()
                            ->withCount([
                                'bookings as completed_bookings_count' => static fn ($q) => $q->where('status', BookingStatus::Completed),
                            ]);
                    },
                ])
                ->whereIn('category', $category->storageValues())
                ->when($q !== '', function ($inner) use ($q): void {
                    $inner->where(function ($search) use ($q): void {
                        $search->where('name', 'like', '%'.$q.'%')
                            ->orWhere('description', 'like', '%'.$q.'%')
                            ->orWhereHas('muthowifProfile.user', fn ($u) => $u->where('name', 'like', '%'.$q.'%'));
                    });
                });

            $paginator = MuthowifProfile::orderRelatedByMarketplaceRanking(
                $query,
                'muthowif_support_packages.muthowif_profile_id',
            )->paginate(12);

            $data = $paginator->getCollection()->map(fn (MuthowifSupportPackage $p) => $this->packagePayload($p))->values()->all();
            $meta = [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ];
        }

        $catalogStats = [
            'packages' => $baseCatalog()->count(),
            'muthowifs' => $baseCatalog()->distinct()->count('muthowif_profile_id'),
            'avg_rating' => round((float) (DB::table('booking_reviews')
                ->whereIn('muthowif_profile_id', $baseCatalog()->select('muthowif_profile_id'))
                ->avg('rating') ?? 0), 1),
        ];

        return response()->json([
            'data' => $data,
            'meta' => $meta,
            'filters' => [
                'category' => $category->value,
                'q' => $q,
                'starts_at' => $startsAt?->toIso8601String(),
                'starts_at_input' => $startsAtInput,
                'has_search' => $hasSearch,
            ],
            'categories' => $this->categoriesPayload(),
            'catalog_stats' => $catalogStats,
        ]);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $package = MuthowifSupportPackage::query()
            ->whereKey($id)
            ->where('is_active', true)
            ->with([
                'muthowifProfile' => function ($query): void {
                    $query->with('user')
                        ->withCount('bookingReviews')
                        ->withAvg('bookingReviews', 'rating');
                },
            ])
            ->firstOrFail();

        abort_unless($package->muthowifProfile?->isApproved(), 404);

        return response()->json([
            'data' => $this->packagePayload($package, true),
            'starts_at_input' => $this->resolveStartsAtInput($request),
        ]);
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function categoriesPayload(): array
    {
        return collect(SupportPackageCategory::ordered())->map(fn (SupportPackageCategory $c) => [
            'value' => $c->value,
            'label' => $c->label(),
        ])->values()->all();
    }

    /** @return array<string, mixed> */
    private function packagePayload(MuthowifSupportPackage $package, bool $detailed = false): array
    {
        $profile = $package->muthowifProfile;
        $name = (string) ($profile?->user?->name ?? 'Muthowif');

        $payload = [
            'id' => (string) $package->getKey(),
            'name' => $package->name,
            'category' => $package->category instanceof SupportPackageCategory
                ? $package->category->hubCategory()->value
                : (string) $package->category,
            'category_label' => $package->category instanceof SupportPackageCategory
                ? $package->category->hubCategory()->label()
                : (string) $package->category,
            'description' => $package->description,
            'price' => (float) $package->price,
            'min_pilgrims' => (int) $package->min_pilgrims,
            'max_pilgrims' => (int) $package->max_pilgrims,
            'is_active' => (bool) $package->is_active,
            'muthowif' => [
                'profile_id' => (string) ($profile?->getKey() ?? ''),
                'name' => $name,
                'avatar' => $profile ? ApiMediaUrl::muthowifAvatar($profile, $name) : ApiMediaUrl::fallbackAvatar($name),
                'average_rating' => round((float) ($profile?->booking_reviews_avg_rating
                    ?? $profile?->average_rating
                    ?? 0), 1),
                'reviews_count' => (int) ($profile?->booking_reviews_count
                    ?? $profile?->reviews_count
                    ?? 0),
                'completed_bookings_count' => (int) ($profile?->completed_bookings_count ?? 0),
                'work_location' => $profile?->work_location,
            ],
        ];

        if ($detailed) {
            $payload['category_raw'] = $package->category instanceof SupportPackageCategory
                ? $package->category->value
                : (string) $package->category;
        }

        return $payload;
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
