<?php

namespace App\Support;

use App\Models\BookingPayment;
use App\Models\MuthowifBooking;

/**
 * Biaya admin refund: 2,5% + 1% dari total layanan (harga dasar, tanpa biaya platform di sisi customer).
 */
final class BookingRefundFee
{
    public const PLATFORM_RATE = 0.025;

    public const MUTHOWIF_RATE = 0.01;

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
        $feePlatform = (int) round($base * self::PLATFORM_RATE);
        $feeMuthowif = (int) round($base * self::MUTHOWIF_RATE);
        $paid = (int) $payment->gross_amount;
        $net = max(0, $paid - $feePlatform - $feeMuthowif);

        return [
            'service_base_amount' => round($base, 2),
            'customer_paid_amount' => $paid,
            'refund_fee_platform' => $feePlatform,
            'refund_fee_muthowif' => $feeMuthowif,
            'net_refund_customer' => $net,
        ];
    }
}
