{{-- Builds $articleEditorConfig for Alpine (requires $article). --}}
@php
    /** @var \App\Models\Article $article */
    $articleEditorConfig = [
        'slug' => old('slug', $article->slug ?? ''),
        'publishedAt' => old('published_at', $article->published_at?->format('Y-m-d\TH:i') ?? ''),
        'dateFormatLocale' => str_replace('_', '-', app()->getLocale()),
        'locales' => [],
    ];
    foreach (['id', 'en', 'ar'] as $lc) {
        $oldKey = fn (string $k) => request()->old('loc.'.$lc.'.'.$k);
        $fromOld = function (string $k) use ($oldKey, $article, $lc): string {
            $o = $oldKey($k);
            if ($o !== null) {
                return (string) $o;
            }
            if (! $article->exists) {
                return '';
            }

            return (string) ($article->translationBlock($lc)[$k] ?? '');
        };

        $articleEditorConfig['locales'][$lc] = [
            'title' => $fromOld('title'),
            'excerpt' => $fromOld('excerpt'),
            'category' => $fromOld('category'),
            'author' => $fromOld('author'),
            'bodyHtml' => $fromOld('body'),
        ];
    }
    $articleEditorConfig['labels'] = [
        'readingMinutes' => __('articles.reading_minutes', ['count' => '{n}']),
        'byAuthor' => __('articles.by_author', ['name' => '{name}']),
        'previewTitleFallback' => __('admin.articles.preview_title_fallback'),
    ];
@endphp
