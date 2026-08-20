<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    protected $fillable = [
        'slug',
        'is_published',
        'is_featured',
        'sort_order',
        'published_at',
        'translations',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'is_featured' => 'boolean',
            'published_at' => 'datetime',
            'translations' => 'array',
        ];
    }

    /**
     * @param  Builder<Article>  $query
     * @return Builder<Article>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where('is_published', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    /**
     * @param  Builder<Article>  $query
     * @return Builder<Article>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query
            ->orderByDesc('is_featured')
            ->orderBy('sort_order')
            ->orderByDesc('published_at');
    }

    /**
     * @return array<string, mixed>
     */
    public function translationBlock(?string $locale = null): array
    {
        /** @var array<string, mixed> $translations */
        $translations = $this->translations ?? [];

        if ($locale !== null) {
            $block = $translations[$locale] ?? [];

            return is_array($block) ? $block : [];
        }

        $active = app()->getLocale();
        
        $locales = array_unique([$active, 'id', 'en', 'ar']);
        foreach ($locales as $lc) {
            $block = $translations[$lc] ?? null;
            if (is_array($block) && !empty(trim((string)($block['title'] ?? '')))) {
                return $block;
            }
        }

        return [];
    }

    public function localized(string $key): string
    {
        $block = $this->translationBlock();

        return (string) ($block[$key] ?? '');
    }

    public function coverImageUrl(): ?string
    {
        $body = $this->localized('body');
        if ($body === '' || ! preg_match('/<img[^>]+src=["\']([^"\']+)["\']/i', $body, $matches)) {
            return null;
        }

        $src = html_entity_decode(trim($matches[1]), ENT_QUOTES | ENT_HTML5);
        if ($src === '') {
            return null;
        }

        if (str_starts_with($src, 'http://') || str_starts_with($src, 'https://')) {
            return $src;
        }

        if (str_starts_with($src, '//')) {
            return 'https:'.$src;
        }

        return url($src);
    }

    public function seoDescription(int $limit = 155): string
    {
        $excerpt = trim(strip_tags($this->localized('excerpt')));
        if ($excerpt === '') {
            $excerpt = trim(strip_tags($this->localized('body')));
        }
        if ($excerpt === '') {
            $excerpt = trim(strip_tags($this->localized('title')));
        }

        return \Illuminate\Support\Str::limit($excerpt, $limit, '');
    }

    public function readingMinutes(): int
    {
        $words = str_word_count(strip_tags($this->localized('body')));

        return max(1, (int) ceil($words / 200));
    }
}
