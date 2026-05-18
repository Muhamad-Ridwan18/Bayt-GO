<?php

namespace App\Support;

use App\Enums\PaymentStatus;
use App\Models\BookingPayment;
use App\Models\MuthowifProfile;

/**
 * Agregat ringkasan muthowif (dashboard) — selaras aturan volume bruto admin/keuangan, per profil.
 */
final class MuthowifFinanceSummary
{
    public static function clearCache(string $profileId): void
    {
        \Illuminate\Support\Facades\Cache::forget("muthowif_gross_volume_{$profileId}");
    }

    /**
     * Volume bruto pembayaran jamaah (gross): settlement/capture pada booking muthowif ini yang belum refunded.
     */
    public static function grossVolumeExcludingRefundedBookings(MuthowifProfile|string $profile): int
    {
        $id = $profile instanceof MuthowifProfile ? $profile->getKey() : $profile;

        return (int) \Illuminate\Support\Facades\Cache::remember("muthowif_gross_volume_{$id}", 86400, function () use ($id) {
            return (int) BookingPayment::query()
                ->join('muthowif_bookings as b', 'b.id', '=', 'booking_payments.muthowif_booking_id')
                ->where('b.muthowif_profile_id', $id)
                ->whereIn('booking_payments.status', ['settlement', 'capture'])
                ->where('b.payment_status', '!=', PaymentStatus::Refunded->value)
                ->sum('booking_payments.gross_amount');
        });
    }
}
