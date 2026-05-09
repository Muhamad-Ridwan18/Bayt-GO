<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Stevebauman\Purify\Facades\Purify;

class ArticlesAdminController extends Controller
{
    public function index(): View
    {
        $articles = Article::query()
            ->orderByDesc('updated_at')
            ->get();

        return view('admin.articles.index', [
            'articles' => $articles,
        ]);
    }

    public function create(): View
    {
        $article = new Article;

        return view('admin.articles.create', [
            'article' => $article,
            'articleEditorConfig' => $this->articleEditorConfig($article),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge(['published_at' => $request->input('published_at') ?: null]);
        $validated = $this->validatedBase($request);
        $translations = $this->buildTranslations($request, null);

        Article::query()->create([
            'slug' => $validated['slug'],
            'is_published' => $request->boolean('is_published'),
            'is_featured' => $request->boolean('is_featured'),
            'sort_order' => $validated['sort_order'],
            'published_at' => $validated['published_at'],
            'translations' => $translations,
        ]);

        return redirect()
            ->route('admin.articles.index')
            ->with('status', __('admin.articles.saved'));
    }

    public function edit(Article $article): View
    {
        return view('admin.articles.edit', [
            'article' => $article,
            'articleEditorConfig' => $this->articleEditorConfig($article),
        ]);
    }

    public function update(Request $request, Article $article): RedirectResponse
    {
        $request->merge(['published_at' => $request->input('published_at') ?: null]);
        $validated = $this->validatedBase($request, $article->id);
        $translations = $this->buildTranslations($request, $article);

        $article->update([
            'slug' => $validated['slug'],
            'is_published' => $request->boolean('is_published'),
            'is_featured' => $request->boolean('is_featured'),
            'sort_order' => $validated['sort_order'],
            'published_at' => $validated['published_at'],
            'translations' => $translations,
        ]);

        return redirect()
            ->route('admin.articles.edit', $article)
            ->with('status', __('admin.articles.saved'));
    }

    public function ckeditorUpload(Request $request): \Symfony\Component\HttpFoundation\Response
    {
        $request->validate([
            'upload' => [
                'required',
                'file',
                'max:10240',
                'mimes:jpg,jpeg,png,webp,gif,pdf',
            ],
        ]);

        $funcNum = (int) ($request->input('CKEditorFuncNum') ?? $request->query('CKEditorFuncNum') ?? 0);

        try {
            $file = $request->file('upload');
            if ($file === null) {
                if ($request->expectsJson()) {
                    return response()->json(['error' => __('admin.articles.upload_missing_file')], 422);
                }
                return $this->ckeditorUploadResponse($funcNum, '', __('admin.articles.upload_missing_file'));
            }

            $folder = 'articles/ckeditor/'.now()->format('Y/m');
            $path = $file->store($folder, 'public');
            $url = asset('storage/'.$path);

            if ($request->expectsJson()) {
                return response()->json(['url' => $url]);
            }
            return $this->ckeditorUploadResponse($funcNum, $url, '');
        } catch (\Throwable $e) {
            if ($request->expectsJson()) {
                return response()->json(['error' => __('admin.articles.upload_failed')], 500);
            }
            return $this->ckeditorUploadResponse($funcNum, '', __('admin.articles.upload_failed'));
        }
    }

    public function editorjsUpload(Request $request): JsonResponse
    {
        $request->validate([
            'image' => ['required', 'image', 'max:2048'], // Max 2MB
        ]);

        try {
            $file = $request->file('image');
            $folder = 'articles/images/'.now()->format('Y/m');
            $path = $file->store($folder, 'public');
            $url = asset('storage/'.$path);

            return response()->json([
                'success' => 1,
                'file' => [
                    'url' => $url,
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => 0,
                'message' => __('admin.articles.upload_failed'),
            ]);
        }
    }

    public function destroy(Article $article): RedirectResponse
    {
        $article->delete();

        return redirect()
            ->route('admin.articles.index')
            ->with('status', __('admin.articles.deleted'));
    }

    /**
     * @return array{slug: string, sort_order: int, published_at: ?Carbon}
     */
    private function validatedBase(Request $request, ?int $ignoreArticleId = null): array
    {
        $slugRules = [
            'required',
            'string',
            'max:120',
            'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
        ];
        $slugRules[] = $ignoreArticleId === null
            ? Rule::unique('articles', 'slug')
            : Rule::unique('articles', 'slug')->ignore($ignoreArticleId);

        return $request->validate([
            'slug' => $slugRules,
            'sort_order' => ['required', 'integer', 'min:0', 'max:99999'],
            'published_at' => ['nullable', 'date'],
            'loc' => ['required', 'array'],
            'loc.id.title'    => ['required', 'string', 'max:255'],
            'loc.id.excerpt'  => ['nullable', 'string', 'max:65535'],
            'loc.id.category' => ['nullable', 'string', 'max:120'],
            'loc.id.author'   => ['nullable', 'string', 'max:120'],
            'loc.id.body'     => ['nullable', 'string'],
            'loc.id.body_json' => ['nullable', 'string'],
            'loc.en.title'    => ['nullable', 'string', 'max:255'],
            'loc.en.excerpt'  => ['nullable', 'string', 'max:65535'],
            'loc.en.category' => ['nullable', 'string', 'max:120'],
            'loc.en.author'   => ['nullable', 'string', 'max:120'],
            'loc.en.body'     => ['nullable', 'string'],
            'loc.en.body_json' => ['nullable', 'string'],
            'loc.ar.title'    => ['nullable', 'string', 'max:255'],
            'loc.ar.excerpt'  => ['nullable', 'string', 'max:65535'],
            'loc.ar.category' => ['nullable', 'string', 'max:120'],
            'loc.ar.author'   => ['nullable', 'string', 'max:120'],
            'loc.ar.body'     => ['nullable', 'string'],
            'loc.ar.body_json' => ['nullable', 'string'],
        ]);
    }

    /**
     * Alpine.js initial state for admin article create / edit (live preview).
     *
     * @return array<string, mixed>
     */
    private function articleEditorConfig(Article $article): array
    {
        $activeLocale = 'id';
        $errors = session('errors');
        if ($errors) {
            if ($errors->has('loc.en.*')) {
                $activeLocale = 'en';
            } elseif ($errors->has('loc.ar.*')) {
                $activeLocale = 'ar';
            }
        }

        $config = [
            'activeLocale' => $activeLocale,
            'slug' => old('slug', $article->slug ?? ''),
            'publishedAt' => old('published_at', $article->published_at?->format('Y-m-d\TH:i') ?? ''),
            'dateFormatLocale' => str_replace('_', '-', app()->getLocale()),
            'locales' => [],
        ];

        foreach (['id', 'en', 'ar'] as $lc) {
            $fromOld = function (string $key) use ($article, $lc): string {
                $o = request()->old('loc.'.$lc.'.'.$key);
                if ($o !== null) {
                    return (string) $o;
                }
                if (! $article->exists) {
                    return '';
                }

                return (string) ($article->translationBlock($lc)[$key] ?? '');
            };

            $config['locales'][$lc] = [
                'title' => $fromOld('title'),
                'excerpt' => $fromOld('excerpt'),
                'category' => $fromOld('category'),
                'author' => $fromOld('author'),
                'bodyHtml' => $fromOld('body'),
                'bodyJson' => $fromOld('body_json'),
            ];
        }

        $config['labels'] = [
            'readingMinutes' => __('articles.reading_minutes', ['count' => '{n}']),
            'byAuthor' => __('articles.by_author', ['name' => '{name}']),
            'previewTitleFallback' => __('admin.articles.preview_title_fallback'),
        ];

        return $config;
    }

    /**
     * @return array<string, array<string, string>>
     */
    private function buildTranslations(Request $request, ?Article $existing = null): array
    {
        // Base: start from existing translations so we never wipe data the user didn't touch.
        $existingTranslations = $existing?->translations ?? [];

        $out = [];
        foreach (['id', 'en', 'ar'] as $locale) {
            app()->setLocale($locale);
            $row = $request->input('loc.'.$locale, []);

            $newTitle    = trim((string) ($row['title']    ?? ''));
            $newExcerpt  = trim((string) ($row['excerpt']  ?? ''));
            $newCategory = trim((string) ($row['category'] ?? ''));
            $newAuthor   = trim((string) ($row['author']   ?? ''));
            $newBody     = (string) ($row['body']     ?? '');
            $newBodyJson = (string) ($row['body_json'] ?? '');

            // If user left this locale completely empty, keep existing data (for edit).
            $isBlank = $newTitle === '' && $newExcerpt === '' && $newBody === '' && $newBodyJson === '';

            if ($isBlank && isset($existingTranslations[$locale])) {
                $out[$locale] = $existingTranslations[$locale];
                continue;
            }

            $out[$locale] = [
                'title'    => $newTitle,
                'excerpt'  => $newExcerpt,
                'category' => $newCategory,
                'author'   => $newAuthor,
                'body'     => Purify::config('article')->clean($newBody),
                'body_json' => $newBodyJson,
                'body_md'  => (string) ($existingTranslations[$locale]['body_md'] ?? ''),
            ];
        }

        return $out;
    }
}
