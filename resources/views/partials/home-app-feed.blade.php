@php
    $homeHeroName = $homeHeroName ?? null;
    $homeHelpHref = $homeHelpHref ?? (Route::has('support.index') ? route('support.index') : route('layanan.index'));
    $homeGuideCards = $homeGuideCards ?? [];
    $activeCampaigns = $activeCampaigns ?? collect();
    $featuredMuthowifs = $featuredMuthowifs ?? collect();
    $latestArticles = $latestArticles ?? collect();
@endphp

{{-- Shared home feed (welcome + customer dashboard) --}}
<div>
    <section class="relative mb-6 overflow-hidden rounded-3xl bg-gradient-to-br from-sky-50 via-emerald-50/80 to-welcomeCanvas p-5 ring-1 ring-emerald-100/80 sm:p-6">
        <div class="pointer-events-none absolute inset-y-0 right-0 w-2/5 opacity-40" aria-hidden="true">
            <img src="{{ $welcomeHeroBg }}" alt="" class="h-full w-full object-cover object-[70%_30%]" loading="eager" decoding="async" />
            <div class="absolute inset-0 bg-gradient-to-l from-transparent via-sky-50/70 to-sky-50"></div>
        </div>
        <div class="relative max-w-lg">
            <h1 class="text-xl font-bold tracking-tight text-slate-900 sm:text-2xl">
                {{ __('dashboard.customer_hero_intro') }}
                <span aria-hidden="true">👋</span>
            </h1>
            <p class="mt-1.5 text-sm font-medium text-slate-700 sm:text-base">{{ __('dashboard.customer_hero_sub') }}</p>
            @if ($homeHeroName)
                <p class="mt-1 text-xs text-slate-500">{{ $homeHeroName }}</p>
            @endif
        </div>
    </section>

    <section class="mb-6" aria-label="{{ __('dashboard.customer_hero_sub') }}">
        <div class="grid grid-cols-3 gap-3 sm:grid-cols-5 sm:gap-4">
            <a href="{{ route('layanan.index') }}" class="group relative flex flex-col items-center rounded-2xl border border-slate-100 bg-white p-3.5 text-center shadow-sm ring-1 ring-slate-100/80 transition hover:-translate-y-0.5 hover:border-emerald-200 hover:shadow-md sm:p-4">
                <span class="absolute -top-2 left-1/2 z-10 -translate-x-1/2 rounded-full bg-gold px-2 py-0.5 text-[9px] font-bold uppercase tracking-wide text-baytgo-950 shadow-sm">{{ __('dashboard.customer_cat_utama') }}</span>
                <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-700 ring-1 ring-emerald-100 sm:h-14 sm:w-14">
                    <svg class="h-6 w-6 sm:h-7 sm:w-7" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25"/></svg>
                </span>
                <span class="mt-2.5 text-[11px] font-semibold leading-snug text-slate-800 sm:text-xs">{{ __('dashboard.customer_cat_umroh') }}</span>
            </a>

            <a href="{{ route('layanan-pendukung.index', ['category' => 'mobility']) }}" class="group flex flex-col items-center rounded-2xl border border-slate-100 bg-white p-3.5 text-center shadow-sm ring-1 ring-slate-100/80 transition hover:-translate-y-0.5 hover:border-emerald-200 hover:shadow-md sm:p-4">
                <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-teal-50 text-teal-700 ring-1 ring-teal-100 sm:h-14 sm:w-14">
                    <svg class="h-6 w-6 sm:h-7 sm:w-7" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0"/><circle cx="8" cy="18" r="2.25"/><path stroke-linecap="round" stroke-linejoin="round" d="M10.25 18H15a3 3 0 003-3v-2.25"/></svg>
                </span>
                <span class="mt-2.5 text-[11px] font-semibold leading-snug text-slate-800 sm:text-xs">{{ __('dashboard.customer_cat_wheelchair') }}</span>
            </a>

            <a href="{{ route('layanan-pendukung.index', ['category' => 'umrah']) }}" class="group flex flex-col items-center rounded-2xl border border-slate-100 bg-white p-3.5 text-center shadow-sm ring-1 ring-slate-100/80 transition hover:-translate-y-0.5 hover:border-emerald-200 hover:shadow-md sm:p-4">
                <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-700 ring-1 ring-emerald-100 sm:h-14 sm:w-14">
                    <svg class="h-6 w-6 sm:h-7 sm:w-7" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v3m0 12v3M4.5 9.75h15M6 9.75V18a1.5 1.5 0 001.5 1.5h9A1.5 1.5 0 0018 18V9.75M9 6.75h6"/></svg>
                </span>
                <span class="mt-2.5 text-[11px] font-semibold leading-snug text-slate-800 sm:text-xs">{{ __('dashboard.customer_cat_prayer') }}</span>
            </a>

            <a href="{{ route('layanan-pendukung.index', ['category' => 'other']) }}" class="group flex flex-col items-center rounded-2xl border border-slate-100 bg-white p-3.5 text-center shadow-sm ring-1 ring-slate-100/80 transition hover:-translate-y-0.5 hover:border-amber-200 hover:shadow-md sm:p-4">
                <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-amber-50 text-amber-700 ring-1 ring-amber-100 sm:h-14 sm:w-14">
                    <svg class="h-6 w-6 sm:h-7 sm:w-7" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z"/><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0z"/></svg>
                </span>
                <span class="mt-2.5 text-[11px] font-semibold leading-snug text-slate-800 sm:text-xs">{{ __('dashboard.customer_cat_photo') }}</span>
            </a>

            <a href="{{ route('layanan-pendukung.index', ['category' => 'ziarah']) }}" class="group col-span-1 flex flex-col items-center rounded-2xl border border-slate-100 bg-white p-3.5 text-center shadow-sm ring-1 ring-slate-100/80 transition hover:-translate-y-0.5 hover:border-emerald-200 hover:shadow-md sm:col-auto sm:p-4">
                <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-700 ring-1 ring-emerald-100 sm:h-14 sm:w-14">
                    <svg class="h-6 w-6 sm:h-7 sm:w-7" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 12V10.332A48.36 48.36 0 0012 9.75c-2.551 0-5.056.2-7.5.582V21"/></svg>
                </span>
                <span class="mt-2.5 text-[11px] font-semibold leading-snug text-slate-800 sm:text-xs">{{ __('dashboard.customer_cat_raudho') }}</span>
            </a>
        </div>
    </section>

    <section class="mb-6">
        <div class="flex gap-2 overflow-x-auto pb-1 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden sm:grid sm:grid-cols-4 sm:gap-3 sm:overflow-visible">
            <a href="#customer-recommend" class="inline-flex min-w-[7.5rem] shrink-0 items-center gap-2 rounded-xl border border-slate-100 bg-white px-3 py-2.5 text-xs font-semibold text-slate-800 shadow-sm ring-1 ring-slate-100/80 transition hover:border-amber-200 sm:min-w-0">
                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-amber-50 text-amber-600"><svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg></span>
                {{ __('dashboard.customer_quick_popular') }}
            </a>
            <a href="#customer-promo" class="inline-flex min-w-[7.5rem] shrink-0 items-center gap-2 rounded-xl border border-slate-100 bg-white px-3 py-2.5 text-xs font-semibold text-slate-800 shadow-sm ring-1 ring-slate-100/80 transition hover:border-emerald-200 sm:min-w-0">
                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-50 text-emerald-700"><svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3z"/><path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6z"/></svg></span>
                {{ __('dashboard.customer_quick_promo') }}
            </a>
            <a href="{{ route('articles.index') }}" class="inline-flex min-w-[7.5rem] shrink-0 items-center gap-2 rounded-xl border border-slate-100 bg-white px-3 py-2.5 text-xs font-semibold text-slate-800 shadow-sm ring-1 ring-slate-100/80 transition hover:border-sky-200 sm:min-w-0">
                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-sky-50 text-sky-700"><svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg></span>
                {{ __('dashboard.customer_quick_articles') }}
            </a>
            <a href="{{ $homeHelpHref }}" class="inline-flex min-w-[7.5rem] shrink-0 items-center gap-2 rounded-xl border border-slate-100 bg-white px-3 py-2.5 text-xs font-semibold text-slate-800 shadow-sm ring-1 ring-slate-100/80 transition hover:border-violet-200 sm:min-w-0">
                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-violet-50 text-violet-700"><svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19.114 5.636a9 9 0 010 12.728M16.463 8.288a5.25 5.25 0 010 7.424M6.75 8.25l4.72-4.72a.75.75 0 011.28.53v15.88a.75.75 0 01-1.28.53l-4.72-4.72H4.51c-.88 0-1.704-.507-1.938-1.354A9.01 9.01 0 012.25 12c0-.83.112-1.633.322-2.396C2.806 8.756 3.63 8.25 4.51 8.25H6.75z"/></svg></span>
                {{ __('dashboard.customer_quick_help') }}
            </a>
        </div>
    </section>

    <section id="customer-promo" class="mb-8 scroll-mt-24">
        @if ($activeCampaigns->isNotEmpty())
            <x-campaign-carousel :campaigns="$activeCampaigns" />
        @else
            <div class="relative overflow-hidden rounded-3xl bg-baytgo p-5 text-white shadow-lg sm:p-6">
                <div class="pointer-events-none absolute inset-y-0 right-0 w-1/2 opacity-30" aria-hidden="true">
                    <img src="{{ $welcomeHeroBg }}" alt="" class="h-full w-full object-cover" loading="lazy" />
                    <div class="absolute inset-0 bg-gradient-to-l from-transparent to-baytgo"></div>
                </div>
                <div class="relative max-w-md">
                    <p class="text-base font-bold leading-snug sm:text-lg">{{ __('dashboard.customer_promo_title') }}</p>
                    <a href="{{ route('layanan.index') }}" class="mt-4 inline-flex items-center gap-1.5 rounded-xl bg-gold px-4 py-2.5 text-sm font-bold text-baytgo-950 shadow transition hover:bg-gold-muted">
                        {{ __('dashboard.customer_promo_cta') }}
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5 15.75 12l-7.5 7.5"/></svg>
                    </a>
                </div>
            </div>
        @endif
    </section>

    <section id="customer-recommend" class="mb-10 scroll-mt-24" aria-labelledby="customer-rec-heading">
        <div class="mb-5 flex flex-wrap items-end justify-between gap-3">
            <h2 id="customer-rec-heading" class="text-lg font-bold text-baytgo sm:text-xl">{{ __('dashboard.customer_popular_title') }}</h2>
            <a href="{{ route('layanan.index') }}" class="inline-flex items-center gap-1 text-sm font-semibold text-baytgo hover:text-baytgo-800">
                {{ __('dashboard.customer_popular_see_all') }}
                <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg>
            </a>
        </div>

        @if ($featuredMuthowifs->isEmpty())
            <p class="rounded-2xl border border-dashed border-slate-200 bg-white py-14 text-center text-sm text-slate-600">{{ __('welcome.popular_empty') }}</p>
        @else
            <div class="relative" x-data="{ scroll(dx) { const el = this.$refs.trackHome; if (el) el.scrollBy({ left: dx, behavior: 'smooth' }); } }">
                <button type="button" @click="scroll(-320)" class="absolute -left-1 top-[38%] z-10 hidden h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full border border-slate-200 bg-white text-baytgo shadow-lg transition hover:bg-slate-50 md:flex" aria-label="{{ __('welcome.carousel_prev') }}">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/></svg>
                </button>
                <button type="button" @click="scroll(320)" class="absolute -right-1 top-[38%] z-10 hidden h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full border border-slate-200 bg-white text-baytgo shadow-lg transition hover:bg-slate-50 md:flex" aria-label="{{ __('welcome.carousel_next') }}">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg>
                </button>
                <div class="-mx-1 flex gap-4 overflow-x-auto scroll-pl-4 px-1 pb-2 snap-x snap-mandatory [scrollbar-width:none] [&::-webkit-scrollbar]:hidden md:px-10" x-ref="trackHome" style="-webkit-overflow-scrolling: touch;">
                    @foreach ($featuredMuthowifs as $profile)
                        @php
                            $minPrice = (int) round((float) ($profile->services->min('daily_price') ?? 0));
                            $formatted = $minPrice > 0 ? 'Rp '.number_format($minPrice, 0, ',', '.') : '—';
                            $rating = $profile->booking_reviews_avg_rating ?? $profile->average_rating;
                            $ratingStr = $rating !== null ? number_format((float) $rating, 1) : '—';
                            $reviewCount = (int) ($profile->booking_reviews_count ?? 0);
                            $loc = method_exists($profile, 'workLocationLabel') ? $profile->workLocationLabel() : null;
                        @endphp
                        <article class="w-[11.5rem] shrink-0 snap-start overflow-hidden rounded-2xl border border-slate-100 bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md sm:w-[13rem]">
                            <a href="{{ route('layanan.show', $profile) }}" class="block h-full focus:outline-none focus-visible:ring-2 focus-visible:ring-baytgo focus-visible:ring-offset-2">
                                <div class="relative aspect-[4/5] overflow-hidden bg-slate-100">
                                    <img src="{{ $profile->photoUrl() }}" alt="" class="h-full w-full object-cover object-top" loading="lazy" decoding="async" />
                                    <span class="absolute left-2 top-2 inline-flex items-center gap-1 rounded-full bg-white/95 px-2 py-0.5 text-[10px] font-bold text-emerald-700 shadow-sm">
                                        <svg class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" /></svg>
                                        {{ __('dashboard.customer_verified_badge') }}
                                    </span>
                                </div>
                                <div class="p-3.5">
                                    <h3 class="line-clamp-1 font-bold text-slate-900">{{ $profile->user->name ?? '—' }}</h3>
                                    <div class="mt-1 flex flex-wrap items-center gap-x-1.5 gap-y-0.5 text-xs text-slate-600">
                                        <span class="inline-flex items-center gap-0.5 font-semibold text-slate-800">
                                            <svg class="h-3.5 w-3.5 text-amber-500" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                            {{ $ratingStr }}
                                        </span>
                                        <span class="text-slate-400">({{ $reviewCount }})</span>
                                        @if ($loc)
                                            <span class="text-slate-400">·</span>
                                            <span class="line-clamp-1">{{ $loc }}</span>
                                        @endif
                                    </div>
                                    <p class="mt-2 text-xs font-semibold text-baytgo sm:text-sm">{{ __('welcome.popular_from', ['amount' => $formatted]) }}</p>
                                </div>
                            </a>
                        </article>
                    @endforeach
                </div>
            </div>
        @endif
    </section>

    <section id="customer-articles" class="scroll-mt-24" aria-labelledby="customer-articles-heading">
        <div class="mb-5 flex flex-wrap items-end justify-between gap-3">
            <h2 id="customer-articles-heading" class="text-lg font-bold text-baytgo sm:text-xl">{{ __('dashboard.customer_articles_title') }}</h2>
            <a href="{{ route('articles.index') }}" class="inline-flex items-center gap-1 text-sm font-semibold text-baytgo hover:text-baytgo-800">
                {{ __('dashboard.customer_articles_see_all') }}
                <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg>
            </a>
        </div>

        @if ($latestArticles->isEmpty())
            @if (is_array($homeGuideCards) && $homeGuideCards !== [])
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    @foreach ($homeGuideCards as $card)
                        <a href="{{ route('welcome') }}#{{ $card['fragment'] ?? '' }}" class="group overflow-hidden rounded-2xl border border-slate-100 bg-white shadow-sm ring-1 ring-slate-100/80 transition hover:shadow-md">
                            <div class="p-5">
                                <span class="inline-flex rounded-lg bg-gold-light/35 px-2 py-1 text-[10px] font-bold uppercase tracking-wider text-baytgo ring-1 ring-gold/25">{{ $card['read'] ?? '' }}</span>
                                <p class="mt-3 font-bold leading-snug text-baytgo group-hover:text-baytgo-800">{{ $card['title'] ?? '' }}</p>
                                <p class="mt-2 text-xs leading-relaxed text-slate-600">{{ $card['desc'] ?? '' }}</p>
                            </div>
                        </a>
                    @endforeach
                </div>
            @else
                <p class="rounded-2xl border border-dashed border-slate-200 bg-white py-10 text-center text-sm text-slate-600">{{ __('welcome.popular_empty') }}</p>
            @endif
        @else
            <div class="-mx-1 flex gap-4 overflow-x-auto px-1 pb-2 snap-x snap-mandatory [scrollbar-width:none] [&::-webkit-scrollbar]:hidden sm:grid sm:grid-cols-3 sm:overflow-visible">
                @foreach ($latestArticles->take(3) as $article)
                    @php
                        $body = $article->localized('body');
                        $thumbnail = null;
                        if (preg_match('/<img[^>]+src=["\']([^"\']+)["\']/', $body, $m)) {
                            $thumbnail = $m[1];
                        }
                    @endphp
                    <article class="w-[15rem] shrink-0 snap-start overflow-hidden rounded-2xl border border-slate-100 bg-white shadow-sm transition hover:shadow-md sm:w-auto">
                        <a href="{{ route('articles.show', ['slug' => $article->slug]) }}" class="block">
                            @if ($thumbnail)
                                <div class="aspect-[16/10] overflow-hidden bg-slate-100">
                                    <img src="{{ $thumbnail }}" alt="" class="h-full w-full object-cover" loading="lazy" />
                                </div>
                            @endif
                            <div class="p-4">
                                <p class="line-clamp-2 text-sm font-bold text-slate-900">{{ $article->localized('title') }}</p>
                                <p class="mt-2 line-clamp-2 text-xs text-slate-600">{{ $article->localized('excerpt') }}</p>
                            </div>
                        </a>
                    </article>
                @endforeach
            </div>
        @endif
    </section>
</div>
