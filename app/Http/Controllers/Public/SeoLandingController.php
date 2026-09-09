<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\MuthowifProfile;
use App\Services\Seo\SitemapGenerator;
use App\Support\SeoMetaOverrides;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Str;

class SeoLandingController extends Controller
{
    public function showKeyword(Request $request, string $keyword)
    {
        $landing = config("seo.landing_pages.{$keyword}");

        if (! $landing) {
            abort(404);
        }

        $landing = array_merge($landing, self::localizedLanding($keyword, $landing));

        // Meta dipisah dari teks hero supaya override admin tidak mengubah tampilan halaman.
        $page = 'landing_'.$keyword;
        $landing['meta_title'] = SeoMetaOverrides::title($page, (string) $landing['title']);
        $landing['meta_description'] = SeoMetaOverrides::description($page, (string) $landing['subtitle']);

        $services = MuthowifProfile::approved()
            ->when($landing['city'] ?? null, fn ($query, $city) => $query->where('city', $city))
            ->when($landing['language'] ?? null, fn ($query, $language) => $query->whereJsonContains('languages', $language))
            ->with(['user', 'services'])
            ->withMarketplaceStats()
            ->orderByMarketplaceRanking()
            ->limit(12)
            ->get();

        return view('seo.landing', [
            'landing' => $landing,
            'keyword' => $keyword,
            'services' => $services,
        ]);
    }

    /**
     * @param  array<string, mixed>  $landing
     * @return array<string, string>
     */
    private static function localizedLanding(string $keyword, array $landing): array
    {
        $translated = [];

        foreach (['title', 'subtitle'] as $key) {
            $langKey = "seo.landing_pages.{$keyword}.{$key}";
            if (Lang::has($langKey)) {
                $translated[$key] = __($langKey);
            }
        }

        return $translated;
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
