<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CurrencyService
{
    /**
     * Ambil kurs USD ke IDR dari API gratis (Frankfurter).
     * Hasil di-cache selama 24 jam.
     */
    public function getUsdToIdrRate(): float
    {
        return Cache::remember('currency_usd_to_idr', now()->addDay(), function () {
            try {
                // Frankfurter API: Gratis & No API Key
                $response = Http::timeout(10)->get('https://api.frankfurter.app/latest', [
                    'from' => 'USD',
                    'to' => 'IDR',
                ]);

                if ($response->successful()) {
                    return (float) $response->json('rates.IDR');
                }
            } catch (\Exception $e) {
                Log::error('CurrencyService Error: ' . $e->getMessage());
            }

            // Fallback jika API gagal, cek database via SiteSetting
            $fallback = \App\Models\SiteSetting::getValue('fallback_usd_rate');
            if ($fallback !== null && is_numeric($fallback)) {
                return (float) $fallback;
            }

            return (float) config('app.currency.fallback_usd_rate', 16000.0);
        });
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
