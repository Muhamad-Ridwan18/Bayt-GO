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
            ->orderByDesc('booking_reviews_count')
            ->orderByDesc('verified_at')
            ->limit(14)
            ->get();

        $latestArticles = Article::query()
            ->published()
            ->orderByDesc('published_at')
            ->limit(3)
            ->get();

        return view('welcome', [
            'featuredMuthowifs' => $featuredMuthowifs,
            'latestArticles' => $latestArticles,
        ]);
    }
}
