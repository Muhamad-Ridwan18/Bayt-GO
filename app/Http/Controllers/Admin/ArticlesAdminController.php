<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Support\ArticleBodyMarkdown;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

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
        return view('admin.articles.create', [
            'article' => new Article,
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
            'loc.id.body_md' => ['required', 'string'],
            'loc.en.title' => ['required', 'string', 'max:255'],
            'loc.en.excerpt' => ['required', 'string', 'max:65535'],
            'loc.en.category' => ['required', 'string', 'max:120'],
            'loc.en.author' => ['required', 'string', 'max:120'],
            'loc.en.body_md' => ['required', 'string'],
            'loc.ar.title' => ['required', 'string', 'max:255'],
            'loc.ar.excerpt' => ['required', 'string', 'max:65535'],
            'loc.ar.category' => ['required', 'string', 'max:120'],
            'loc.ar.author' => ['required', 'string', 'max:120'],
            'loc.ar.body_md' => ['required', 'string'],
        ]);
    }

    /**
     * @return array<string, array{title: string, excerpt: string, category: string, author: string, body_md: string, body: string}>
     */
    private function buildTranslations(Request $request): array
    {
        $out = [];
        foreach (['id', 'en', 'ar'] as $locale) {
            app()->setLocale($locale);
            $row = $request->input('loc.'.$locale, []);
            $md = (string) ($row['body_md'] ?? '');
            $out[$locale] = [
                'title' => trim((string) ($row['title'] ?? '')),
                'excerpt' => trim((string) ($row['excerpt'] ?? '')),
                'category' => trim((string) ($row['category'] ?? '')),
                'author' => trim((string) ($row['author'] ?? '')),
                'body_md' => $md,
                'body' => ArticleBodyMarkdown::toHtml($md),
            ];
        }

        return $out;
    }
}
