@props(['page'])

<section id="customer-recommend" class="home-section-pad scroll-mt-24 pb-10 pt-8 sm:pb-12 sm:pt-10" aria-labelledby="customer-rec-heading">
    <x-home.section-heading
        :kicker="__('welcome.landing_badge_popular')"
        :title="__('dashboard.customer_popular_title')"
        title-id="customer-rec-heading"
        :subtitle="__('welcome.landing_popular_sub')"
        :href="$page->layananIndexUrl"
        :link-label="__('dashboard.customer_popular_see_all')"
    />

    @if (! $page->hasMuthowifs())
        <p class="rounded-2xl border border-dashed border-slate-200 bg-white py-14 text-center text-sm text-slate-600">{{ __('welcome.popular_empty') }}</p>
    @else
        <div class="relative" x-data="homeScrollTrack()">
            <button type="button" @click="scroll(-340)" class="absolute -left-1 top-[40%] z-10 hidden h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full border border-slate-200 bg-white text-baytgo shadow-lg transition hover:bg-slate-50 md:flex" aria-label="{{ __('welcome.carousel_prev') }}">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/></svg>
            </button>
            <button type="button" @click="scroll(340)" class="absolute -right-1 top-[40%] z-10 hidden h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full border border-slate-200 bg-white text-baytgo shadow-lg transition hover:bg-slate-50 md:flex" aria-label="{{ __('welcome.carousel_next') }}">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg>
            </button>
            <div class="-mx-1 flex gap-4 overflow-x-auto scroll-pl-4 px-1 pb-2 snap-x snap-mandatory [scrollbar-width:none] [&::-webkit-scrollbar]:hidden md:px-10" x-ref="track" style="-webkit-overflow-scrolling: touch;">
                @foreach ($page->muthowifCards as $card)
                    <x-home.muthowif-card :card="$card" />
                @endforeach
            </div>
        </div>
    @endif
</section>
