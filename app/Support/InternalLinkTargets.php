<?php

namespace App\Support;

use App\Models\Article;
use Illuminate\Support\Facades\Lang;

/**
 * Daftar halaman yang layak ditautkan dari dalam artikel.
 *
 * Dipakai automation konten sebagai tujuan tautan internal, dan sengaja hidup di
 * Laravel supaya daftarnya tidak tercecer di dalam prompt n8n yang tidak terversi.
 */
final class InternalLinkTargets
{
    /** Berapa artikel terbaru yang ditawarkan sebagai tujuan tautan. */
    private const MAX_ARTICLES = 40;

    /**
     * Halaman komersial: direktori muthowif, paket pendukung, dan landing SEO.
     *
     * @return list<array{url: string, path: string, title: string, description: string, kind: string}>
     */
    public static function commercial(): array
    {
        $targets = [
            self::target(
                route('layanan.index', [], true),
                (string) __('layanan.page_title'),
                self::translate('seo.directory_description'),
                'directory',
            ),
            self::target(
                route('layanan-pendukung.index', [], true),
                (string) __('layanan_pendukung.page_title'),
                self::translate('layanan_pendukung.page_subtitle'),
                'support_packages',
            ),
        ];

        foreach ((array) config('seo.landing_pages') as $keyword => $landing) {
            if (! is_array($landing)) {
                continue;
            }

            $targets[] = self::target(
                route('seo.landing', ['keyword' => $keyword], true),
                (string) ($landing['title'] ?? $keyword),
                (string) ($landing['subtitle'] ?? ''),
                'landing',
            );
        }

        return $targets;
    }

    /**
     * Hanya artikel terbit yang ditawarkan; menautkan ke draft berarti membuat
     * tautan internal yang 404.
     *
     * @return list<array{url: string, path: string, title: string, description: string, kind: string}>
     */
    public static function articles(): array
    {
        return Article::query()
            ->published()
            ->ordered()
            ->limit(self::MAX_ARTICLES)
            ->get()
            ->map(fn (Article $article) => self::target(
                ArticleUrl::show($article),
                $article->localized('title'),
                $article->seoDescription(110),
                'article',
            ))
            ->filter(fn (array $t) => $t['title'] !== '')
            ->values()
            ->all();
    }

    /**
     * @return list<array{url: string, path: string, title: string, description: string, kind: string}>
     */
    public static function all(): array
    {
        return array_merge(self::articles(), self::commercial());
    }

    /**
     * @return array{url: string, path: string, title: string, description: string, kind: string}
     */
    private static function target(string $url, string $title, string $description, string $kind): array
    {
        return [
            'url' => $url,
            'path' => (string) (parse_url($url, PHP_URL_PATH) ?: '/'),
            'title' => trim($title),
            'description' => trim($description),
            'kind' => $kind,
        ];
    }

    private static function translate(string $key): string
    {
        return Lang::has($key) ? (string) __($key) : '';
    }
}
