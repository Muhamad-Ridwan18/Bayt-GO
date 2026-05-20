<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\MuthowifProfile;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Str;

class ArticleController extends Controller
{
    public function index(): View
    {
        $articles = Article::query()->published()->ordered()->get();

        $seoTitle = "Tips & Panduan Ibadah Umroh & Haji Terpercaya";
        $seoDesc = "Kumpulan artikel edukasi terbaru, tips praktis, panduan ibadah Umroh dan Haji, serta panduan memilih asisten Muthowif & jasa tour guide terbaik dari Bayt-GO.";

        return view('articles.index', [
            'articles' => $articles,
            'title' => $seoTitle,
            'metaDescription' => $seoDesc,
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
        $relatedServices = $this->relatedServicesForArticle($article);

        return view('articles.show', [
            'article' => $article,
            'metaDescription' => $excerpt !== '' ? $excerpt : strip_tags($article->localized('title')),
            'relatedServices' => $relatedServices,
        ]);
    }

    private function relatedServicesForArticle(Article $article)
    {
        $text = Str::lower($article->localized('title') . ' ' . $article->localized('excerpt') . ' ' . strip_tags($article->localized('body')));

        $query = MuthowifProfile::query()->approved();

        if (Str::contains($text, 'jakarta')) {
            $query->where('city', 'Jakarta');
        } elseif (Str::contains($text, 'madinah')) {
            $query->where('city', 'Madinah');
        } elseif (Str::contains($text, 'bahasa indonesia') || Str::contains($text, 'indonesia')) {
            $query->whereJsonContains('languages', 'Bahasa Indonesia');
        }

        $services = $query->orderByDesc('updated_at')->limit(5)->get();

        if ($services->isEmpty()) {
            return MuthowifProfile::query()
                ->approved()
                ->orderByDesc('updated_at')
                ->limit(5)
                ->get();
        }

        return $services;
    }
}
