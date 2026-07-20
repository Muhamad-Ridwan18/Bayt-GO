<?php

namespace App\Support;

final class AffiliateBankOptions
{
    /**
     * @return array<string, array{label: string, slug: string}>
     */
    public static function catalog(): array
    {
        return [
            'BCA' => ['label' => 'Bank Central Asia (BCA)', 'slug' => 'bca'],
            'BNI' => ['label' => 'Bank Negara Indonesia (BNI)', 'slug' => 'bni'],
            'BRI' => ['label' => 'Bank Rakyat Indonesia (BRI)', 'slug' => 'bri'],
            'Mandiri' => ['label' => 'Bank Mandiri', 'slug' => 'mandiri'],
            'BSI' => ['label' => 'Bank Syariah Indonesia (BSI)', 'slug' => 'bsi'],
            'CIMB Niaga' => ['label' => 'CIMB Niaga', 'slug' => 'cimb-niaga'],
            'Permata' => ['label' => 'Permata Bank', 'slug' => 'permata'],
            'Danamon' => ['label' => 'Bank Danamon', 'slug' => 'danamon'],
            'BTN' => ['label' => 'Bank BTN', 'slug' => 'btn'],
            'OCBC NISP' => ['label' => 'OCBC NISP', 'slug' => 'ocbc-nisp'],
            'Maybank' => ['label' => 'Maybank Indonesia', 'slug' => 'maybank'],
            'Bank Muamalat' => ['label' => 'Bank Muamalat', 'slug' => 'muamalat'],
        ];
    }

    /** @return array<string, string> */
    public static function all(): array
    {
        $out = [];
        foreach (self::catalog() as $code => $meta) {
            $out[$code] = $meta['label'];
        }

        return $out;
    }

    public static function label(string $code): string
    {
        return self::catalog()[$code]['label'] ?? $code;
    }

    public static function slug(string $code): ?string
    {
        return self::catalog()[$code]['slug'] ?? self::guessSlug($code);
    }

    public static function logoPath(string $code): ?string
    {
        $slug = self::slug($code);
        if ($slug === null) {
            return null;
        }

        $relative = 'images/banks/'.$slug.'.svg';
        if (! is_file(public_path($relative))) {
            return null;
        }

        return $relative;
    }

    public static function logoUrl(string $code): ?string
    {
        $path = self::logoPath($code);

        return $path !== null ? asset($path) : null;
    }

    public static function hasLogo(string $code): bool
    {
        return self::logoPath($code) !== null;
    }

    /**
     * @return list<array{code: string, label: string, slug: string, logo_url: string|null}>
     */
    public static function optionsWithLogos(): array
    {
        $out = [];
        foreach (self::catalog() as $code => $meta) {
            $out[] = [
                'code' => $code,
                'label' => $meta['label'],
                'slug' => $meta['slug'],
                'logo_url' => self::logoUrl($code),
            ];
        }

        return $out;
    }

    /**
     * Map payment method id / bank label / Moota bank_type → katalog code.
     */
    public static function resolveCodeFromHint(?string $hint): ?string
    {
        if ($hint === null) {
            return null;
        }

        $raw = trim($hint);
        if ($raw === '') {
            return null;
        }

        if (isset(self::catalog()[$raw])) {
            return $raw;
        }

        $key = strtolower($raw);
        $key = str_replace([' ', '_'], '', $key);

        $map = [
            'vabca' => 'BCA',
            'bca' => 'BCA',
            'bcagiro' => 'BCA',
            'bcasyariah' => 'BCA',
            'vabni' => 'BNI',
            'bni' => 'BNI',
            'bnibisnis' => 'BNI',
            'bnisyariah' => 'BNI',
            'bnibisnissyariah' => 'BNI',
            'vabri' => 'BRI',
            'bri' => 'BRI',
            'bricms' => 'BRI',
            'brigiro' => 'BRI',
            'brisyariah' => 'BRI',
            'brisyariahcms' => 'BRI',
            'vapermata' => 'Permata',
            'permata' => 'Permata',
            'permatabank' => 'Permata',
            'vamandiribill' => 'Mandiri',
            'mandiri' => 'Mandiri',
            'mandirionline' => 'Mandiri',
            'mandiribisnis' => 'Mandiri',
            'mandirimcm' => 'Mandiri',
            'mandirimcm2' => 'Mandiri',
            'mandirisyariah' => 'Mandiri',
            'mandirisyariahbisnis' => 'Mandiri',
            'mandirisyariahmcm' => 'Mandiri',
            'bsi' => 'BSI',
            'bsigiro' => 'BSI',
            'banksyariahindonesia' => 'BSI',
            'cimb' => 'CIMB Niaga',
            'cimbniaga' => 'CIMB Niaga',
            'danamon' => 'Danamon',
            'btn' => 'BTN',
            'ocbc' => 'OCBC NISP',
            'ocbcnisp' => 'OCBC NISP',
            'maybank' => 'Maybank',
            'maybankindonesia' => 'Maybank',
            'muamalat' => 'Bank Muamalat',
            'bankmuamalat' => 'Bank Muamalat',
        ];

        if (isset($map[$key])) {
            return $map[$key];
        }

        foreach (self::catalog() as $code => $meta) {
            $slugKey = str_replace('-', '', $meta['slug']);
            if (str_contains($key, $slugKey) || str_contains($key, strtolower(str_replace(' ', '', $code)))) {
                return $code;
            }
        }

        return null;
    }

    public static function logoUrlForPaymentMethod(string $methodId): ?string
    {
        $code = match (true) {
            $methodId === 'va_bca' => 'BCA',
            $methodId === 'va_bni' => 'BNI',
            $methodId === 'va_bri' => 'BRI',
            $methodId === 'va_permata' => 'Permata',
            $methodId === 'va_mandiri_bill' => 'Mandiri',
            default => self::resolveCodeFromHint($methodId),
        };

        return $code !== null ? self::logoUrl($code) : null;
    }

    private static function guessSlug(string $code): ?string
    {
        $normalized = strtolower(trim($code));
        $normalized = str_replace([' ', '_'], '-', $normalized);

        $aliases = [
            'bank-muamalat' => 'muamalat',
            'muamalat' => 'muamalat',
            'cimb' => 'cimb-niaga',
            'ocbc' => 'ocbc-nisp',
            'bank-mandiri' => 'mandiri',
            'bank-bca' => 'bca',
            'bank-bni' => 'bni',
            'bank-bri' => 'bri',
            'bank-bsi' => 'bsi',
            'bank-btn' => 'btn',
            'bank-danamon' => 'danamon',
            'permata-bank' => 'permata',
        ];

        if (isset($aliases[$normalized])) {
            return $aliases[$normalized];
        }

        foreach (self::catalog() as $meta) {
            if ($meta['slug'] === $normalized) {
                return $meta['slug'];
            }
        }

        return preg_match('/^[a-z0-9\-]+$/', $normalized) ? $normalized : null;
    }
}
