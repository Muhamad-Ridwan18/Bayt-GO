<?php

namespace App\Support;

final class PhoneNumber
{
    /**
     * Normalize Indonesian mobile to digits with country code 62 (e.g. 6281234567890).
     */
    public static function normalize(?string $input): ?string
    {
        if ($input === null || trim($input) === '') {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $input);
        if ($digits === null || $digits === '') {
            return null;
        }

        if (str_starts_with($digits, '62')) {
            return $digits;
        }

        if (str_starts_with($digits, '0')) {
            return '62'.substr($digits, 1);
        }

        if (str_starts_with($digits, '8')) {
            return '62'.$digits;
        }

        return $digits;
    }

    /**
     * Format untuk API Fonnte (target lokal 08… dengan countryCode 62 default).
     */
    public static function forFonnte(?string $input): ?string
    {
        $normalized = self::normalize($input);
        if ($normalized === null || strlen($normalized) < 10) {
            return null;
        }

        if (str_starts_with($normalized, '62')) {
            return '0'.substr($normalized, 2);
        }

        return $normalized;
    }
}
