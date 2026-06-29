<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\WelcomePageCache;
use Illuminate\Http\JsonResponse;

class HomeApiController extends Controller
{
    public function index(): JsonResponse
    {
        $data = WelcomePageCache::data();

        $featured = $data['featuredMuthowifs']->map(function ($profile) {
            $minPrice = (int) round((float) ($profile->services->min('daily_price') ?? 0));

            return [
                'id' => $profile->id,
                'name' => $profile->user->name ?? 'Muthowif',
                'avatar' => $profile->photoUrl(),
                'rating' => $profile->average_rating !== null
                    ? number_format((float) $profile->average_rating, 1)
                    : null,
                'reviews' => (int) ($profile->booking_reviews_count ?? 0),
                'languages' => array_slice($profile->languagesForDisplay(), 0, 3),
                'start_price' => $minPrice,
            ];
        })->values();

        $gallery = $data['galleryImages']->map(fn ($img) => [
            'id' => $img->id,
            'url' => $img->publicUrl(),
        ])->values();

        return response()->json([
            'featured_muthowifs' => $featured,
            'gallery' => $gallery,
        ]);
    }
}
