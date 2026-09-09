<?php

namespace App\Support;

use App\Models\Article;

/**
 * Artikel punya URL terpisah per bahasa. Bahasa Indonesia memakai URL akar
 * (`/artikel`), bahasa lain memakai prefix (`/en/artikel`).
 */
final class ArticleUrl
{
    /** @var list<string> */
    public const LOCALES = ['id', 'en'];

    public const DEFAULT_LOCALE = 'id';

    public static function index(?string $locale = null, bool $absolute = true): string
    {
        return route(self::routeName('articles.index', $locale), [], $absolute);
    }

    public static function show(Article|string $article, ?string $locale = null, bool $absolute = true): string
    {
        $slug = $article instanceof Article ? $article->slug : $article;

        return route(self::routeName('articles.show', $locale), $slug, $absolute);
    }

    /**
     * URL artikel dalam bahasa aktif bila terjemahannya ada, selain itu URL bahasa default.
     */
    public static function showBestFor(Article $article, ?string $locale = null): string
    {
        $locale = self::normalize($locale);

        return self::show($article, $article->hasTranslation($locale) ? $locale : self::DEFAULT_LOCALE);
    }

    /**
     * @return array<string, string>
     */
    public static function indexAlternates(bool $absolute = true): array
    {
        $alternates = [];
        foreach (self::LOCALES as $locale) {
            $alternates[$locale] = self::index($locale, $absolute);
        }

        return $alternates;
    }

    /**
     * Hanya bahasa dengan terjemahan asli yang diumumkan sebagai alternatif.
     *
     * @return array<string, string>
     */
    public static function showAlternates(Article $article, bool $absolute = true): array
    {
        $alternates = [];
        foreach (self::LOCALES as $locale) {
            if ($article->hasTranslation($locale)) {
                $alternates[$locale] = self::show($article, $locale, $absolute);
            }
        }

        return $alternates;
    }

    /**
     * URL padanan halaman artikel yang sedang dibuka dalam bahasa lain, atau null jika
     * request ini bukan halaman artikel / terjemahannya tidak ada.
     */
    public static function currentAlternate(string $locale): ?string
    {
        $route = request()->route();
        $name = $route?->getName();

        if ($name === null) {
            return null;
        }

        $base = str_starts_with($name, 'en.') ? substr($name, 3) : $name;

        if ($base === 'articles.index') {
            return self::index($locale);
        }

        if ($base !== 'articles.show') {
            return null;
        }

        $article = Article::query()
            ->published()
            ->where('slug', (string) $route->parameter('slug'))
            ->first();

        return $article?->hasTranslation($locale) ? self::show($article, $locale) : null;
    }

    private static function routeName(string $name, ?string $locale): string
    {
        $locale = self::normalize($locale);

        return $locale === self::DEFAULT_LOCALE ? $name : $locale.'.'.$name;
    }

    private static function normalize(?string $locale): string
    {
        $locale ??= app()->getLocale();

        return in_array($locale, self::LOCALES, true) ? $locale : self::DEFAULT_LOCALE;
    }
}
