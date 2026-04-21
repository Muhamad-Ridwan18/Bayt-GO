<?php

namespace App\Support;

use App\Models\BookingPayment;
use App\Models\MuthowifBooking;

/**
 * Refund: layanan batal — potongan platform 15% dari harga dasar; potongan muthowif 1% dari harga dasar.
 * Net jamaah = harga dasar − potongan admin − potongan muthowif. Potongan muthowif mengurangi saldo dompet muthowif saat refund diselesaikan admin.
 */
final class BookingRefundFee
{
    /** Potongan admin/platform saat refund: 15% dari harga dasar. */
    public const PLATFORM_REFUND_RATE = 0.15;

    /** Potongan muthowif saat refund: 1% dari harga dasar (mengurangi saldo muthowif). */
    public const MUTHOWIF_REFUND_RATE = 0.01;

    /**
     * @return array{
     *     service_base_amount: float,
     *     customer_paid_amount: int,
     *     refund_fee_platform: int,
     *     refund_fee_muthowif: int,
     *     net_refund_customer: int
     * }
     */
    public static function snapshot(MuthowifBooking $booking, BookingPayment $payment): array
    {
        $base = (float) PlatformFee::split((float) $booking->resolvedAmountDue())['base'];
        $feePlatform = (int) round($base * self::PLATFORM_REFUND_RATE);
        $feeMuthowif = (int) round($base * self::MUTHOWIF_REFUND_RATE);
        $paid = (int) $payment->gross_amount;
        $baseIdr = (int) round($base);
        $net = max(0, $baseIdr - $feePlatform - $feeMuthowif);

        return [
            'service_base_amount' => round($base, 2),
            'customer_paid_amount' => $paid,
            'refund_fee_platform' => $feePlatform,
            'refund_fee_muthowif' => $feeMuthowif,
            'net_refund_customer' => $net,
        ];
    }
}
