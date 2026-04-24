<?php

namespace App\Http\Controllers\Api;

use App\Enums\BookingStatus;
use App\Enums\MuthowifVerificationStatus;
use App\Http\Controllers\Controller;
use App\Models\MuthowifProfile;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

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
            ->withCount([
                'bookings as confirmed_bookings_count' => static fn ($q) => $q->where('status', BookingStatus::Confirmed),
                'bookingReviews',
            ])
            ->withAvg('bookingReviews as average_rating', 'rating')
            ->where('verification_status', MuthowifVerificationStatus::Approved);

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

        $profiles = $query->orderByDesc('verified_at')->paginate(12)->withQueryString();

        // Format data to be easily consumed by mobile
        $formattedData = $profiles->getCollection()->map(function($profile) {
            $startPrice = $profile->services->min('price') ?? 0;
            return [
                'id' => $profile->id,
                'name' => $profile->user->name ?? 'Muthowif',
                'avatar' => $profile->photo_path ? asset('storage/' . $profile->photo_path) : 'https://ui-avatars.com/api/?name=' . urlencode($profile->user->name ?? 'M') . '&background=0984e3&color=fff',
                'rating' => number_format($profile->average_rating ?? 5.0, 1),
                'reviews' => $profile->booking_reviews_count ?? 0,
                'location' => 'Makkah & Madinah', // Or derive from address
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

        $publicProfile->load([
            'user',
            'services.addOns',
            'bookingReviews' => fn ($q) => $q
                ->with('customer')
                ->latest()
                ->limit(10),
        ]);

        $publicProfile->loadCount([
            'bookings as confirmed_bookings_count' => static fn ($q) => $q->where('status', BookingStatus::Confirmed),
            'bookingReviews',
        ]);
        $publicProfile->loadAvg('bookingReviews', 'rating');

        $startDate = (string) $request->query('start_date', '');
        $endDate = (string) $request->query('end_date', '');
        $bookingIntent = $this->bookingIntentForProfile($request, $publicProfile, $startDate, $endDate);

        $startPrice = $publicProfile->services->min('price') ?? 0;

        return response()->json([
            'profile' => [
                'id' => $publicProfile->id,
                'name' => $publicProfile->user->name ?? 'Muthowif',
                'avatar' => $publicProfile->photo_path ? asset('storage/' . $publicProfile->photo_path) : 'https://ui-avatars.com/api/?name=' . urlencode($publicProfile->user->name ?? 'M') . '&background=0984e3&color=fff',
                'rating' => number_format($publicProfile->average_rating ?? 5.0, 1),
                'reviews_count' => $publicProfile->booking_reviews_count ?? 0,
                'confirmed_bookings' => $publicProfile->confirmed_bookings_count ?? 0,
                'location' => 'Makkah & Madinah',
                'start_price' => $startPrice,
                'languages' => $publicProfile->languagesForDisplay(),
                'bio' => $publicProfile->bio,
            ],
            'services' => $publicProfile->services->map(function($service) {
                return [
                    'id' => $service->id,
                    'name' => $service->name,
                    'type' => $service->type->value ?? (string)$service->type,
                    'description' => $service->description,
                    'price' => $service->price,
                    'duration_hours' => $service->duration_hours,
                    'max_pax' => $service->max_pax,
                    'same_hotel_price_per_day' => (float)$service->same_hotel_price_per_day,
                    'transport_price_flat' => (float)$service->transport_price_flat,
                    'add_ons' => $service->addOns->map(function($addon) {
                        return [
                            'id' => $addon->id,
                            'name' => $addon->name,
                            'price' => $addon->price,
                            'type' => $addon->type->value ?? (string)$addon->type,
                        ];
                    }),
                ];
            }),
            'reviews' => $publicProfile->bookingReviews->map(function($review) {
                return [
                    'id' => $review->id,
                    'customer_name' => $review->customer->name ?? 'Jamaah',
                    'customer_avatar' => 'https://ui-avatars.com/api/?name=' . urlencode($review->customer->name ?? 'J') . '&background=F1F5F9&color=64748B',
                    'rating' => $review->rating,
                    'comment' => $review->comment,
                    'created_at' => $review->created_at->diffForHumans(),
                ];
            }),
            'bookingIntent' => $bookingIntent,
        ]);
    }

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

        if (! $profile->isSlotAvailableForRange($start, $end)) {
            return array_merge($empty, [
                'reason' => 'slot_unavailable',
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
