<?php

namespace App\Support;

use App\Models\BookingPayment;
use App\Models\MuthowifBooking;

/**
 * Refund: layanan batal — potongan platform 15% dari harga dasar; bagian muthowif 1% dari harga dasar.
 * Net jamaah = harga dasar − potongan admin − bagian muthowif. Saat admin menyelesaikan refund, nominal bagian muthowif dikreditkan ke saldo dompet muthowif.
 */
final class BookingRefundFee
{
    /** Potongan admin/platform saat refund: 15% dari harga dasar. */
    public const PLATFORM_REFUND_RATE = 0.15;

    /** Bagian muthowif (1% dari harga dasar): mengurangi net refund jamaah; masuk ke saldo muthowif saat refund selesai. */
    public const MUTHOWIF_REFUND_RATE = 0.01;

    /**
     * @return array{
     *     service_base_amount: float,
     *     customer_paid_amount: float,
     *     refund_fee_platform: float,
     *     refund_fee_muthowif: float,
     *     net_refund_customer: float
     * }
     */
    public static function snapshot(MuthowifBooking $booking, BookingPayment $payment): array
    {
        $base = (float) PlatformFee::split((float) $booking->resolvedAmountDue())['base'];
        $feePlatform = round($base * self::PLATFORM_REFUND_RATE, 2);
        $feeMuthowif = round($base * self::MUTHOWIF_REFUND_RATE, 2);
        $paid = (float) $payment->gross_amount;
        $net = round(max(0, $base - $feePlatform - $feeMuthowif), 2);

        return [
            'service_base_amount' => round($base, 2),
            'customer_paid_amount' => $paid,
            'refund_fee_platform' => $feePlatform,
            'refund_fee_muthowif' => $feeMuthowif,
            'net_refund_customer' => $net,
        ];
    }
}
