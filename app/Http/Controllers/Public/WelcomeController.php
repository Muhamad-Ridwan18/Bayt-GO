<?php

namespace App\Http\Controllers\Public;

use App\Enums\MuthowifVerificationStatus;
use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\MuthowifProfile;
use Illuminate\View\View;

final class WelcomeController extends Controller
{
    public function __invoke(): View
    {
        $featuredMuthowifs = MuthowifProfile::query()
            ->with(['user:id,name', 'services:id,muthowif_profile_id,daily_price'])
            ->where('verification_status', MuthowifVerificationStatus::Approved)
            ->withCount('bookingReviews')
            ->withAvg('bookingReviews', 'rating')
            ->orderBy('booking_reviews_count')
            ->orderBy('verified_at')
            ->limit(14)
            ->get();

        $latestArticles = Article::query()
            ->published()
            ->orderByDesc('published_at')
            ->limit(3)
            ->get();

        $landingPages = collect(config('seo.landing_pages', []))
            ->map(fn ($landing, $slug) => array_merge($landing, ['slug' => $slug]));

        $latestServices = MuthowifProfile::query()
            ->approved()
            ->with('user:id,name')
            ->orderByDesc('updated_at')
            ->limit(4)
            ->get();

        return view('welcome', [
            'featuredMuthowifs' => $featuredMuthowifs,
            'latestArticles' => $latestArticles,
            'landingPages' => $landingPages,
            'latestServices' => $latestServices,
        ]);
    }
}
