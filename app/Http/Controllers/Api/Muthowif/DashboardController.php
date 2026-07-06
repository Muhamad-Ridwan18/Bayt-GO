<?php

namespace App\Http\Controllers\Api\Muthowif;

use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Models\MuthowifBooking;
use App\Support\MuthowifEmergencyOfferCounts;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user->isMuthowif()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $profile = $user->muthowifProfile;
        $pendingCount = (int) MuthowifBooking::query()
            ->where('muthowif_profile_id', $profile->id)
            ->where('status', BookingStatus::Pending)
            ->count();

        $avgRating = $profile->bookingReviews()->avg('rating');

        $stats = [
            [
                'label' => 'Permintaan Baru',
                'value' => (string) $pendingCount,
                'color' => '#6c5ce7',
            ],
            [
                'label' => 'Jadwal Aktif',
                'value' => (string) MuthowifBooking::where('muthowif_profile_id', $profile->id)->whereIn('status', ['confirmed', 'in_progress'])->count(),
                'color' => '#0984e3',
            ],
            [
                'label' => 'Saldo (IDR)',
                'value' => 'Rp '.number_format($profile->wallet_balance ?? 0, 0, ',', '.'),
                'color' => '#f39c12',
            ],
        ];

        $schedules = MuthowifBooking::where('muthowif_profile_id', $profile->id)
            ->with('customer')
            ->whereIn('status', ['confirmed', 'in_progress'])
            ->orderBy('starts_on', 'asc')
            ->take(5)
            ->get()
            ->map(function ($booking) {
                $customerName = $booking->customer->name ?? 'Jamaah';

                return [
                    'id' => $booking->id,
                    'booking_number' => $booking->booking_code ?? ('#BGO-'.$booking->id),
                    'customer_name' => $customerName,
                    'customer_avatar' => 'https://ui-avatars.com/api/?name='.urlencode($customerName).'&background=1A3D34&color=fff',
                    'date' => $booking->starts_on ? $booking->starts_on->format('d M Y') : '-',
                    'starts_on' => $booking->starts_on?->toDateString(),
                    'ends_on' => $booking->ends_on?->toDateString(),
                    'raw_date' => $booking->starts_on ? $booking->starts_on->format('Y-m-d') : null,
                    'duration' => $booking->billingNightsInclusive().' Hari',
                    'status' => $booking->status->value,
                    'pilgrim_count' => (int) $booking->pilgrim_count,
                ];
            });

        $unreadCount = MuthowifBooking::where('muthowif_profile_id', $profile->id)
            ->withCount(['chatMessages as unread_count' => function ($q) use ($user) {
                $q->where('user_id', '!=', $user->id)->whereNull('read_at');
            }])
            ->get()
            ->sum('unread_count');

        return response()->json([
            'stats' => $stats,
            'recent_schedules' => $schedules,
            'unread_messages' => $unreadCount,
            'pending_booking_count' => $pendingCount,
            'emergency_offer_count' => MuthowifEmergencyOfferCounts::pendingOfferedCountForUser($user),
            'referral_code' => $profile->referral_code,
            'rating' => $avgRating !== null ? number_format((float) $avgRating, 1) : null,
            'review_count' => (int) $profile->bookingReviews()->count(),
            'wallet_balance' => (float) ($profile->wallet_balance ?? 0),
        ]);
    }
}
