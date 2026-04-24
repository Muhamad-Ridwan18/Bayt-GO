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

        // Mengambil 5 booking terbaru dengan kolom yang benar (starts_on)
        $recentBookings = MuthowifBooking::where('customer_id', $user->id)
            ->with('muthowifProfile.user')
            ->latest()
            ->take(5)
            ->get()
            ->map(function($booking) {
                return [
                    'id' => $booking->id,
                    'booking_number' => $booking->booking_code ?? ('#BGO-' . $booking->id),
                    'status' => strtoupper($booking->status->value ?? (string)$booking->status),
                    'muthowif_name' => $booking->muthowifProfile->user->name ?? 'Muthowif',
                    'date' => $booking->starts_on ? $booking->starts_on->format('d M Y') : 'Tanggal tidak set',
                    'duration' => $booking->billingNightsInclusive() . ' Hari'
                ];
            });

        return response()->json([
            'stats' => $stats,
            'recent_bookings' => $recentBookings,
        ]);
    }
}
