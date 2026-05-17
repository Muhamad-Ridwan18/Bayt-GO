<?php

namespace App\Support;

use App\Models\SiteSetting;

/**
 * Biaya platform diambil dari bruto pembayaran jamaah (bukan ditambah di atas harga).
 */
final class PlatformFee
{
    /**
     * Biaya platform per sisi (customer & muthowif). Default 7,5%.
     */
    public static function getRate(): float
    {
        return (float) SiteSetting::getValue('platform_fee_rate', '0.075');
    }

    /**
     * Total fee platform (customer + muthowif). Default 15%.
     */
    public static function getTotalRate(): float
    {
        return self::getRate() * 2;
    }

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
    public static function split(float $grossIdr, bool $isCompanyCustomer = false): array
    {
        $base = round($grossIdr, 2);
        $rate = self::getRate();

        // Customer fee ditambahkan ke tagihan yang dibayar. Jika customer = company, bebaskan fee customer.
        $customerFee = $isCompanyCustomer ? 0.0 : round($base * $rate, 2);
        // Muthowif fee dipotong dari bagian muthowif (net yang masuk ke saldo).
        $muthowifFee = round($base * $rate, 2);

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
