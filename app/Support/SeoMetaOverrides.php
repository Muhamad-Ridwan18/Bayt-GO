<?php

namespace App\Support;

use App\Models\SiteSetting;

/**
 * Judul dan deskripsi meta per halaman publik yang bisa ditimpa admin tanpa deploy.
 * Nilai kosong berarti memakai teks bawaan dari file bahasa.
 */
final class SeoMetaOverrides
{
    public const TITLE_MAX = 70;

    public const DESCRIPTION_MAX = 170;

    private const PREFIX = 'seo_meta_';

    /** @var array<string, string>|null */
    private static ?array $cache = null;

    /**
     * Halaman yang judul/deskripsinya berasal dari file bahasa, jadi aman ditimpa
     * satu nilai per halaman. Halaman per-record (artikel, paket, campaign) tidak
     * masuk daftar karena metanya ikut isi masing-masing record.
     *
     * @return array<string, array{label: string, url: string}>
     */
    public static function pages(): array
    {
        return [
            'home' => ['label' => 'admin.seo_meta.pages.home', 'url' => route('welcome')],
            'directory' => ['label' => 'admin.seo_meta.pages.directory', 'url' => route('layanan.index')],
            'articles' => ['label' => 'admin.seo_meta.pages.articles', 'url' => route('articles.index')],
            'terms' => ['label' => 'admin.seo_meta.pages.terms', 'url' => route('terms')],
            'landing_jakarta' => ['label' => 'admin.seo_meta.pages.landing_jakarta', 'url' => route('seo.landing', 'jakarta')],
            'landing_madinah' => ['label' => 'admin.seo_meta.pages.landing_madinah', 'url' => route('seo.landing', 'madinah')],
            'landing_bahasa-indonesia' => ['label' => 'admin.seo_meta.pages.landing_bahasa_indonesia', 'url' => route('seo.landing', 'bahasa-indonesia')],
        ];
    }

    /** @return list<string> */
    public static function locales(): array
    {
        return ['id', 'en'];
    }

    public static function title(string $page, string $fallback, ?string $locale = null): string
    {
        return self::resolve($page, 'title', $locale) ?? $fallback;
    }

    public static function description(string $page, string $fallback, ?string $locale = null): string
    {
        return self::resolve($page, 'description', $locale) ?? $fallback;
    }

    /**
     * @return array<string, array<string, array{title: string, description: string}>>
     */
    public static function valuesForForm(): array
    {
        $values = [];
        foreach (array_keys(self::pages()) as $page) {
            foreach (self::locales() as $locale) {
                $values[$page][$locale] = [
                    'title' => self::resolve($page, 'title', $locale) ?? '',
                    'description' => self::resolve($page, 'description', $locale) ?? '',
                ];
            }
        }

        return $values;
    }

    /**
     * @return array<string, list<string>>
     */
    public static function validationRules(): array
    {
        $rules = [];
        foreach (array_keys(self::pages()) as $page) {
            foreach (self::locales() as $locale) {
                $rules[self::inputName($page, $locale, 'title')] = ['nullable', 'string', 'max:'.self::TITLE_MAX];
                $rules[self::inputName($page, $locale, 'description')] = ['nullable', 'string', 'max:'.self::DESCRIPTION_MAX];
            }
        }

        return $rules;
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public static function saveFromInput(array $input): void
    {
        foreach (array_keys(self::pages()) as $page) {
            foreach (self::locales() as $locale) {
                foreach (['title', 'description'] as $field) {
                    $value = trim((string) ($input[self::inputName($page, $locale, $field)] ?? ''));
                    SiteSetting::putValue(self::settingKey($page, $locale, $field), $value === '' ? null : $value);
                }
            }
        }

        self::$cache = null;
    }

    public static function inputName(string $page, string $locale, string $field): string
    {
        return str_replace('-', '_', $page).'__'.$locale.'__'.$field;
    }

    private static function resolve(string $page, string $field, ?string $locale): ?string
    {
        $locale ??= app()->getLocale();
        $value = self::all()[self::settingKey($page, $locale, $field)] ?? null;

        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }

    private static function settingKey(string $page, string $locale, string $field): string
    {
        return self::PREFIX.$page.'_'.$locale.'_'.$field;
    }

    /**
     * Dibaca sekali per request; jumlah barisnya kecil dan selalu dipakai bersamaan.
     *
     * @return array<string, string>
     */
    private static function all(): array
    {
        return self::$cache ??= SiteSetting::query()
            ->where('key', 'like', self::PREFIX.'%')
            ->pluck('value', 'key')
            ->all();
    }
}
