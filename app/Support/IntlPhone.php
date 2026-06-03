<?php

namespace App\Support;

use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumber;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;

final class IntlPhone
{
    private static ?PhoneNumberUtil $instance = null;

    /** @var array<string, list<array{d: string, iso: string, flag: string, name: string}>> */
    private static array $countriesForPickerCache = [];

    /** @var array<string, array<string, string>> */
    private static array $countryNameMaps = [];

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
     * Pecah nomor (bebas format) menjadi kode negara pemanggil + digit nomor nasional (tanpa 0 depan/trunk).
     *
     * @return array{dial: string, national: string}|null
     */
    public static function dialAndNational(?string $input): ?array
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

        $national = (string) $proto->getNationalNumber();

        return [
            'dial' => (string) $cc,
            'national' => $national,
        ];
    }

    /**
     * Kode negara pemanggil untuk wilayah default (.env PHONE_DEFAULT_REGION).
     */
    public static function defaultDialCode(): string
    {
        $util = self::util();
        $code = $util->getCountryCodeForRegion(self::defaultRegion());
        if ($code === 0) {
            return '62';
        }

        return (string) $code;
    }

    /**
     * Nomor untuk backend: gabung kode negara + digit lokal (hanya angka). Digit lokal boleh diawali 0 (dihapus untuk ID).
     */
    public static function mergeDialAndNational(string $dialDigits, string $localDigits): ?string
    {
        $d = preg_replace('/\D+/', '', $dialDigits) ?? '';
        $l = preg_replace('/\D+/', '', $localDigits) ?? '';
        if ($d === '' || $l === '') {
            return null;
        }

        if ($d === '62' && str_starts_with($l, '0')) {
            $l = substr($l, 1);
        }
        if ($l === '') {
            return null;
        }

        return '+'.$d.$l;
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
     * Variasi string yang mungkin tersimpan di DB atau diketik pengguna (08…, +62…, 62…, dll.).
     *
     * @return list<string>
     */
    public static function storageLookupVariants(string $normalized, ?string $phoneInput = null): array
    {
        $variants = [$normalized];

        if (str_starts_with($normalized, '62') && strlen($normalized) > 2) {
            $national = substr($normalized, 2);
            $variants[] = '0'.$national;
            $variants[] = $national;
            $variants[] = '+62'.$national;
            $variants[] = '62'.$national;
            $variants[] = '0062'.$national;
        }

        $variants[] = '+'.$normalized;

        if ($phoneInput !== null && trim($phoneInput) !== '') {
            $variants[] = trim($phoneInput);
        }

        return array_values(array_unique(array_filter(
            $variants,
            static fn (string $v): bool => $v !== ''
        )));
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
     * ISO 3166-1 alpha-2 untuk nomor valid, bila metadata mengenali region (berguna untuk kode pemanggil yang dipakai banyak negara, mis. +1).
     */
    public static function regionForNumber(?string $input): ?string
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

        $r = self::util()->getRegionCodeForNumber($proto);
        if (! is_string($r) || strlen($r) !== 2 || ! ctype_alpha($r)) {
            return null;
        }

        return strtoupper($r);
    }

    /**
     * Hanya digit “target” Fonnte tanpa konteks negara — gunakan {@see fonnteDial} bila mengirim lewat Fonnte API.
     */
    public static function forFonnte(?string $input): ?string
    {
        $d = self::fonnteDial($input);

        return $d['target'] ?? null;
    }

    /**
     * Semua region yang didukung libphonenumber + nama negara (data statis umpirsky/country-list, tanpa ext-intl).
     *
     * @return list<array{d: string, iso: string, flag: string, name: string}>
     */
    public static function countriesForPhonePicker(?string $displayLocale = null): array
    {
        $locale = $displayLocale ?? str_replace('_', '-', app()->getLocale());
        if (isset(self::$countriesForPickerCache[$locale])) {
            return self::$countriesForPickerCache[$locale];
        }

        $util = self::util();
        $rows = [];
        foreach ($util->getSupportedRegions() as $region) {
            if (! is_string($region) || strlen($region) !== 2 || ! ctype_alpha($region)) {
                continue;
            }
            $cc = $util->getCountryCodeForRegion($region);
            if ($cc === 0) {
                continue;
            }
            $iso = strtoupper($region);
            $name = self::countryDisplayName($iso, $locale);
            $rows[] = [
                'd' => (string) $cc,
                'iso' => $iso,
                'flag' => self::flagEmojiFromIso3166Alpha2($iso),
                'name' => $name,
            ];
        }

        usort($rows, static function (array $a, array $b): int {
            return strcasecmp($a['name'], $b['name']);
        });

        $priority = ['ID', 'SA', 'MY', 'SG', 'AE', 'QA', 'KW', 'BH', 'OM', 'YE', 'US', 'GB'];
        $byIso = [];
        foreach ($rows as $row) {
            $byIso[$row['iso']] = $row;
        }
        $ordered = [];
        foreach ($priority as $iso) {
            if (isset($byIso[$iso])) {
                $ordered[] = $byIso[$iso];
                unset($byIso[$iso]);
            }
        }
        $rest = array_values($byIso);
        usort($rest, static function (array $a, array $b): int {
            return strcasecmp($a['name'], $b['name']);
        });

        self::$countriesForPickerCache[$locale] = array_merge($ordered, $rest);

        return self::$countriesForPickerCache[$locale];
    }

    public static function flagEmojiFromIso3166Alpha2(string $iso3166Alpha2): string
    {
        $s = strtoupper($iso3166Alpha2);
        if (strlen($s) !== 2 || ! ctype_alpha($s)) {
            return '🌐';
        }

        return mb_chr(0x1F1E6 + ord($s[0]) - 65).mb_chr(0x1F1E6 + ord($s[1]) - 65);
    }

    private static function countryDisplayName(string $iso3166Alpha2, string $displayLocale): string
    {
        $iso = strtoupper($iso3166Alpha2);
        $map = self::countryNameMapForLocale($displayLocale);
        if (isset($map[$iso])) {
            return $map[$iso];
        }

        if (! str_starts_with(strtolower(str_replace('_', '-', $displayLocale)), 'en')) {
            $en = self::countryNameMapForLocale('en');
            if (isset($en[$iso])) {
                return $en[$iso];
            }
        }

        return $iso;
    }

    /**
     * @return array<string, string> ISO alpha-2 => localized country name
     */
    private static function countryNameMapForLocale(string $displayLocale): array
    {
        $primary = strtolower(explode('-', str_replace('_', '-', $displayLocale))[0]);
        if (isset(self::$countryNameMaps[$primary])) {
            return self::$countryNameMaps[$primary];
        }

        $path = base_path('vendor/umpirsky/country-list/data/'.$primary.'/country.php');
        if (! is_file($path)) {
            $map = self::countryNameMapForLocale('en');
            self::$countryNameMaps[$primary] = $map;

            return $map;
        }

        /** @var array<string, string> $map */
        $map = require $path;
        self::$countryNameMaps[$primary] = $map;

        return $map;
    }
}
