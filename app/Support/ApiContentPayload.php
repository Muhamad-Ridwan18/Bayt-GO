<?php

namespace App\Support;

use App\Models\Article;
use App\Models\Campaign;

final class ApiContentPayload
{
    public static function article(Article $article, bool $withBody = false): array
    {
        $body = $article->localized('body');
        $thumbnail = null;
        if (preg_match('/<img[^>]+src=["\']([^"\']+)["\']/', $body, $m)) {
            $thumbnail = ApiMediaUrl::absolute($m[1]);
        }

        $payload = [
            'slug' => $article->slug,
            'title' => $article->localized('title'),
            'excerpt' => $article->localized('excerpt'),
            'category' => $article->localized('category') ?: null,
            'author' => $article->localized('author') ?: null,
            'thumbnail' => $thumbnail,
            'published_at' => $article->published_at?->toIso8601String(),
            'reading_minutes' => $article->readingMinutes(),
        ];

        if ($withBody) {
            $payload['body'] = self::plainText($body);
            $payload['images'] = self::htmlImages($body);
        }

        return $payload;
    }

    public static function campaign(Campaign $campaign, bool $withBody = false): array
    {
        $payload = [
            'slug' => $campaign->slug,
            'title' => $campaign->title,
            'banner_url' => ApiMediaUrl::publicDisk($campaign->mobile_banner ?: $campaign->desktop_banner),
            'theme_color' => $campaign->theme_color ?: '#10b981',
            'cta_text' => $campaign->cta_text ?: null,
            'cta_url' => $campaign->cta_url ?: null,
            'start_date' => $campaign->start_date?->toIso8601String(),
            'end_date' => $campaign->end_date?->toIso8601String(),
        ];

        if ($withBody) {
            $payload['body'] = self::plainText((string) $campaign->body);
        }

        return $payload;
    }

    private static function plainText(?string $html): string
    {
        if (! filled($html)) {
            return '';
        }

        $withBreaks = preg_replace('/<(br|p|h[1-6]|li|div|tr)\b[^>]*>/i', "\n", $html) ?? $html;
        $text = html_entity_decode(strip_tags($withBreaks), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace("/[ \t]+/", ' ', $text) ?? $text;
        $text = preg_replace("/\n{3,}/", "\n\n", $text) ?? $text;

        return trim($text);
    }

    /**
     * @return list<string>
     */
    private static function htmlImages(?string $html): array
    {
        if (! filled($html) || ! preg_match_all('/<img[^>]+src=["\']([^"\']+)["\']/', $html, $matches)) {
            return [];
        }

        return collect($matches[1] ?? [])
            ->map(fn ($url) => ApiMediaUrl::absolute($url))
            ->filter()
            ->values()
            ->all();
    }
}
