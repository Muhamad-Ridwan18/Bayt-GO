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

    /**
     * Untuk Moota: bila ada beberapa {@see config('services.moota.bank_account_ids')}, halaman web memecah
     * menjadi metode bank_transfer_moota__0, __1, … agar jamaah memilih rekening.
     *
     * @return list<string>
     */
    public static function webMethodsExpanded(): array
    {
        $base = self::webMethods();
        if (self::driver() !== 'moota') {
            return $base;
        }

        /** @var array<int, string> $ids */
        $ids = config('services.moota.bank_account_ids', []);
        $ids = array_values(array_filter(array_map(trim(...), $ids)));
        if (count($ids) <= 1) {
            return $base;
        }

        return array_map(static fn (int $i): string => 'bank_transfer_moota__'.$i, array_keys($ids));
    }

    /**
     * @return array{canonical: string, moota_bank_account_id: ?string}
     */
    public static function normalizeWebPaymentMethod(string $selected): array
    {
        if (! preg_match('/^bank_transfer_moota__(\d+)$/', $selected, $m)) {
            return ['canonical' => $selected, 'moota_bank_account_id' => null];
        }

        /** @var array<int, string> $ids */
        $ids = config('services.moota.bank_account_ids', []);
        $ids = array_values(array_filter(array_map(trim(...), $ids)));
        $idx = (int) $m[1];
        $accountId = $ids[$idx] ?? null;

        return [
            'canonical' => 'bank_transfer_moota',
            'moota_bank_account_id' => is_string($accountId) ? $accountId : null,
        ];
    }
}
