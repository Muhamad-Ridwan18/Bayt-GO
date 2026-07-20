@props(['page'])

<div>
    <x-home.hero-search :page="$page" />

    <div class="relative bg-slate-50">
        @if ($page->hasCampaigns())
            <section class="home-section-pad pb-8 pt-8 sm:pb-10 sm:pt-10" id="customer-promo">
                <x-campaign-carousel :campaigns="$page->campaigns" />
            </section>
        @endif

        <x-home.muthowif-carousel :page="$page" />
        <x-home.gallery :page="$page" />

        @if ($page->showLandingChrome)
            <x-home.how-it-works :page="$page" />
            <x-home.trust-band />
        @endif

        <x-home.articles :page="$page" />

        @if ($page->showLandingChrome)
            <x-home.faq :page="$page" />
            <x-home.cta :page="$page" />
        @endif
    </div>
</div>
