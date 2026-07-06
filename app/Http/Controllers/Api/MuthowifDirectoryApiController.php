<?php

namespace App\Http\Controllers\Api;

use App\Enums\BookingStatus;
use App\Enums\MuthowifServiceType;
use App\Enums\MuthowifVerificationStatus;
use App\Http\Controllers\Controller;
use App\Models\MuthowifProfile;
use App\Models\MuthowifService;
use App\Support\ApiMediaUrl;
use App\Support\MarketplaceProfileCache;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;

class MuthowifDirectoryApiController extends Controller
{
    private const MAX_RANGE_DAYS = 90;

    public function index(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));
        $startRaw = $request->query('start_date');
        $endRaw = $request->query('end_date');

        $hasDateSearch = filled($startRaw);

        $query = MuthowifProfile::query()
            ->with(['user', 'services'])
            ->approved()
            ->hasPublishedServices()
            ->withMarketplaceStats();

        if ($hasDateSearch) {
            $endEffective = filled($endRaw) ? $endRaw : $startRaw;

            $validator = Validator::make(
                [
                    'start_date' => $startRaw,
                    'end_date' => $endEffective,
                ],
                [
                    'start_date' => ['required', 'date'],
                    'end_date' => ['required', 'date', 'after_or_equal:start_date'],
                ]
            );

            if ($validator->fails()) {
                return response()->json(['message' => 'Format tanggal tidak valid', 'errors' => $validator->errors()], 422);
            }

            $start = Carbon::parse($startRaw)->startOfDay();
            $end = Carbon::parse($endEffective)->startOfDay();

            if ($start->lt(now()->startOfDay())) {
                return response()->json(['message' => 'Tanggal mulai tidak boleh sebelum hari ini.'], 422);
            }

            if ($start->diffInDays($end) > self::MAX_RANGE_DAYS) {
                return response()->json(['message' => 'Rentang maksimal '.self::MAX_RANGE_DAYS.' hari.'], 422);
            }

            $startStr = $start->toDateString();
            $endStr = $end->toDateString();

            $blockingStatuses = array_map(
                static fn (BookingStatus $s) => $s->value,
                BookingStatus::blocksAvailability()
            );

            $query->whereDoesntHave('blockedDates', function ($q) use ($startStr, $endStr) {
                $q->whereBetween('blocked_on', [$startStr, $endStr]);
            })
            ->whereDoesntHave('bookings', function ($q) use ($startStr, $endStr, $blockingStatuses) {
                $q->whereIn('status', $blockingStatuses)
                    ->where('starts_on', '<=', $endStr)
                    ->where('ends_on', '>=', $startStr);
            });
        }

        if ($q !== '') {
            $query->whereHas('user', fn ($u) => $u->where('name', 'like', '%'.$q.'%'));
        }

        $profiles = $query->orderByMarketplaceRanking()->paginate(12)->withQueryString();

        // Format data to be easily consumed by mobile
        $formattedData = $profiles->getCollection()->map(function($profile) {
            $startPrice = $profile->services->min('price') ?? 0;
            return [
                'id' => $profile->id,
                'name' => $profile->user->name ?? 'Muthowif',
                'avatar' => ApiMediaUrl::muthowifAvatar($profile),
                'rating' => number_format($profile->average_rating ?? 5.0, 1),
                'reviews' => $profile->booking_reviews_count ?? 0,
                'location' => $profile->workLocationLabel(),
                'start_price' => $startPrice,
                'languages' => array_slice($profile->languagesForDisplay(), 0, 2),
            ];
        });

        return response()->json([
            'data' => $formattedData,
            'current_page' => $profiles->currentPage(),
            'last_page' => $profiles->lastPage(),
            'total' => $profiles->total(),
        ]);
    }

    public function show(Request $request, $id): JsonResponse
    {
        $publicProfile = MuthowifProfile::where('id', $id)
            ->where('verification_status', MuthowifVerificationStatus::Approved)
            ->firstOrFail();

        $publicProfile = MarketplaceProfileCache::forShow($publicProfile);

        $startDate = (string) $request->query('start_date', '');
        $endDate = (string) $request->query('end_date', '');
        $bookingIntent = $this->bookingIntentForProfile(
            $this->optionalUser($request),
            $publicProfile,
            $startDate,
            $endDate
        );

        $group = $publicProfile->services->firstWhere('type', MuthowifServiceType::Group);
        $private = $publicProfile->services->firstWhere('type', MuthowifServiceType::PrivateJamaah);
        $reviewsCount = (int) ($publicProfile->booking_reviews_count ?? 0);
        $avgRating = $publicProfile->booking_reviews_avg_rating !== null
            ? round((float) $publicProfile->booking_reviews_avg_rating, 1)
            : null;
        $confirmedBookings = (int) ($publicProfile->confirmed_bookings_count ?? 0);
        $languages = $publicProfile->languagesForDisplay();
        $educations = $publicProfile->educationsForDisplay();
        $experiences = $publicProfile->workExperiencesForDisplay();
        $startPrice = $publicProfile->services->min('price') ?? 0;

        $bio = filled($publicProfile->reference_text)
            ? $publicProfile->reference_text
            : ($group && filled($group->description)
                ? Str::limit(trim(strip_tags($group->description)), 400)
                : ($private && filled($private->description)
                    ? Str::limit(trim(strip_tags($private->description)), 400)
                    : null));

        $specializations = collect([$group?->name, $private?->name])
            ->filter()
            ->merge(collect($languages)->take(3))
            ->unique()
            ->values()
            ->all();

        $allAddons = collect();
        if ($private) {
            $allAddons = $allAddons->merge($private->addOns);
        }
        if ($group) {
            $allAddons = $allAddons->merge($group->addOns ?? collect());
        }
        $allAddons = $allAddons->unique('id')->values();

        return response()->json([
            'profile' => [
                'id' => $publicProfile->id,
                'slug' => $publicProfile->slug,
                'name' => $publicProfile->user->name ?? 'Muthowif',
                'avatar' => ApiMediaUrl::absolute($publicProfile->photoUrl()),
                'rating' => $avgRating !== null ? number_format($avgRating, 1) : null,
                'reviews_count' => $reviewsCount,
                'confirmed_bookings' => $confirmedBookings,
                'is_new' => $reviewsCount === 0 && $confirmedBookings === 0,
                'location' => $publicProfile->workLocationLabel(),
                'start_price' => $startPrice,
                'languages' => $languages,
                'bio' => $bio,
                'experience_summary' => $experiences[0] ?? null,
                'educations' => $educations,
                'work_experiences' => $experiences,
                'specializations' => $specializations,
            ],
            'services' => $publicProfile->services
                ->filter(fn (MuthowifService $service) => in_array($service->type, [MuthowifServiceType::Group, MuthowifServiceType::PrivateJamaah], true))
                ->map(fn (MuthowifService $service) => $this->formatServiceForApi($service))
                ->values(),
            'add_ons' => $allAddons->map(fn ($addon) => [
                'id' => $addon->id,
                'name' => $addon->name,
                'price' => (float) $addon->price,
                'type' => $addon->type->value ?? (string) $addon->type,
            ])->values(),
            'portfolios' => $publicProfile->portfolios->map(fn ($portfolio) => [
                'id' => $portfolio->id,
                'title' => $portfolio->title,
                'description' => $portfolio->description,
                'cover_url' => ApiMediaUrl::absolute($portfolio->coverUrl()),
                'images' => $portfolio->images->isNotEmpty()
                    ? $portfolio->images->map(fn ($image) => ApiMediaUrl::absolute($image->publicUrl()))->values()
                    : collect([ApiMediaUrl::absolute($portfolio->coverUrl())]),
            ])->values(),
            'portfolios_count' => (int) ($publicProfile->portfolios_count ?? $publicProfile->portfolios->count()),
            'reviews' => $publicProfile->bookingReviews->map(function ($review) {
                $customerName = $review->customer->name ?? 'Jamaah';

                return [
                    'id' => $review->id,
                    'customer_name' => $customerName,
                    'customer_avatar' => 'https://ui-avatars.com/api/?name='.urlencode($customerName).'&background=F1F5F9&color=64748B',
                    'rating' => $review->rating,
                    'comment' => $review->review,
                    'created_at' => $review->created_at->diffForHumans(),
                ];
            }),
            'blocked_dates' => $publicProfile->blockedDates->map(fn ($bd) => [
                'date' => $bd->blocked_on->toDateString(),
                'note' => $bd->note,
            ])->values(),
            'blocked_dates_count' => (int) ($publicProfile->blocked_dates_count ?? $publicProfile->blockedDates->count()),
            'bookingIntent' => $bookingIntent,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function formatServiceForApi(MuthowifService $service): array
    {
        return [
            'id' => $service->id,
            'name' => $service->name,
            'type' => $service->type->value ?? (string) $service->type,
            'type_label' => $service->type->label(),
            'description' => $service->description,
            'price' => $service->price,
            'min_pilgrims' => $service->min_pilgrims,
            'max_pilgrims' => $service->max_pilgrims,
            'same_hotel_price_per_day' => (float) $service->same_hotel_price_per_day,
            'transport_price_flat' => (float) $service->transport_price_flat,
            'has_hotel_addon' => (float) ($service->same_hotel_price_per_day ?? 0) > 0,
            'has_transport_addon' => (float) ($service->transport_price_flat ?? 0) > 0,
            'features' => $this->packageFeatures($service),
            'add_ons' => $service->addOns->map(fn ($addon) => [
                'id' => $addon->id,
                'name' => $addon->name,
                'price' => (float) $addon->price,
                'type' => $addon->type->value ?? (string) $addon->type,
            ])->values(),
        ];
    }

    /**
     * @return list<string>
     */
    private function packageFeatures(MuthowifService $service): array
    {
        $items = [__('marketplace.show.feature_guidance')];

        if ((float) ($service->same_hotel_price_per_day ?? 0) > 0) {
            $items[] = __('marketplace.show.feature_hotel');
        }
        if ((float) ($service->transport_price_flat ?? 0) > 0) {
            $items[] = __('marketplace.show.feature_transport');
        }
        if (filled($service->description)) {
            $items[] = __('marketplace.show.feature_description');
        }

        return $items;
    }

    private function optionalUser(Request $request): ?\App\Models\User
    {
        if (! $request->bearerToken()) {
            return null;
        }

        return PersonalAccessToken::findToken($request->bearerToken())?->tokenable;
    }

    private function bookingIntentForProfile(?\App\Models\User $user, MuthowifProfile $profile, string $startDate, string $endDate): array
    {
        $empty = ['can_submit' => false, 'reason' => null, 'start' => null, 'end' => null];

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
            ]
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
}
