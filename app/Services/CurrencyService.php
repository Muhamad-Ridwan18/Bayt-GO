<?php

namespace App\Services;

class CurrencyService
{
    /**
     * Ambil kurs USD ke IDR dari SiteSetting.
     * Fallback ke config jika setting belum diisi.
     */
    public function getUsdToIdrRate(): float
    {
        $setting = \App\Models\SiteSetting::getValue('fallback_usd_rate');
        if ($setting !== null && is_numeric($setting)) {
            return (float) $setting;
        }

        return (float) config('app.currency.fallback_usd_rate', 17602.0);
    }

    /**
     * Konversi USD ke IDR
     */
    public function convertUsdToIdr(float $usdAmount): float
    {
        $rate = $this->getUsdToIdrRate();

        return $usdAmount * $rate;
    }
}
