@props(['page'])

<section id="tentang" class="home-section-pad scroll-mt-24 pb-8 pt-2 sm:pb-10">
    <div class="relative overflow-hidden rounded-3xl bg-baytgo px-6 py-10 text-white shadow-xl sm:px-10 sm:py-12">
        <div class="pointer-events-none absolute -right-16 -top-16 h-64 w-64 rounded-full bg-gold/15 blur-3xl" aria-hidden="true"></div>
        <div class="relative flex flex-col items-center gap-8 text-center lg:flex-row lg:justify-between lg:text-left">
            <div class="max-w-xl">
                <h2 class="text-2xl font-bold leading-snug sm:text-3xl">{{ __('welcome.landing_cta_title') }}</h2>
                <p class="mt-3 text-sm text-white/85 sm:text-base">{{ __('welcome.landing_cta_sub') }}</p>
                <dl class="mt-6 flex flex-wrap justify-center gap-6 lg:justify-start">
                    <div>
                        <dt class="text-2xl font-bold tabular-nums">500+</dt>
                        <dd class="text-xs text-white/75">{{ __('welcome.landing_stat_guides') }}</dd>
                    </div>
                    <div>
                        <dt class="text-2xl font-bold tabular-nums">10.000+</dt>
                        <dd class="text-xs text-white/75">{{ __('welcome.landing_stat_jamaah') }}</dd>
                    </div>
                    <div>
                        <dt class="text-2xl font-bold tabular-nums">4.9</dt>
                        <dd class="text-xs text-white/75">{{ __('welcome.landing_stat_rating') }}</dd>
                    </div>
                </dl>
            </div>
            <a href="{{ $page->layananIndexUrl }}" class="inline-flex shrink-0 items-center gap-2 rounded-xl bg-gold px-7 py-3.5 text-sm font-bold text-baytgo-950 shadow-lg transition hover:bg-gold-muted">
                {{ __('welcome.landing_hero_cta') }}
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5 15.75 12l-7.5 7.5"/></svg>
            </a>
        </div>
    </div>
</section>
