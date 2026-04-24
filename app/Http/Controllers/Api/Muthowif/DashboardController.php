<?php

namespace App\Http\Controllers\Api\Muthowif;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MuthowifBooking;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        
        if (!$user->isMuthowif()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $profile = $user->muthowifProfile;

        // Statistik Muthowif
        $stats = [
            [
                'label' => 'Jadwal Aktif',
                'value' => (string) MuthowifBooking::where('muthowif_profile_id', $profile->id)->whereIn('status', ['confirmed', 'ongoing'])->count(),
                'color' => '#0984e3'
            ],
            [
                'label' => 'Total Selesai',
                'value' => (string) MuthowifBooking::where('muthowif_profile_id', $profile->id)->where('status', 'completed')->count(),
                'color' => '#00b894'
            ],
            [
                'label' => 'Saldo (IDR)',
                'value' => 'Rp ' . number_format($profile->wallet_balance ?? 0, 0, ',', '.'),
                'color' => '#f39c12'
            ],
        ];

        // Jadwal Mendatang
        $schedules = MuthowifBooking::where('muthowif_profile_id', $profile->id)
            ->with('customer')
            ->whereIn('status', ['confirmed', 'ongoing'])
            ->orderBy('starts_on', 'asc')
            ->take(5)
            ->get()
            ->map(function($booking) {
                return [
                    'id' => $booking->id,
                    'booking_number' => $booking->booking_code ?? ('#BGO-' . $booking->id),
                    'customer_name' => $booking->customer->name ?? 'Jamaah',
                    'date' => $booking->starts_on ? $booking->starts_on->format('d M Y') : '-',
                    'raw_date' => $booking->starts_on ? $booking->starts_on->format('Y-m-d') : null,
                    'duration' => $booking->billingNightsInclusive() . ' Hari',
                    'status' => strtoupper($booking->status->value ?? (string)$booking->status),
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
        ]);
    }
}
