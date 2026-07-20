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
