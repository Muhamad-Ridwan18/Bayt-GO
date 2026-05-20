<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\MuthowifProfile;
use App\Services\Seo\SitemapGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SeoLandingController extends Controller
{
    public function showKeyword(Request $request, string $keyword)
    {
        $landing = config("seo.landing_pages.{$keyword}");

        if (! $landing) {
            abort(404);
        }

        $services = MuthowifProfile::approved()
            ->when($landing['city'] ?? null, fn ($query, $city) => $query->where('city', $city))
            ->when($landing['language'] ?? null, fn ($query, $language) => $query->whereJsonContains('languages', $language))
            ->orderByDesc('updated_at')
            ->limit(12)
            ->get();

        return view('seo.landing', [
            'landing' => $landing,
            'keyword' => $keyword,
            'services' => $services,
        ]);
    }

    public function sitemapIndex(SitemapGenerator $generator)
    {
        return response($generator->generateIndex(), 200, ['Content-Type' => 'application/xml']);
    }

    public function sitemapPage(SitemapGenerator $generator, string $type, int $page = 1)
    {
        if (! in_array($type, ['home', 'categories', 'services', 'articles'], true)) {
            abort(404);
        }

        return response($generator->generatePage($type, $page), 200, ['Content-Type' => 'application/xml']);
    }
}
