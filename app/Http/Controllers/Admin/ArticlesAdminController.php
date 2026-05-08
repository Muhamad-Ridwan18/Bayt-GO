<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
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
        $translations = $this->buildTranslations($request);

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
        $translations = $this->buildTranslations($request);

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

    /**
     * CKEditor 4 expects an HTML page that calls CKEDITOR.tools.callFunction on the parent window.
     */
    private function ckeditorUploadResponse(int $funcNum, string $url, string $errorMessage): Response
    {
        $urlJson = json_encode($url, JSON_THROW_ON_ERROR | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES);
        $errJson = json_encode($errorMessage, JSON_THROW_ON_ERROR);

        $html = '<!DOCTYPE html><html><head><meta charset="utf-8"></head><body>'
            .'<script type="text/javascript">'
            .'window.parent.CKEDITOR.tools.callFunction('.$funcNum.', '.$urlJson.', '.$errJson.');'
            .'</script></body></html>';

        return response($html)->header('Content-Type', 'text/html; charset=utf-8');
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
            'loc.id.title' => ['required', 'string', 'max:255'],
            'loc.id.excerpt' => ['required', 'string', 'max:65535'],
            'loc.id.category' => ['required', 'string', 'max:120'],
            'loc.id.author' => ['required', 'string', 'max:120'],
            'loc.id.body' => ['required', 'string'],
            'loc.en.title' => ['nullable', 'string', 'max:255'],
            'loc.en.excerpt' => ['nullable', 'string', 'max:65535'],
            'loc.en.category' => ['nullable', 'string', 'max:120'],
            'loc.en.author' => ['nullable', 'string', 'max:120'],
            'loc.en.body' => ['nullable', 'string'],
            'loc.ar.title' => ['nullable', 'string', 'max:255'],
            'loc.ar.excerpt' => ['nullable', 'string', 'max:65535'],
            'loc.ar.category' => ['nullable', 'string', 'max:120'],
            'loc.ar.author' => ['nullable', 'string', 'max:120'],
            'loc.ar.body' => ['nullable', 'string'],
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
    private function buildTranslations(Request $request): array
    {
        $out = [];
        foreach (['id', 'en', 'ar'] as $locale) {
            app()->setLocale($locale);
            $row = $request->input('loc.'.$locale, []);
            $html = (string) ($row['body'] ?? '');
            $out[$locale] = [
                'title' => trim((string) ($row['title'] ?? '')),
                'excerpt' => trim((string) ($row['excerpt'] ?? '')),
                'category' => trim((string) ($row['category'] ?? '')),
                'author' => trim((string) ($row['author'] ?? '')),
                'body' => Purify::config('article')->clean($html),
                'body_md' => '',
            ];
        }

        return $out;
    }
}
