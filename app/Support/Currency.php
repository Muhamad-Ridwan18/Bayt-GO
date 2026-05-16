<?php

namespace App\Support;

use App\Services\CurrencyService;
use Illuminate\Support\Facades\App;

class Currency
{
    /**
     * Format nominal USD (dari database) menjadi string mata uang (USD atau IDR).
     */
    public static function format(string|int|float|null $amount, string $to = null): string
    {
        if ($amount === null || $amount === '') {
            return '—';
        }

        $amount = (float) $amount;
        $targetCurrency = $to ?? config('app.currency.display', 'USD');

        if (strtoupper($targetCurrency) === 'IDR') {
            $service = App::make(CurrencyService::class);
            $idrAmount = $service->convertUsdToIdr($amount);
            
            // Format IDR: Rp 1.000.000
            return 'Rp ' . number_format($idrAmount, 0, ',', '.');
        }

        // Default: Format USD ($ 50.00)
        return '$ ' . number_format($amount, 2, '.', ',');
    }

    /**
     * Shortcut untuk paksa format ke USD
     */
    public static function usd(string|int|float|null $amount): string
    {
        return self::format($amount, 'USD');
    }

    /**
     * Shortcut untuk paksa format ke IDR
     */
    public static function idr(string|int|float|null $amount): string
    {
        return self::format($amount, 'IDR');
    }
}
