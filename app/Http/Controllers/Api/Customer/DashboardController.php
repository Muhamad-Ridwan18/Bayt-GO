<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Models\MuthowifBooking;
use App\Support\ApiMediaUrl;
use App\Support\CustomerDashboardCache;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $dash = CustomerDashboardCache::stats($user);

        $stats = [
            [
                'label' => 'Booking Aktif',
                'value' => (string) $dash['activeBookingCount'],
                'color' => '#0984e3',
            ],
            [
                'label' => 'Tiket Bantuan',
                'value' => (string) $dash['supportOpenCount'],
                'color' => '#6c5ce7',
            ],
            [
                'label' => 'Perjalanan Mendatang',
                'value' => (string) $dash['upcomingTripCount'],
                'color' => '#00b894',
            ],
            [
                'label' => 'Ulasan Diberikan',
                'value' => (string) $dash['reviewsGivenCount'],
                'color' => '#f39c12',
            ],
        ];

        $topMuthowifs = \App\Models\MuthowifProfile::with(['user', 'services'])
            ->where('verification_status', \App\Enums\MuthowifVerificationStatus::Approved)
            ->inRandomOrder()
            ->take(5)
            ->get()
            ->map(function ($profile) {
                $avgRating = $profile->bookingReviews()->avg('rating') ?? 5.0;
                $reviewCount = $profile->bookingReviews()->count();
                $startPrice = $profile->services->min('price') ?? 0;

                return [
                    'id' => $profile->id,
                    'name' => $profile->user->name ?? 'Muthowif',
                    'avatar' => ApiMediaUrl::muthowifAvatar($profile),
                    'rating' => number_format($avgRating, 1),
                    'reviews' => $reviewCount,
                    'location' => $profile->workLocationLabel(),
                    'start_price' => $startPrice,
                    'languages' => array_slice($profile->languagesForDisplay(), 0, 2),
                ];
            });

        $unreadCount = MuthowifBooking::where('customer_id', $user->id)
            ->withCount(['chatMessages as unread_count' => function ($q) use ($user) {
                $q->where('user_id', '!=', $user->id)->whereNull('read_at');
            }])
            ->get()
            ->sum('unread_count');

        $next = $dash['nextBooking'];
        $nextBooking = null;
        if ($next instanceof MuthowifBooking) {
            $next->loadMissing('muthowifProfile.user');
            $profile = $next->muthowifProfile;
            $muthowifName = $profile?->user?->name ?? 'Muthowif';
            $nextBooking = [
                'id' => (string) $next->getKey(),
                'booking_code' => $next->booking_code,
                'status' => $next->status->value,
                'payment_status' => $next->payment_status->value,
                'starts_on' => $next->starts_on?->toDateString(),
                'ends_on' => $next->ends_on?->toDateString(),
                'muthowif_name' => $muthowifName,
                'muthowif_avatar' => $profile
                    ? ApiMediaUrl::muthowifAvatar($profile, $muthowifName)
                    : ApiMediaUrl::fallbackAvatar($muthowifName),
                'pilgrim_count' => (int) $next->pilgrim_count,
            ];
        }

        return response()->json([
            'stats' => $stats,
            'top_muthowifs' => $topMuthowifs,
            'unread_messages' => $unreadCount,
            'next_booking' => $nextBooking,
        ]);
    }
}
