@props(['page'])

<section id="cara-kerja" class="home-section-pad scroll-mt-24 border-t border-slate-100 py-10 sm:py-12" aria-labelledby="welcome-work-heading">
    <x-home.section-heading
        align="center"
        :title="__('welcome.work_title')"
        title-id="welcome-work-heading"
        :subtitle="__('welcome.work_sub')"
    />
    <div class="grid gap-4 sm:grid-cols-3 sm:gap-6">
        @foreach ($page->workSteps as $i => $step)
            <article class="relative rounded-2xl border border-slate-100 bg-white p-5 text-center shadow-sm sm:p-6">
                <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-baytgo text-sm font-bold text-white">{{ $i + 1 }}</span>
                <h3 class="mt-4 text-base font-bold text-slate-900">{{ $step['title'] ?? '' }}</h3>
                <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ $step['desc'] ?? '' }}</p>
            </article>
        @endforeach
    </div>
</section>

<section class="relative left-1/2 right-1/2 -ml-[50vw] -mr-[50vw] w-screen bg-baytgo-950 py-12 sm:py-14">
    <div class="home-section-pad mx-auto max-w-6xl">
        <div class="grid gap-8 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.1fr)] lg:items-center lg:gap-10">
            <div>
                <h2 class="text-xl font-bold text-white sm:text-2xl">{{ __('welcome.landing_trust_title') }}</h2>
                <dl class="mt-6 flex flex-wrap gap-8">
                    <div>
                        <dt class="text-3xl font-bold tabular-nums text-gold-muted">10.000+</dt>
                        <dd class="mt-1 text-sm text-white/75">{{ __('welcome.landing_stat_jamaah') }}</dd>
                    </div>
                    <div>
                        <dt class="text-3xl font-bold tabular-nums text-gold-muted">4.9</dt>
                        <dd class="mt-1 text-sm text-white/75">{{ __('welcome.landing_stat_rating') }}</dd>
                    </div>
                </dl>
            </div>
            <blockquote class="rounded-2xl border border-white/10 bg-white/5 p-6 backdrop-blur-sm">
                <p class="text-sm leading-relaxed text-white/90 sm:text-base">&ldquo;{{ __('welcome.landing_trust_quote') }}&rdquo;</p>
                <footer class="mt-4 text-sm font-semibold text-gold-muted">&mdash; {{ __('welcome.landing_trust_quote_by') }}</footer>
            </blockquote>
        </div>
    </div>
</section>

{{ $slot }}

<section id="faq" class="home-section-pad scroll-mt-24 border-t border-slate-100 py-10 sm:py-12" aria-labelledby="welcome-faq-heading">
    <h2 id="welcome-faq-heading" class="mb-6 text-center text-xl font-bold text-baytgo sm:text-2xl">{{ __('welcome.faq_title') }}</h2>
    <div class="mx-auto max-w-3xl space-y-3" x-data="homeFaq()">
        @foreach ($page->faqItems as $i => $item)
            <div class="overflow-hidden rounded-2xl border border-slate-100 bg-white shadow-sm">
                <button type="button" class="flex w-full items-center justify-between gap-3 px-5 py-4 text-left" @click="toggle({{ $i }})">
                    <span class="text-sm font-semibold text-slate-900">{{ $item['q'] ?? '' }}</span>
                    <svg class="h-4 w-4 shrink-0 text-slate-400 transition" :class="open === {{ $i }} && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                </button>
                <div x-show="open === {{ $i }}" x-cloak class="border-t border-slate-100 px-5 pb-4 pt-3 text-sm leading-relaxed text-slate-600">{{ $item['a'] ?? '' }}</div>
            </div>
        @endforeach
    </div>
</section>

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
            <x-ui.button variant="gold" :href="$page->layananIndexUrl" class="w-auto">
                {{ __('welcome.landing_hero_cta') }}
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5 15.75 12l-7.5 7.5"/></svg>
            </x-ui.button>
        </div>
    </div>
</section>
