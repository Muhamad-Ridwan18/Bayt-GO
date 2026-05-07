<?php

namespace App\Support;

use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumber;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;

final class IntlPhone
{
    private static ?PhoneNumberUtil $instance = null;

    private static function util(): PhoneNumberUtil
    {
        return self::$instance ??= PhoneNumberUtil::getInstance();
    }

    /** ISO 3166-1 alpha-2 untuk input format “lokal” tanpa kode negara (mis. 0812… di Indonesia). */
    public static function defaultRegion(): string
    {
        $r = trim((string) config('services.phone.default_region', 'ID'));

        return strlen($r) === 2 ? strtoupper($r) : 'ID';
    }

    /**
     * @throws NumberParseException
     */
    private static function parseToProto(string $trimmed): PhoneNumber
    {
        $util = self::util();

        if (str_starts_with($trimmed, '+')) {
            return $util->parse($trimmed, null);
        }

        $digitsOnly = preg_replace('/\D+/', '', $trimmed) ?? '';

        if (strlen($digitsOnly) >= 10 && str_starts_with($digitsOnly, '00')) {
            return $util->parse('+'.substr($digitsOnly, 2), null);
        }

        try {
            $proto = $util->parse($trimmed, self::defaultRegion());
            if ($util->isPossibleNumber($proto)) {
                return $proto;
            }
        } catch (NumberParseException) {
            // Fallback di bawah.
        }

        if ($digitsOnly !== '' && ctype_digit($digitsOnly) && ! str_starts_with($digitsOnly, '0')) {
            try {
                $protoIntl = $util->parse('+'.$digitsOnly, null);
                if ($util->isPossibleNumber($protoIntl)) {
                    return $protoIntl;
                }
            } catch (NumberParseException) {
                // Fallback akhir di bawah.
            }
        }

        return $util->parse($trimmed, self::defaultRegion());
    }

    /**
     * Normalisasi ke digit E.164 tanpa prefiks + (kunci penyimpanan & cache).
     */
    public static function normalize(?string $input): ?string
    {
        if ($input === null || trim($input) === '') {
            return null;
        }

        $trimmed = trim($input);

        try {
            $proto = self::parseToProto($trimmed);
        } catch (NumberParseException) {
            return null;
        }

        if (! self::util()->isPossibleNumber($proto)) {
            return null;
        }

        $e164 = self::util()->format($proto, PhoneNumberFormat::E164);
        $digits = preg_replace('/\D+/', '', $e164);

        return is_string($digits) && strlen($digits) >= 8 && strlen($digits) <= 15 ? $digits : null;
    }

    /**
     * Format untuk API Fonnte: nomor jalur domestik digit + kode negara (bukan nama negara).
     *
     * @return array{target: string, country_calling_code: string}|null
     */
    public static function fonnteDial(?string $input): ?array
    {
        if ($input === null || trim($input) === '') {
            return null;
        }

        try {
            $proto = self::parseToProto(trim($input));
        } catch (NumberParseException) {
            return null;
        }

        if (! self::util()->isPossibleNumber($proto)) {
            return null;
        }

        $cc = $proto->getCountryCode();
        if ($cc === 0) {
            return null;
        }

        $nationalFmt = self::util()->format($proto, PhoneNumberFormat::NATIONAL);
        $target = preg_replace('/\D+/', '', $nationalFmt ?? '');
        if (! is_string($target) || $target === '') {
            return null;
        }

        return [
            'target' => $target,
            'country_calling_code' => (string) $cc,
        ];
    }

    /**
     * Hanya digit “target” Fonnte tanpa konteks negara — gunakan {@see fonnteDial} bila mengirim lewat Fonnte API.
     */
    public static function forFonnte(?string $input): ?string
    {
        $d = self::fonnteDial($input);

        return $d['target'] ?? null;
    }
}
