<?php

namespace App\Http\Controllers\Public;

use App\Enums\MuthowifVerificationStatus;
use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\MuthowifPortfolioImage;
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

        // Fetch random portfolio images from approved muthowifs for the gallery strip
        $galleryImages = MuthowifPortfolioImage::query()
            ->join('muthowif_portfolios', 'muthowif_portfolio_images.muthowif_portfolio_id', '=', 'muthowif_portfolios.id')
            ->join('muthowif_profiles', 'muthowif_portfolios.muthowif_profile_id', '=', 'muthowif_profiles.id')
            ->where('muthowif_profiles.verification_status', MuthowifVerificationStatus::Approved)
            ->select('muthowif_portfolio_images.id', 'muthowif_portfolio_images.path')
            ->inRandomOrder()
            ->limit(30)
            ->get();

        return view('welcome', [
            'featuredMuthowifs' => $featuredMuthowifs,
            'latestArticles' => $latestArticles,
            'landingPages' => $landingPages,
            'latestServices' => $latestServices,
            'galleryImages' => $galleryImages,
        ]);
    }
}
