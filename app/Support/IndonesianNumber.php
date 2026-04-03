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
    public static function formatThousands(?string $digitsOnly): string
    {
        if ($digitsOnly === null || $digitsOnly === '') {
            return '';
        }

        $digits = self::digitsOnly($digitsOnly);
        if ($digits === '') {
            return '';
        }

        return preg_replace('/\B(?=(\d{3})+(?!\d))/', '.', $digits) ?? $digits;
    }
}
