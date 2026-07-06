<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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

        $gallery = $data['galleryImages']->map(fn ($img) => [
            'id' => $img->id,
            'url' => ApiMediaUrl::absolute($img->publicUrl()),
        ])->values();

        return response()->json([
            'brand' => [
                'name' => config('app.name'),
                'logo_url' => ApiMediaUrl::absolute(SiteBrand::logoPublicUrl()),
            ],
            'featured_muthowifs' => $featured,
            'gallery' => $gallery,
        ]);
    }
}
