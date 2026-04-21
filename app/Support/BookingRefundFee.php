<?php

namespace App\Support;

use App\Models\BookingPayment;
use App\Models\MuthowifBooking;

/**
 * Refund: layanan batal — potongan hanya untuk platform (15% dari harga dasar).
 * Bagian sisi muthowif dari transaksi (7,5% fee platform) tidak dijadikan potongan/refund tambahan ke jamaah;
 * tidak ada alokasi ke saldo muthowif dari refund. Net jamaah = harga dasar − potongan admin.
 */
final class BookingRefundFee
{
    /** Potongan admin/platform saat refund: 15% dari harga dasar. */
    public const PLATFORM_REFUND_RATE = 0.15;

    /** Di nol-kan: order tidak jadi, tidak ada potongan/pembagian ke muthowif dari refund. */
    public const MUTHOWIF_REFUND_RATE = 0.0;

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
