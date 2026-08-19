<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreExternalArticleRequest;
use App\Models\Article;
use App\Services\UploadedImageOptimizer;
use App\Support\ArticleBodyMarkdown;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Stevebauman\Purify\Facades\Purify;

class ArticleApiController extends Controller
{
    public function store(StoreExternalArticleRequest $request): JsonResponse
    {
        $existing = $request->existingArticle();
        if ($existing !== null) {
            $existing->update($this->payload($request, $existing));

            return response()->json([
                'created' => false,
                'data' => $this->serialize($existing->fresh()),
            ]);
        }

        $article = Article::query()->create($this->payload($request));

        return response()->json([
            'created' => true,
            'data' => $this->serialize($article),
        ], 201);
    }

    public function update(StoreExternalArticleRequest $request, Article $article): JsonResponse
    {
        $article->update($this->payload($request, $article));

        return response()->json([
            'created' => false,
            'data' => $this->serialize($article->fresh()),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(StoreExternalArticleRequest $request, ?Article $existing = null): array
    {
        $validated = $request->validated();
        $title = trim((string) ($validated['translations']['id']['title'] ?? $validated['title'] ?? $existing?->translationBlock('id')['title'] ?? ''));
        $slug = trim((string) ($validated['slug'] ?? ''));
        if ($slug === '') {
            $slug = $existing?->slug ?: $this->uniqueSlug($title, $existing?->id);
        }

        $isPublished = array_key_exists('is_published', $validated)
            ? $request->boolean('is_published')
            : ($existing?->is_published ?? true);
        $publishedAt = $validated['published_at'] ?? $existing?->published_at;
        if ($publishedAt === null && $isPublished) {
            $publishedAt = now();
        }

        return [
            'slug' => $slug,
            'is_published' => $isPublished,
            'is_featured' => array_key_exists('is_featured', $validated)
                ? $request->boolean('is_featured')
                : ($existing?->is_featured ?? false),
            'sort_order' => (int) ($validated['sort_order'] ?? $existing?->sort_order ?? 0),
            'published_at' => $publishedAt,
            'translations' => $this->buildTranslations($request, $existing),
        ];
    }

    /**
     * @return array<string, array<string, string>>
     */
    private function buildTranslations(StoreExternalArticleRequest $request, ?Article $existing): array
    {
        $existingTranslations = $existing?->translations ?? [];
        $imageHtml = $this->coverImageHtml($request);

        $out = [];
        foreach (['id', 'en', 'ar'] as $locale) {
            $row = $request->input('translations.'.$locale, []);
            if (! is_array($row)) {
                $row = [];
            }

            $title = trim((string) ($row['title'] ?? ''));
            $excerpt = trim((string) ($row['excerpt'] ?? ''));
            $category = trim((string) ($row['category'] ?? ''));
            $author = trim((string) ($row['author'] ?? ''));
            $body = (string) ($row['body'] ?? '');
            $bodyMd = (string) ($row['body_md'] ?? '');

            $isBlank = $title === '' && $excerpt === '' && $body === '' && $bodyMd === '';
            if ($isBlank && isset($existingTranslations[$locale]) && is_array($existingTranslations[$locale])) {
                $out[$locale] = $existingTranslations[$locale];
                if ($locale === 'id' && $imageHtml !== '') {
                    $out[$locale]['body'] = $imageHtml.($out[$locale]['body'] ?? '');
                }

                continue;
            }

            [$cleanBody, $storedMd] = $this->htmlAndMarkdown(
                $body,
                $bodyMd,
                (string) ($existingTranslations[$locale]['body_md'] ?? ''),
            );

            if ($locale === 'id' && $imageHtml !== '') {
                $cleanBody = $imageHtml.$cleanBody;
            }

            $out[$locale] = [
                'title' => $title,
                'excerpt' => $excerpt,
                'category' => $category,
                'author' => $author,
                'body' => $cleanBody,
                'body_json' => (string) ($existingTranslations[$locale]['body_json'] ?? ''),
                'body_md' => $storedMd,
            ];
        }

        return $out;
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function htmlAndMarkdown(string $body, string $bodyMd, string $existingMd): array
    {
        $markdown = trim($bodyMd);
        $raw = trim($body);

        if ($markdown !== '') {
            return [
                Purify::config('article')->clean(ArticleBodyMarkdown::toHtml($markdown)),
                $markdown,
            ];
        }

        if ($raw !== '' && ! preg_match('/<[a-z][\s\S]*>/i', $raw)) {
            return [
                Purify::config('article')->clean(ArticleBodyMarkdown::toHtml($raw)),
                $raw,
            ];
        }

        return [
            Purify::config('article')->clean($body),
            $existingMd,
        ];
    }

    private function coverImageHtml(StoreExternalArticleRequest $request): string
    {
        $file = $request->file('image');
        if ($file !== null) {
            return $this->storedImageHtml($file);
        }

        $imageUrl = trim((string) $request->input('image_url', ''));
        if ($imageUrl === '') {
            return '';
        }

        return $this->storedImageHtml($this->downloadImage($imageUrl));
    }

    private function storedImageHtml(UploadedFile $file): string
    {
        $folder = 'articles/images/'.now()->format('Y/m');
        $path = app(UploadedImageOptimizer::class)->store($file, $folder, 'public', 'content');

        return '<p><img src="'.e(asset('storage/'.$path)).'" alt=""></p>';
    }

    private function downloadImage(string $url): UploadedFile
    {
        $host = strtolower((string) (parse_url($url, PHP_URL_HOST) ?: ''));
        if ($host === '' || $this->isBlockedImageHost($host)) {
            throw ValidationException::withMessages([
                'image_url' => 'URL gambar tidak diizinkan.',
            ]);
        }

        try {
            $response = Http::timeout(15)
                ->withOptions(['allow_redirects' => false])
                ->get($url);
        } catch (\Throwable) {
            throw ValidationException::withMessages([
                'image_url' => 'Gagal mengunduh gambar.',
            ]);
        }

        if (! $response->successful()) {
            throw ValidationException::withMessages([
                'image_url' => 'Gagal mengunduh gambar.',
            ]);
        }

        $contents = $response->body();
        if ($contents === '' || strlen($contents) > 10 * 1024 * 1024) {
            throw ValidationException::withMessages([
                'image_url' => 'Ukuran gambar tidak valid (maks 10MB).',
            ]);
        }

        $mime = strtolower((string) ($response->header('Content-Type') ?: ''));
        $mime = trim(explode(';', $mime)[0]);
        $extension = match ($mime) {
            'image/jpeg', 'image/jpg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
            default => null,
        };
        if ($extension === null) {
            throw ValidationException::withMessages([
                'image_url' => 'URL harus mengarah ke file gambar.',
            ]);
        }

        $tmp = tempnam(sys_get_temp_dir(), 'artimg');
        if ($tmp === false) {
            throw ValidationException::withMessages([
                'image_url' => 'Gagal mengunduh gambar.',
            ]);
        }
        file_put_contents($tmp, $contents);

        return new UploadedFile($tmp, 'cover.'.$extension, $mime, UPLOAD_ERR_OK, true);
    }

    private function isBlockedImageHost(string $host): bool
    {
        $host = trim($host, '[]');
        $blocked = [
            'localhost',
            '127.0.0.1',
            '0.0.0.0',
            '::1',
            '169.254.169.254',
            'metadata.google.internal',
        ];
        if (in_array($host, $blocked, true) || str_ends_with($host, '.localhost')) {
            return true;
        }

        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false;
        }

        return false;
    }

    private function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title);
        if ($base === '') {
            $base = 'artikel-'.now()->format('YmdHis');
        }
        $base = Str::limit($base, 120, '');

        $candidate = $base;
        $i = 2;
        while (
            Article::query()
                ->where('slug', $candidate)
                ->when($ignoreId !== null, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $suffix = '-'.$i;
            $candidate = Str::limit($base, 120 - strlen($suffix), '').$suffix;
            $i++;
        }

        return $candidate;
    }

    /**
     * @return array<string, mixed>
     */
    private function serialize(Article $article): array
    {
        return [
            'id' => $article->id,
            'slug' => $article->slug,
            'url' => route('articles.show', $article->slug),
            'is_published' => $article->is_published,
            'is_featured' => $article->is_featured,
            'sort_order' => $article->sort_order,
            'published_at' => $article->published_at?->toIso8601String(),
            'translations' => $article->translations,
        ];
    }
}
