<?php

namespace App\Support;

final class IndonesianNumber
{
    /**
     * Hanya digit 0–9 (untuk normalisasi dari input berpemisah ribuan).
     */
    public static function digitsOnly(?string $input): string
    {
        if ($input === null || $input === '') {
            return '';
        }

        $digits = preg_replace('/\D+/', '', $input);

        return $digits ?? '';
    }

    /**
     * Tampilan pemisah ribuan titik (format Indonesia), tanpa titik desimal.
     */
    public static function formatThousands(string|int|float|null $input): string
    {
        if ($input === null || (is_string($input) && $input === '')) {
            return '';
        }

        // If it's a numeric value (int, float, or simple numeric string like "13000.00"),
        // we use standard number_format to avoid bugs with decimals.
        if (is_numeric($input)) {
            return number_format((float) $input, 0, ',', '.');
        }

        // If it's a string that might already have some formatting (e.g. "Rp 1.000"),
        // we strip everything but digits and then add separators.
        $digits = self::digitsOnly((string) $input);
        if ($digits === '') {
            return '';
        }

        return preg_replace('/\B(?=(\d{3})+(?!\d))/', '.', $digits) ?? $digits;
    }
}
