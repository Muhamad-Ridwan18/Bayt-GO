<?php

namespace App\Http\Controllers\Api\Muthowif;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MuthowifBooking;
use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use Carbon\Carbon;

class MuthowifBookingController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        
        $bookings = MuthowifBooking::where('muthowif_profile_id', $user->muthowifProfile->id)
            ->with(['customer', 'muthowifProfile.services'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function($b) {
                return [
                    'id' => $b->id,
                    'booking_code' => $b->booking_code,
                    'customer_name' => $b->customer->name,
                    'customer_email' => $b->customer->email,
                    'status' => $b->status->value,
                    'status_label' => $b->status->label(),
                    'payment_status' => $b->payment_status->value,
                    'payment_label' => $b->payment_status->label(),
                    'starts_on' => Carbon::parse($b->starts_on)->format('d M Y'),
                    'ends_on' => Carbon::parse($b->ends_on)->format('d M Y'),
                    'service_type' => $b->service_type?->label() ?? '—',
                    'pilgrim_count' => $b->pilgrim_count,
                    'total_price' => 'Rp ' . number_format($b->total_amount, 0, ',', '.')
                ];
            });

        return response()->json([
            'bookings' => $bookings
        ]);
    }

    public function show(Request $request, $id)
    {
        $user = $request->user();
        $b = MuthowifBooking::where('muthowif_profile_id', $user->muthowifProfile->id)
            ->with(['customer', 'muthowifProfile.services'])
            ->findOrFail($id);

        return response()->json([
            'booking' => [
                'id' => $b->id,
                'booking_code' => $b->booking_code,
                'customer_name' => $b->customer->name,
                'customer_email' => $b->customer->email,
                'customer_phone' => $b->customer->phone,
                'status' => $b->status->value,
                'status_label' => $b->status->label(),
                'payment_status' => $b->payment_status->value,
                'payment_label' => $b->payment_status->label(),
                'starts_on' => Carbon::parse($b->starts_on)->format('d M Y'),
                'ends_on' => Carbon::parse($b->ends_on)->format('d M Y'),
                'service_type' => $b->service_type?->label() ?? '—',
                'pilgrim_count' => $b->pilgrim_count,
                'total_price' => 'Rp ' . number_format($b->resolvedAmountDue(), 0, ',', '.'),
                'with_same_hotel' => (bool)$b->with_same_hotel,
                'with_transport' => (bool)$b->with_transport,
                'add_ons' => $b->resolvedAddOns()->map(fn($a) => ['name' => $a->name, 'price' => (float)$a->price]),
                'notes' => $b->notes ?? 'Tidak ada catatan tambahan'
            ]
        ]);
    }

    public function confirm(Request $request, $id)
    {
        $user = $request->user();
        $booking = MuthowifBooking::where('muthowif_profile_id', $user->muthowifProfile->id)
            ->findOrFail($id);

        if ($booking->status !== BookingStatus::Pending) {
            return response()->json(['message' => 'Hanya pesanan pending yang bisa disetujui.'], 422);
        }

        $booking->update(['status' => BookingStatus::Confirmed]);

        return response()->json([
            'message' => 'Pesanan berhasil disetujui',
            'status' => 'confirmed'
        ]);
    }

    public function cancel(Request $request, $id)
    {
        $user = $request->user();
        $booking = MuthowifBooking::where('muthowif_profile_id', $user->muthowifProfile->id)
            ->findOrFail($id);

        if (!in_array($booking->status, [BookingStatus::Pending, BookingStatus::Confirmed])) {
            return response()->json(['message' => 'Pesanan ini tidak dapat dibatalkan.'], 422);
        }

        $booking->update(['status' => BookingStatus::Cancelled]);

        return response()->json([
            'message' => 'Pesanan berhasil dibatalkan',
            'status' => 'cancelled'
        ]);
    }
}
