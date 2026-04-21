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
    /**
     * Volume bruto pembayaran jamaah (gross Midtrans): settlement/capture pada booking muthowif ini yang belum refunded.
     */
    public static function grossVolumeExcludingRefundedBookings(MuthowifProfile|string $profile): int
    {
        $id = $profile instanceof MuthowifProfile ? $profile->getKey() : $profile;

        return (int) BookingPayment::query()
            ->join('muthowif_bookings as b', 'b.id', '=', 'booking_payments.muthowif_booking_id')
            ->where('b.muthowif_profile_id', $id)
            ->whereIn('booking_payments.status', ['settlement', 'capture'])
            ->whereRaw('b.payment_status != ?', [PaymentStatus::Refunded->value])
            ->sum('booking_payments.gross_amount');
    }
}
