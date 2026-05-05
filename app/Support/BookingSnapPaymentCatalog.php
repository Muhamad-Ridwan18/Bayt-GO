<?php

namespace App\Support;

final class BookingSnapPaymentCatalog
{
    private const DOKU_METHODS = [
        'va_bca',
        'va_bni',
        'va_bri',
        'va_permata',
        'va_mandiri_bill',
        'qris',
        'gopay',
        'shopeepay',
    ];

    public static function driver(): string
    {
        return (string) config('services.booking.payment_driver', 'doku');
    }

    /** @return list<string> */
    public static function webMethods(): array
    {
        return match (self::driver()) {
            'moota' => ['bank_transfer_moota'],
            default => [...self::DOKU_METHODS],
        };
    }
}
