<?php

namespace App\Support;

/**
 * Biaya platform diambil dari bruto pembayaran jamaah (bukan ditambah di atas harga).
 */
final class PlatformFee
{
    /**
     * Biaya platform per sisi: 7,5% untuk customer dan 7,5% untuk muthowif.
     * Total fee platform = 15% dari harga dasar layanan (gross sebelum fee).
     */
    public const RATE = 0.075;
    public const TOTAL_RATE = 0.15;

    /**
     * @return array{
     *     base: float,
     *     customer_fee: float,
     *     muthowif_fee: float,
     *     platform_fee_total: float,
     *     customer_gross: float,
     *     muthowif_net: float
     * }
     */
    public static function split(float $grossIdr): array
    {
        $base = round($grossIdr, 2);

        // Customer fee 7,5% ditambahkan ke tagihan yang dibayar.
        $customerFee = round($base * self::RATE, 2);
        // Muthowif fee 7,5% dipotong dari bagian muthowif (net yang masuk ke saldo).
        $muthowifFee = round($base * self::RATE, 2);

        $platformFeeTotal = round($customerFee + $muthowifFee, 2);
        $customerGross = round($base + $customerFee, 2);
        $muthowifNet = round($base - $muthowifFee, 2);

        return [
            'base' => $base,
            'customer_fee' => $customerFee,
            'muthowif_fee' => $muthowifFee,
            'platform_fee_total' => $platformFeeTotal,
            'customer_gross' => $customerGross,
            'muthowif_net' => $muthowifNet,
        ];
    }
}
