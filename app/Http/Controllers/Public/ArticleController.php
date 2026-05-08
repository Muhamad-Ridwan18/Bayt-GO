<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Article;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ArticleController extends Controller
{
    public function index(): View
    {
        $articles = Article::query()->published()->ordered()->get();

        return view('articles.index', [
            'articles' => $articles,
            'metaDescription' => __('articles.index_meta_description'),
        ]);
    }

    public function show(string $slug): View
    {
        $article = Article::query()
            ->published()
            ->where('slug', $slug)
            ->first();

        if ($article === null) {
            throw (new ModelNotFoundException)->setModel(Article::class, [$slug]);
        }

        $excerpt = strip_tags($article->localized('excerpt'));

        return view('articles.show', [
            'article' => $article,
            'metaDescription' => $excerpt !== '' ? $excerpt : strip_tags($article->localized('title')),
        ]);
    }
}
