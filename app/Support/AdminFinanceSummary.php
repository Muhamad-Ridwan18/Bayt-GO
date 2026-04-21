<?php

namespace App\Support;

use App\Enums\BookingChangeRequestStatus;
use App\Enums\PaymentStatus;
use App\Models\BookingPayment;
use App\Models\BookingRefundRequest;

/**
 * Agregat ringkasan admin (kartu dashboard & halaman keuangan) — satu sumber kebenaran.
 */
final class AdminFinanceSummary
{
    public static function platformFeesFromPayments(): float
    {
        return (float) BookingPayment::query()
            ->join('muthowif_bookings as b', 'b.id', '=', 'booking_payments.muthowif_booking_id')
            ->whereIn('booking_payments.status', ['settlement', 'capture'])
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN b.payment_status = ? THEN booking_payments.platform_fee_amount * 0.5 ELSE booking_payments.platform_fee_amount END), 0) as platform_from_payments',
                [PaymentStatus::Refunded->value]
            )
            ->value('platform_from_payments');
    }

    public static function platformFeesFromRefunds(): float
    {
        return (float) BookingRefundRequest::query()
            ->where('status', BookingChangeRequestStatus::Approved)
            ->sum('refund_fee_platform');
    }

    public static function totalPlatformFees(): float
    {
        return self::platformFeesFromPayments() + self::platformFeesFromRefunds();
    }

    /**
     * Volume bruto pembayaran (gross Midtrans): settlement/capture pada booking yang belum refunded.
     */
    public static function grossVolumeExcludingRefundedBookings(): int
    {
        return (int) BookingPayment::query()
            ->join('muthowif_bookings as b', 'b.id', '=', 'booking_payments.muthowif_booking_id')
            ->whereIn('booking_payments.status', ['settlement', 'capture'])
            ->whereRaw('b.payment_status != ?', [PaymentStatus::Refunded->value])
            ->sum('booking_payments.gross_amount');
    }
}
