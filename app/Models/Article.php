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
        $block = $translations[$active] ?? $translations['id'] ?? $translations['en'] ?? [];

        return is_array($block) ? $block : [];
    }

    public function localized(string $key): string
    {
        $block = $this->translationBlock();

        return (string) ($block[$key] ?? '');
    }

    public function readingMinutes(): int
    {
        $words = str_word_count(strip_tags($this->localized('body')));

        return max(1, (int) ceil($words / 200));
    }
}
