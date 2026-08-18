<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Campaign;
use App\Support\ApiContentPayload;
use App\Support\ApiMuthowifCard;
use App\Support\ApiMediaUrl;
use App\Support\SiteBrand;
use App\Support\WelcomePageCache;
use Illuminate\Http\JsonResponse;

class HomeApiController extends Controller
{
    public function index(): JsonResponse
    {
        $data = WelcomePageCache::data();

        $featured = $data['featuredMuthowifs']->map(
            fn ($profile) => ApiMuthowifCard::fromProfile($profile)
        )->values();

        $gallery = $data['galleryImages']->map(function ($img) {
            $profile = $img->portfolio?->muthowifProfile;

            return [
                'id' => $img->id,
                'url' => ApiMediaUrl::absolute($img->publicUrl()),
                'caption' => $img->portfolio?->title ?: ($profile?->user?->name),
                'muthowif_id' => $profile?->id,
            ];
        })->values();

        $campaigns = $data['activeCampaigns']
            ->map(fn (Campaign $campaign) => ApiContentPayload::campaign($campaign))
            ->values();

        $articles = $data['latestArticles']
            ->take(4)
            ->map(fn (Article $article) => ApiContentPayload::article($article))
            ->values();

        return response()->json([
            'brand' => [
                'name' => config('app.name'),
                'logo_url' => ApiMediaUrl::absolute(SiteBrand::logoPublicUrl()),
                'contact_label' => filled(SiteBrand::contactRaw()) ? SiteBrand::contactRaw() : null,
                'contact_url' => SiteBrand::contactWhatsappUrl(),
            ],
            'featured_muthowifs' => $featured,
            'gallery' => $gallery,
            'campaigns' => $campaigns,
            'articles' => $articles,
            'work' => [
                'title' => (string) __('welcome.work_title'),
                'subtitle' => (string) __('welcome.work_sub'),
                'steps' => self::langList('welcome.work_steps'),
            ],
            'faq' => [
                'title' => (string) __('welcome.faq_title'),
                'items' => self::langList('welcome.faq_items'),
            ],
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function langList(string $key): array
    {
        $value = __($key);

        return is_array($value) ? array_values($value) : [];
    }
}
