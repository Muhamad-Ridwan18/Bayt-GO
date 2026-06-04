{{--
    Mobile: hero+cari → ringkasan → perjalanan+bantuan → rekomendasi → panduan → pintasan.
    Desktop: hero full-bleed, lalu grid 8+4 (kiri direktori, kanan status).
--}}

{{-- 1. Hero + pencarian (full bleed seperti beranda) --}}
<section class="relative left-1/2 mb-2 w-screen max-w-[100vw] -translate-x-1/2 overflow-hidden bg-welcomeCanvas pb-8 sm:pb-10 lg:mb-0">
    <div class="pointer-events-none absolute inset-0 z-0" aria-hidden="true">
        <img src="{{ $welcomeHeroBg }}" alt="" class="h-full w-full min-h-[18rem] object-cover object-[70%_28%] sm:min-h-[20rem] sm:object-[72%_28%] lg:min-h-[22rem]" loading="eager" decoding="async" />
    </div>
    <div class="pointer-events-none absolute inset-0 z-[1] bg-gradient-to-b from-welcomeCanvas via-welcomeCanvas/94 to-welcomeCanvas/50 sm:hidden" aria-hidden="true"></div>
    <div class="pointer-events-none absolute inset-0 z-[1] hidden bg-gradient-to-r from-welcomeCanvas from-[32%] via-welcomeCanvas/95 via-[58%] to-welcomeCanvas/5 sm:block lg:from-[36%] lg:via-[62%] lg:to-transparent" aria-hidden="true"></div>
    <div class="pointer-events-none absolute inset-x-0 bottom-0 z-[1] h-20 bg-gradient-to-t from-welcomeCanvas to-transparent sm:h-28" aria-hidden="true"></div>

    <x-page-container class="relative z-10 pt-8 sm:pt-10 lg:pt-12">
        <div class="max-w-2xl">
            <p class="mb-4 inline-flex items-center gap-2 rounded-full border border-emerald-200/70 bg-emerald-50 px-3.5 py-1.5 text-[10px] font-bold uppercase tracking-[0.12em] text-emerald-900 shadow-sm">
                <span class="flex h-5 w-5 items-center justify-center rounded-full bg-white text-emerald-600" aria-hidden="true">
                    <svg class="h-3 w-3" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" /></svg>
                </span>
                {{ __('dashboard.customer_hero_kicker') }}
            </p>
            <h1 class="text-xl font-bold tracking-tight text-slate-900 sm:text-2xl lg:text-[1.65rem]">
                {{ __('dashboard.customer_hero_intro') }}
                <span class="text-baytgo">{{ $user->name }}</span>
                <span aria-hidden="true">👋</span>
            </h1>
            <p class="mt-3 max-w-xl text-base leading-relaxed text-slate-700 sm:text-lg">{{ __('dashboard.customer_hero_sub') }}</p>
        </div>

        <div class="mt-6 grid grid-cols-2 gap-2.5 sm:mt-8 sm:grid-cols-2 sm:gap-3 lg:grid-cols-4 lg:max-w-4xl">
            @foreach ([
                ['icon' => 'shield', 'bg' => 'bg-emerald-50', 'text' => 'text-emerald-700', 'title' => 'customer_feature_verified_title', 'desc' => 'customer_feature_verified_desc'],
                ['icon' => 'calendar', 'bg' => 'bg-sky-50', 'text' => 'text-sky-700', 'title' => 'customer_feature_schedule_title', 'desc' => 'customer_feature_schedule_desc'],
                ['icon' => 'users', 'bg' => 'bg-violet-50', 'text' => 'text-violet-700', 'title' => 'customer_feature_group_title', 'desc' => 'customer_feature_group_desc'],
                ['icon' => 'phone', 'bg' => 'bg-amber-50', 'text' => 'text-amber-800', 'title' => 'customer_feature_support_title', 'desc' => 'customer_feature_support_desc'],
            ] as $feat)
                <div class="rounded-2xl border border-white/80 bg-white/95 p-3.5 shadow-[0_8px_24px_-8px_rgba(15,42,37,0.12)] ring-1 ring-slate-100/90 backdrop-blur-sm sm:p-4">
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl {{ $feat['bg'] }} {{ $feat['text'] }}" aria-hidden="true">
                        @if ($feat['icon'] === 'shield')
                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M12.516 2.17a.75.75 0 01.466.747l-.286 2.051a.75.75 0 01-.548.582 11.319 11.319 0 00-4.702 2.271.75.75 0 01-.826-.033l-1.64-1.117a.75.75 0 00-.987.052l-1.378 1.378a.75.75 0 00.052.987l1.117 1.64a.75.75 0 01.033.826 11.32 11.32 0 00-2.27 4.702.75.75 0 01-.582.548l-2.051.286a.75.75 0 00-.747.466V12a.75.75 0 00.747-.466l-2.051-.286a.75.75 0 01-.548-.582 11.32 11.32 0 00-2.27-4.702.75.75 0 01.033-.826l1.117-1.64a.75.75 0 00.052-.987L18.72 9.53a.75.75 0 00-.987-.052l-1.64 1.117a.75.75 0 01-.826.033 11.317 11.317 0 00-4.702-2.27.75.75 0 01-.582-.548l-.286-2.051A.75.75 0 0012.516 2.17z" clip-rule="evenodd" /></svg>
                        @elseif ($feat['icon'] === 'calendar')
                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5a2.25 2.25 0 002.25-2.25m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5a2.25 2.25 0 012.25 2.25v7.5" /></svg>
                        @elseif ($feat['icon'] === 'users')
                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a48.667 48.667 0 00-7.5 0m12 0v1.5a3 3 0 01-3 3h-12a3 3 0 01-3-3v-1.5m12 0V9M6 18.72V9m0 0a48.667 48.667 0 017.5 0M6 9V6.75A2.25 2.25 0 018.25 4.5h7.5A2.25 2.25 0 0118 6.75V9" /></svg>
                        @else
                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.72 1.072c-.442.663-1.32.902-2.027.55a12.284 12.284 0 01-7.4-7.4c-.352-.707-.113-1.585.55-2.027l1.072-.72c.363-.271.527-.732.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 6.75z" /></svg>
                        @endif
                    </span>
                    <p class="mt-2.5 text-xs font-bold leading-snug text-slate-900 sm:text-[13px]">{{ __('dashboard.'.$feat['title']) }}</p>
                    <p class="mt-0.5 text-[10px] leading-snug text-slate-500 sm:text-[11px]">{{ __('dashboard.'.$feat['desc']) }}</p>
                </div>
            @endforeach
        </div>
    </x-page-container>

    <x-page-container class="relative z-20 mt-8 sm:mt-10" id="customer-search">
        <div class="rounded-3xl border border-gray-100/90 bg-white shadow-[0_24px_64px_-12px_rgba(15,42,37,0.14),0_8px_20px_-10px_rgba(0,0,0,0.06)] ring-1 ring-slate-100/80">
            @include('layanan.partials.date-search-form', [
                'startDate' => '',
                'endDate' => '',
                'searchQuery' => '',
                'showHeaderBanner' => false,
                'welcomeAccent' => true,
                'welcomeInlineHeader' => true,
                'welcomeFlush' => true,
            ])
        </div>
    </x-page-container>
</section>

<div class="mt-8 flex flex-col gap-8 lg:mt-10 lg:grid lg:grid-cols-12 lg:items-start lg:gap-10">
    {{-- Kolom utama --}}
    <div class="contents lg:col-span-8 lg:flex lg:flex-col lg:gap-10">
        {{-- Rekomendasi --}}
        <section class="order-4 lg:order-none" aria-labelledby="customer-rec-heading" id="customer-recommend">
            <div class="mb-5 flex flex-wrap items-end justify-between gap-3">
                <div>
                    <div class="flex items-center gap-2">
                        <svg class="h-5 w-5 text-amber-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M10.868 2.884c-.321-.772-1.415-.772-1.736 0l-1.83 4.401-4.753.381c-.833.067-1.171 1.107-.536 1.651l3.62 3.102-1.106 4.637c-.194.813.691 1.456 1.405 1.02L10 15.591l4.069 2.485c.713.436 1.598-.207 1.404-1.02l-1.106-4.637 3.62-3.102c.635-.544.297-1.584-.536-1.65l-4.752-.382-1.83-4.401z" clip-rule="evenodd" /></svg>
                        <h2 id="customer-rec-heading" class="text-lg font-bold text-baytgo sm:text-xl">{{ __('dashboard.customer_recommend_title') }}</h2>
                    </div>
                    <p class="mt-1 text-sm text-slate-600">{{ __('dashboard.customer_recommend_sub') }}</p>
                </div>
                <a href="{{ route('layanan.index') }}" class="inline-flex items-center gap-1 text-sm font-semibold text-baytgo hover:text-baytgo-800">
                    {{ __('welcome.popular_see_all') }}
                    <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg>
                </a>
            </div>

            @if ($featuredMuthowifs->isEmpty())
                <p class="rounded-2xl border border-dashed border-slate-200 bg-white py-14 text-center text-sm text-slate-600">{{ __('welcome.popular_empty') }}</p>
            @else
                <div class="relative" x-data="{ scroll(dx) { const el = this.$refs.trackC; if (el) el.scrollBy({ left: dx, behavior: 'smooth' }); } }">
                    <button type="button" @click="scroll(-320)" class="absolute -left-1 top-[38%] z-10 hidden h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full bg-baytgo text-white shadow-lg shadow-baytgo/25 transition hover:bg-baytgo-800 md:flex" aria-label="{{ __('welcome.carousel_prev') }}">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/></svg>
                    </button>
                    <button type="button" @click="scroll(320)" class="absolute -right-1 top-[38%] z-10 hidden h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full bg-baytgo text-white shadow-lg shadow-baytgo/25 transition hover:bg-baytgo-800 md:flex" aria-label="{{ __('welcome.carousel_next') }}">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg>
                    </button>
                    <div class="-mx-1 flex gap-4 overflow-x-auto scroll-pl-4 px-1 pb-2 snap-x snap-mandatory md:px-10" x-ref="trackC" style="-webkit-overflow-scrolling: touch;">
                        @foreach ($featuredMuthowifs as $profile)
                            @php
                                $minPrice = (int) round((float) ($profile->services->min('daily_price') ?? 0));
                                $formatted = $minPrice > 0 ? 'Rp '.number_format($minPrice, 0, ',', '.') : '—';
                                $rating = $profile->booking_reviews_avg_rating;
                                $ratingStr = $rating !== null ? number_format((float) $rating, 1, ',', '') : '—';
                                $reviewCount = (int) $profile->booking_reviews_count;
                                $tripDone = (int) $profile->completed_trips_count;
                                $languages = array_slice($profile->languagesForDisplay(), 0, 5);
                                $langsLine = $languages !== [] ? implode(', ', $languages) : null;
                            @endphp
                            <article class="w-[10.5rem] shrink-0 snap-start overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm transition hover:border-gold-muted/40 hover:shadow-md sm:w-[11.75rem] md:w-[12.25rem]">
                                <a href="{{ route('layanan.show', $profile) }}" class="block h-full rounded-2xl focus:outline-none focus-visible:ring-2 focus-visible:ring-baytgo focus-visible:ring-offset-2">
                                    <div class="relative aspect-[4/5] overflow-hidden bg-slate-100">
                                        <img src="{{ route('layanan.photo', $profile) }}" alt="" class="h-full w-full object-cover object-top" loading="lazy" decoding="async" />
                                        <span class="absolute right-2 top-2 inline-flex items-center gap-0.5 rounded-full bg-white/95 px-2 py-0.5 text-[10px] font-bold shadow-sm ring-1 ring-amber-200/60">
                                            <svg class="h-3 w-3 text-amber-500" viewBox="0 0 20 20" fill="currentColor"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                            {{ $ratingStr }}
                                        </span>
                                    </div>
                                    <div class="border-t border-slate-100/90 p-3.5">
                                        <h3 class="line-clamp-1 font-bold text-slate-900">{{ $profile->user->name ?? '—' }}</h3>
                                        @if ($langsLine)
                                            <p class="mt-1 line-clamp-2 text-[11px] text-slate-500">{{ $langsLine }}</p>
                                        @endif
                                        @if ($tripDone > 0)
                                            <p class="mt-1 text-[10px] text-slate-500">{{ trans_choice('dashboard.customer_trips_done', $tripDone, ['count' => $tripDone]) }}</p>
                                        @endif
                                        <p class="mt-2 text-xs font-semibold text-baytgo sm:text-sm">{{ __('welcome.popular_from', ['amount' => $formatted]) }}</p>
                                        <span class="mt-3 flex w-full items-center justify-center rounded-xl border border-baytgo/20 bg-welcomeCanvas/60 py-2 text-[11px] font-semibold text-baytgo transition hover:border-baytgo hover:bg-baytgo hover:text-white">
                                            {{ __('dashboard.customer_view_profile') }}
                                        </span>
                                    </div>
                                </a>
                            </article>
                        @endforeach
                    </div>
                    <div class="mt-3 flex justify-center gap-2 md:hidden">
                        <button type="button" class="rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-baytgo shadow-sm" @click="scroll(-280)">{{ __('welcome.carousel_prev') }}</button>
                        <button type="button" class="rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-baytgo shadow-sm" @click="scroll(280)">{{ __('welcome.carousel_next') }}</button>
                    </div>
                </div>
            @endif
        </section>

        @if ($customerGuideCards !== [])
            <section class="order-5 lg:order-none" aria-labelledby="customer-guides-heading">
                <h2 id="customer-guides-heading" class="text-lg font-bold text-baytgo sm:text-xl">{{ __('dashboard.customer_content_title') }}</h2>
                <p class="mt-1 text-sm text-slate-600">{{ __('dashboard.customer_content_sub') }}</p>
                <div class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-3">
                    @foreach ($customerGuideCards as $card)
                        <a href="{{ route('welcome') }}#{{ $card['fragment'] ?? '' }}" class="group rounded-2xl border border-gray-100 bg-white p-5 shadow-sm ring-1 ring-slate-100/80 transition hover:border-gold-muted/35 hover:shadow-md">
                            <span class="inline-flex rounded-lg bg-gold-light/35 px-2 py-1 text-[10px] font-bold uppercase tracking-wider text-baytgo ring-1 ring-gold/25">{{ $card['read'] ?? '' }}</span>
                            <p class="mt-3 font-bold leading-snug text-baytgo group-hover:text-baytgo-800">{{ $card['title'] ?? '' }}</p>
                            <p class="mt-2 text-xs leading-relaxed text-slate-600">{{ $card['desc'] ?? '' }}</p>
                            <span class="mt-4 inline-flex items-center gap-1 text-xs font-semibold text-baytgo">
                                {{ __('dashboard.customer_content_read') }}
                                <svg class="h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M3 10a.75.75 0 01.75-.75h10.638L10.23 5.29a.75.75 0 111.04-1.08l5.5 5.25a.75.75 0 010 1.08l-5.5 5.25a.75.75 0 11-1.04-1.08l4.158-3.96H3.75A.75.75 0 013 10z" clip-rule="evenodd" /></svg>
                            </span>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif
    </div>

    {{-- Sidebar --}}
    <aside class="contents lg:col-span-4 lg:flex lg:flex-col lg:gap-6 lg:sticky lg:top-24 lg:self-start">
        {{-- Ringkasan aktivitas --}}
        <div class="relative order-2 overflow-hidden rounded-3xl bg-baytgo p-6 text-white shadow-[0_20px_40px_-14px_rgba(26,61,52,0.35)] ring-1 ring-white/10 sm:p-7 lg:order-none">
            <div class="pointer-events-none absolute -right-8 -top-8 h-32 w-32 rounded-full bg-gold-muted/20 blur-2xl" aria-hidden="true"></div>
            <div class="relative flex items-start justify-between gap-3">
                <div>
                    <p class="text-sm font-bold text-white">{{ __('dashboard.customer_status_title') }}</p>
                    <p class="mt-1 text-xs text-white/75">{{ __('dashboard.customer_status_sub') }}</p>
                </div>
                <span class="shrink-0 rounded-full bg-gold-light/20 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide text-gold-light ring-1 ring-gold-muted/30">{{ __('dashboard.customer_hero_kicker') }}</span>
            </div>
            <div class="relative mt-6 grid grid-cols-2 gap-3">
                <a href="{{ route('bookings.index') }}" class="rounded-2xl bg-white/10 p-4 ring-1 ring-white/15 transition hover:bg-white/15">
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-white/15" aria-hidden="true">
                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5a2.25 2.25 0 002.25-2.25m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5a2.25 2.25 0 012.25 2.25v7.5" /></svg>
                    </span>
                    <p class="mt-3 text-2xl font-bold tabular-nums">{{ $activeBookingCount }}</p>
                    <p class="mt-0.5 text-[11px] font-medium leading-tight text-white/85">{{ __('dashboard.customer_stat_active') }}</p>
                </a>
                @if (Route::has('support.index'))
                    <a href="{{ route('support.index') }}" class="rounded-2xl bg-white/10 p-4 ring-1 ring-white/15 transition hover:bg-white/15">
                        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-white/15" aria-hidden="true">
                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 6v.75m3 3H3.75M3 18.75h16.5M3 12h16.5m-1.5-7.5H6.75m0 0v12m0-12v1.5M6.75 6h10.5v1.5M6.75 15h10.5v1.5" /></svg>
                        </span>
                        <p class="mt-3 text-2xl font-bold tabular-nums">{{ $supportOpenCount }}</p>
                        <p class="mt-0.5 text-[11px] font-medium leading-tight text-white/85">{{ __('dashboard.customer_stat_support') }}</p>
                    </a>
                @else
                    <div class="rounded-2xl bg-white/10 p-4 ring-1 ring-white/15">
                        <p class="mt-10 text-2xl font-bold tabular-nums">{{ $supportOpenCount }}</p>
                        <p class="text-[11px] font-medium text-white/85">{{ __('dashboard.customer_stat_support') }}</p>
                    </div>
                @endif
                <a href="{{ route('bookings.index') }}" class="rounded-2xl bg-white/10 p-4 ring-1 ring-white/15 transition hover:bg-white/15">
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-white/15" aria-hidden="true">
                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </span>
                    <p class="mt-3 text-2xl font-bold tabular-nums">{{ $upcomingTripCount }}</p>
                    <p class="mt-0.5 text-[11px] font-medium leading-tight text-white/85">{{ __('dashboard.customer_stat_upcoming') }}</p>
                </a>
                <div class="rounded-2xl bg-white/10 p-4 ring-1 ring-white/15">
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-white/15" aria-hidden="true">
                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.5c0 1.036.84 1.875 1.875 1.875H16.5a1.875 1.875 0 001.875-1.875V6.75A1.875 1.875 0 0016.5 4.875h-9A1.875 1.875 0 005.625 6.75v9.75z" /></svg>
                    </span>
                    <p class="mt-3 text-2xl font-bold tabular-nums">{{ $reviewsGivenCount }}</p>
                    <p class="mt-0.5 text-[11px] font-medium leading-tight text-white/85">{{ __('dashboard.customer_stat_reviews') }}</p>
                </div>
            </div>
            <a href="{{ route('profile.edit') }}" class="relative mt-6 flex items-center justify-center gap-2 rounded-xl bg-white/12 py-3 text-sm font-semibold text-white ring-1 ring-gold-muted/30 transition hover:bg-white/20">
                {{ __('dashboard.customer_status_account_cta') }}
                <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M3 10a.75.75 0 01.75-.75h10.638L10.23 5.29a.75.75 0 111.04-1.08l5.5 5.25a.75.75 0 010 1.08l-5.5 5.25a.75.75 0 11-1.04-1.08l4.158-3.96H3.75A.75.75 0 013 10z" clip-rule="evenodd" /></svg>
            </a>
        </div>

        <div class="order-3 space-y-6 lg:order-none">
            {{-- Perjalanan mendatang --}}
            <section class="rounded-3xl border border-gray-100 bg-white p-5 shadow-sm ring-1 ring-slate-100/90 sm:p-6" aria-labelledby="customer-up-heading">
                <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
                    <h2 id="customer-up-heading" class="text-base font-bold text-baytgo">{{ __('dashboard.customer_upcoming_title') }}</h2>
                    <a href="{{ route('bookings.index') }}" class="text-xs font-semibold text-baytgo hover:text-baytgo-800">{{ __('dashboard.customer_upcoming_see_all') }}</a>
                </div>

                @if ($nextBooking === null)
                    <div class="rounded-2xl border border-dashed border-slate-200 bg-slate-50/80 py-9 text-center">
                        <p class="text-sm font-medium text-slate-700">{{ __('dashboard.customer_upcoming_empty') }}</p>
                        <a href="{{ route('layanan.index') }}#customer-search" class="mt-4 inline-flex items-center justify-center rounded-xl bg-baytgo px-4 py-2.5 text-xs font-semibold text-white shadow-md shadow-baytgo/20 transition hover:bg-baytgo-800">
                            {{ __('dashboard.customer_upcoming_cta') }}
                        </a>
                    </div>
                @else
                    @php
                        $nb = $nextBooking;
                        $mpName = $nb->muthowifProfile?->user?->name ?? '—';
                        $startStr = $nb->starts_on?->locale(app()->getLocale())->translatedFormat('d M Y') ?? '';
                        $endStr = $nb->ends_on?->locale(app()->getLocale())->translatedFormat('d M Y') ?? '';
                        $dur = '';
                        if ($nb->starts_on && $nb->ends_on) {
                            $dur = max(1, (int) ($nb->starts_on->diffInDays($nb->ends_on) + 1));
                        }
                        $paid = $nb->payment_status === PaymentStatus::Paid;
                    @endphp
                    <div class="overflow-hidden rounded-2xl border border-gray-100 ring-1 ring-slate-100/90">
                        <div class="grid grid-cols-[5.5rem_minmax(0,1fr)] gap-3 p-4 sm:grid-cols-[6.25rem_minmax(0,1fr)] sm:gap-4">
                            <div class="relative h-[5.5rem] overflow-hidden rounded-xl bg-slate-100 ring-1 ring-slate-200/80 sm:h-[6.5rem]">
                                @if ($nb->muthowifProfile)
                                    <img src="{{ route('layanan.photo', $nb->muthowifProfile) }}" alt="" class="h-full w-full object-cover object-top" loading="lazy" />
                                @else
                                    <img src="{{ $welcomeHeroBg }}" alt="" class="h-full w-full object-cover" loading="lazy" />
                                @endif
                            </div>
                            <div class="min-w-0">
                                <p class="font-bold leading-snug text-slate-900">{{ $nb->service_type->label() }}</p>
                                <p class="mt-1 text-xs text-slate-600">{{ __('dashboard.customer_with_guide', ['name' => $mpName]) }}</p>
                                <div class="mt-2.5 space-y-1 text-xs text-slate-600">
                                    <p><span class="font-semibold text-slate-800">{{ __('dashboard.customer_trip_dates') }}</span> {{ $startStr }} — {{ $endStr }}</p>
                                    @if ($dur !== '')
                                        <p><span class="font-semibold text-slate-800">{{ __('dashboard.customer_trip_duration') }}</span> {{ trans_choice('dashboard.customer_trip_days', $dur, ['count' => $dur]) }}</p>
                                    @endif
                                    <p><span class="font-semibold text-slate-800">{{ __('dashboard.customer_trip_group') }}</span> {{ trans_choice('dashboard.customer_trip_pilgrims', (int) $nb->pilgrim_count, ['count' => (int) $nb->pilgrim_count]) }}</p>
                                </div>
                                <div class="mt-2 flex flex-wrap gap-2">
                                    <span class="inline-flex rounded-full px-2 py-0.5 text-[10px] font-bold uppercase {{ $paid ? 'bg-gold-light/45 text-baytgo ring-1 ring-gold-muted/40' : 'bg-welcomeCanvas text-baytgo ring-1 ring-slate-200' }}">
                                        {{ $paid ? __('dashboard.customer_payment_paid') : $nb->payment_status->label() }}
                                    </span>
                                    <span class="text-[10px] font-medium uppercase text-slate-500">{{ $nb->status->label() }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="border-t border-gray-100 bg-welcomeCanvas/50 p-4">
                            <a href="{{ route('bookings.show', $nb) }}" class="flex w-full items-center justify-center rounded-xl border border-baytgo/25 bg-white py-2.5 text-sm font-semibold text-baytgo transition hover:border-baytgo hover:bg-baytgo hover:text-white">
                                {{ __('dashboard.customer_booking_detail_cta') }}
                            </a>
                        </div>
                    </div>
                @endif
            </section>

            {{-- Bantuan --}}
            <div class="rounded-3xl border border-gray-100 bg-white p-5 shadow-sm ring-1 ring-slate-100/90 sm:p-6">
                <h3 class="font-bold text-baytgo">{{ __('dashboard.customer_help_ticket_title') }}</h3>
                <p class="mt-2 text-xs leading-relaxed text-slate-600">{{ __('dashboard.customer_help_ticket_sub') }}</p>
                @if ($supportHref)
                    <a href="{{ $supportHref }}" class="mt-4 inline-flex w-full items-center justify-center rounded-xl bg-baytgo py-3 text-sm font-semibold text-white shadow-md shadow-baytgo/20 transition hover:bg-baytgo-800">{{ __('dashboard.customer_help_ticket_cta') }}</a>
                @endif
            </div>
            <div class="rounded-3xl border border-gray-100 bg-white p-5 shadow-sm ring-1 ring-slate-100/90 sm:p-6">
                <h3 class="font-bold text-baytgo">{{ __('dashboard.customer_help_contact_title') }}</h3>
                <div class="mt-4 flex flex-wrap gap-3">
                    @if ($contactWaLink)
                        <a href="{{ $contactWaLink }}" target="_blank" rel="noopener noreferrer" class="inline-flex h-11 w-11 items-center justify-center rounded-2xl border border-emerald-100 bg-emerald-50 text-baytgo transition hover:bg-emerald-100" aria-label="WhatsApp">
                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.435 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                        </a>
                    @endif
                    @if ($contactPhoneDisplay)
                        @php $phoneHref = 'tel:'.preg_replace('/\s+/', '', (string) preg_replace('/[^\d+]/', '', (string) $contactPhoneDisplay)); @endphp
                        <a href="{{ $phoneHref }}" class="inline-flex h-11 w-11 items-center justify-center rounded-2xl border border-slate-200 bg-welcomeCanvas/80 text-baytgo transition hover:bg-welcomeCanvas" aria-label="{{ __('dashboard.customer_contact_phone') }}">
                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.163-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z" /></svg>
                        </a>
                    @endif
                    @if ($contactEmail !== '')
                        <a href="mailto:{{ $contactEmail }}" class="inline-flex h-11 w-11 items-center justify-center rounded-2xl border border-slate-200 bg-white text-baytgo transition hover:bg-welcomeCanvas" aria-label="Email">
                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" /></svg>
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </aside>
</div>

{{-- Pintasan akses cepat --}}
<div class="mt-8 grid grid-cols-2 gap-3 sm:grid-cols-4 lg:mt-10">
    <a href="{{ route('layanan.index') }}" class="rounded-2xl border border-gray-100 bg-white p-4 text-center shadow-sm ring-1 ring-slate-100/80 transition hover:border-gold-muted/35 hover:shadow-md">
        <span class="mx-auto flex h-11 w-11 items-center justify-center rounded-full bg-emerald-50 text-baytgo ring-1 ring-emerald-100/80">
            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
        </span>
        <p class="mt-3 text-xs font-bold text-baytgo">{{ __('dashboard.shortcut_find_title') }}</p>
    </a>
    <a href="{{ route('bookings.index') }}" class="rounded-2xl border border-gray-100 bg-white p-4 text-center shadow-sm ring-1 ring-slate-100/80 transition hover:border-gold-muted/35 hover:shadow-md">
        <span class="mx-auto flex h-11 w-11 items-center justify-center rounded-full bg-gold-light/35 text-baytgo ring-1 ring-gold/25">
            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        </span>
        <p class="mt-3 text-xs font-bold text-baytgo">{{ __('dashboard.shortcut_bookings_title') }}</p>
    </a>
    @if (Route::has('support.index'))
        <a href="{{ route('support.index') }}" class="rounded-2xl border border-gray-100 bg-white p-4 text-center shadow-sm ring-1 ring-slate-100/80 transition hover:border-gold-muted/35 hover:shadow-md">
            <span class="mx-auto flex h-11 w-11 items-center justify-center rounded-full bg-sky-50 text-baytgo ring-1 ring-sky-100">
                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 6v.75m3 3H3.75M3 18.75h16.5M3 12h16.5m-1.5-7.5H6.75m0 0v12m0-12v1.5M6.75 6h10.5v1.5M6.75 15h10.5v1.5"/></svg>
            </span>
            <p class="mt-3 text-xs font-bold text-baytgo">{{ __('dashboard.shortcut_support_title') }}</p>
        </a>
    @endif
    <a href="{{ route('profile.edit') }}" class="rounded-2xl border border-gray-100 bg-white p-4 text-center shadow-sm ring-1 ring-slate-100/80 transition hover:border-gold-muted/35 hover:shadow-md">
        <span class="mx-auto flex h-11 w-11 items-center justify-center rounded-full bg-welcomeCanvas text-baytgo ring-1 ring-gold-muted/30">
            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/></svg>
        </span>
        <p class="mt-3 text-xs font-bold text-baytgo">{{ __('dashboard.shortcut_profile_title') }}</p>
    </a>
</div>
