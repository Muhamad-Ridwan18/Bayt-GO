<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MuthowifBooking;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // Statistik booking asli
        $stats = [
            [
                'label' => 'Booking Aktif',
                'value' => (string) MuthowifBooking::where('customer_id', $user->id)->whereIn('status', ['confirmed', 'ongoing'])->count(),
                'color' => '#0984e3'
            ],
            [
                'label' => 'Menunggu',
                'value' => (string) MuthowifBooking::where('customer_id', $user->id)->where('status', 'pending')->count(),
                'color' => '#6c5ce7'
            ],
            [
                'label' => 'Selesai',
                'value' => (string) MuthowifBooking::where('customer_id', $user->id)->where('status', 'completed')->count(),
                'color' => '#00b894'
            ],
        ];

        // Mengambil 5 rekomendasi muthowif terverifikasi
        $topMuthowifs = \App\Models\MuthowifProfile::with(['user', 'services'])
            ->where('verification_status', \App\Enums\MuthowifVerificationStatus::Approved)
            ->inRandomOrder()
            ->take(5)
            ->get()
            ->map(function($profile) {
                $avgRating = $profile->bookingReviews()->avg('rating') ?? 5.0;
                $reviewCount = $profile->bookingReviews()->count();
                $startPrice = $profile->services->min('price') ?? 0;

                return [
                    'id' => $profile->id,
                    'name' => $profile->user->name ?? 'Muthowif',
                    'avatar' => $profile->photo_path ? asset('storage/' . $profile->photo_path) : 'https://ui-avatars.com/api/?name=' . urlencode($profile->user->name ?? 'M') . '&background=0984e3&color=fff',
                    'rating' => number_format($avgRating, 1),
                    'reviews' => $reviewCount,
                    'location' => 'Makkah & Madinah',
                    'start_price' => $startPrice,
                    'languages' => array_slice($profile->languagesForDisplay(), 0, 2),
                ];
            });

        $unreadCount = \App\Models\MuthowifBooking::where('customer_id', $user->id)
            ->withCount(['chatMessages as unread_count' => function ($q) use ($user) {
                $q->where('user_id', '!=', $user->id)->whereNull('read_at');
            }])
            ->get()
            ->sum('unread_count');

        return response()->json([
            'stats' => $stats,
            'top_muthowifs' => $topMuthowifs,
            'unread_messages' => $unreadCount,
        ]);
    }
}
