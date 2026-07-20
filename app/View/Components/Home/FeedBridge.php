<?php

namespace App\View\Components\Home;

use App\ViewModels\Public\WelcomePageData;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

final class FeedBridge extends Component
{
    public WelcomePageData $page;

    /**
     * @param  list<array{title?: string, desc?: string, read?: string, fragment?: string}>  $homeGuideCards
     */
    public function __construct(
        ?string $homeHeroName = null,
        array $homeGuideCards = [],
        mixed $featuredMuthowifs = null,
        mixed $latestArticles = null,
        mixed $activeCampaigns = null,
        mixed $galleryImages = null,
        bool $showLandingChrome = true,
        int $muthowifLimit = 14,
    ) {
        $this->page = WelcomePageData::fromLegacy(
            heroName: $homeHeroName,
            guideCards: $homeGuideCards,
            featuredMuthowifs: $featuredMuthowifs,
            latestArticles: $latestArticles,
            activeCampaigns: $activeCampaigns,
            galleryImages: $galleryImages,
            showLandingChrome: $showLandingChrome,
            muthowifLimit: $muthowifLimit,
        );
    }

    public function render(): View
    {
        return view('components.home.feed', [
            'page' => $this->page,
        ]);
    }
}
