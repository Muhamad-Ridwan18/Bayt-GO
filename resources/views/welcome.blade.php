<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
@php
    $contactRaw = (string) (config('app.contact_whatsapp') ?: config('app.contact_phone'));
    $contactDigits = preg_replace('/\D+/', '', $contactRaw ?? '') ?? '';
    $contactLink = $contactDigits !== '' ? 'https://wa.me/'.$contactDigits : null;
    $featuredMuthowifs = $featuredMuthowifs ?? collect();
@endphp
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        @php
            $homeSchema = [
                '@context' => 'https://schema.org',
                '@type' => 'WebSite',
                'name' => config('app.name', 'Bayt-GO'),
                'url' => url('/'),
                'description' => 'Platform penghubung Muthowif profesional terverifikasi & jasa tour guide ibadah Umroh dan Haji.',
                'potentialAction' => [
                    '@type' => 'SearchAction',
                    'target' => url('/layanan') . '?q={search_term_string}',
                    'query-input' => 'required name=search_term_string'
                ]
            ];
            $homeDesc = "Temukan Muthowif terbaik dan jasa tour guide ibadah Umroh & Haji terpercaya di Bayt-GO. Bandingkan rating, ulasan, harga, dan pesan langsung asisten ibadah terverifikasi Anda secara mudah.";
        @endphp
        <x-seo-meta 
            title="Jasa Tour Guide Ibadah Umroh & Haji | Muthowif Terpercaya" 
            :description="$homeDesc" 
            :schema="$homeSchema" 
        />
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&family=plus-jakarta-sans:400,500,600,700&display=swap" rel="stylesheet">
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-welcome antialiased text-slate-800 bg-white min-h-screen selection:bg-gold-light selection:text-baytgo-950">
        <div class="min-h-screen flex flex-col">
            <x-marketing-public-header active="welcome" />

            <main class="flex-1">
                {{-- ============ HERO ============ --}}
                <section class="relative overflow-hidden bg-welcomeCanvas">
                    {{-- Decorative dotted grid (top-right) --}}
                    <svg class="pointer-events-none absolute right-2 top-24 z-0 hidden h-32 w-40 text-baytgo/15 lg:block" aria-hidden="true">
                        <defs>
                            <pattern id="heroDots" width="18" height="18" patternUnits="userSpaceOnUse">
                                <circle cx="2" cy="2" r="2" fill="currentColor" />
                            </pattern>
                        </defs>
                        <rect width="100%" height="100%" fill="url(#heroDots)" />
                    </svg>
                    <div class="pointer-events-none absolute -right-24 top-1/3 z-0 h-72 w-72 rounded-full bg-emerald-100/40 blur-3xl" aria-hidden="true"></div>

                    <x-page-container class="relative z-10 grid items-center gap-8 pt-10 pb-2 lg:grid-cols-2 lg:gap-10 lg:pt-16 lg:pb-6">
                        {{-- Left: copy --}}
                        <div class="max-w-xl">
                            <p class="inline-flex items-center gap-2 rounded-full border border-emerald-200/70 bg-emerald-50 px-4 py-2 text-[10px] font-bold uppercase tracking-[0.14em] text-emerald-900 sm:text-[11px]">
                                <svg class="h-3.5 w-3.5 text-gold" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 0 0 .95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 0 0-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 0 0-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 0 0-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 0 0 .951-.69l1.07-3.292Z"/></svg>
                                {{ __('welcome.hero_kicker') }}
                            </p>

                            <h1 class="mt-6 text-[2rem] font-bold leading-[1.12] tracking-tight text-baytgo sm:text-4xl lg:text-5xl">
                                {{ __('welcome.hero_title_lead') }}
                                <span class="text-gold-muted">{{ __('welcome.hero_title_accent') }}</span>
                            </h1>

                            <p class="mt-5 max-w-md text-base leading-relaxed text-slate-600 sm:text-lg">
                                {{ __('welcome.hero_sub') }}
                            </p>

                            <div class="mt-7 flex flex-wrap gap-2.5">
                                @foreach ([
                                    ['label' => __('welcome.badge_1'), 'icon' => 'M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.726 0-1.452-.219-2.004-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.004 0l.851.659M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
                                    ['label' => __('welcome.badge_2'), 'icon' => 'M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5a2.25 2.25 0 002.25-2.25m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5a2.25 2.25 0 012.25 2.25v7.5'],
                                    ['label' => __('welcome.badge_3'), 'icon' => 'M3.75 13.5 10.5 6.75l4.125 4.125m7.5 0-7.5 7.5'],
                                ] as $badge)
                                    <span class="inline-flex items-center gap-2 rounded-full bg-white px-3.5 py-2 text-xs font-semibold text-baytgo shadow-sm ring-1 ring-slate-200/80 sm:text-sm">
                                        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-emerald-50 text-emerald-600">
                                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.9" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $badge['icon'] }}" /></svg>
                                        </span>
                                        {{ $badge['label'] }}
                                    </span>
                                @endforeach
                            </div>
                        </div>

                        {{-- Right: mosque + kaaba illustration --}}
                        <div class="relative mx-auto w-full max-w-sm lg:max-w-none">
                            <x-hero-illustration class="h-auto w-full" />
                        </div>
                    </x-page-container>

                    {{-- Search card --}}
                    <x-page-container class="relative z-20 mt-6 sm:mt-8">
                        <div class="rounded-3xl border border-slate-100 bg-white shadow-[0_24px_64px_-16px_rgba(15,42,37,0.16),0_10px_24px_-14px_rgba(0,0,0,0.08)]">
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

                    {{-- Stats --}}
                    <x-page-container class="relative z-10 mt-6 sm:mt-8">
                        <div class="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
                            @php
                                $statIcons = [
                                    'M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z',
                                    'M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5a2.25 2.25 0 002.25-2.25m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5a2.25 2.25 0 012.25 2.25v7.5M9 12.75l1.5 1.5 3-3',
                                    'M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.562.562 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.562.562 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z',
                                    'M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z',
                                ];
                            @endphp
                            @foreach (__('welcome.stat_cards') as $i => $stat)
                                <div class="rounded-2xl border border-slate-100 bg-white p-4 shadow-sm sm:p-5">
                                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 sm:h-10 sm:w-10">
                                        <svg class="h-4 w-4 sm:h-5 sm:w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $statIcons[$i] ?? $statIcons[0] }}" /></svg>
                                    </span>
                                    <p class="mt-3 text-xl font-bold tabular-nums text-baytgo sm:text-2xl">{{ $stat['value'] }}</p>
                                    <p class="mt-0.5 text-xs font-semibold text-slate-800 sm:text-sm">{{ $stat['label'] }}</p>
                                    <p class="mt-1 text-[11px] leading-snug text-slate-500 sm:text-xs">{{ $stat['sub'] }}</p>
                                </div>
                            @endforeach
                        </div>
                    </x-page-container>

                    <x-page-container class="relative z-10 mt-6 pb-12 sm:mt-8 sm:pb-14">
                        <x-campaign-carousel :campaigns="$activeCampaigns ?? collect()" />
                    </x-page-container>
                </section>

                {{-- ============ POPULAR MUTHOWIF ============ --}}
                <section id="muthowif-populer" class="bg-white py-14 sm:py-20">
                    <x-page-container>
                        <div class="mb-7 flex flex-wrap items-end justify-between gap-4 sm:mb-8">
                            <div>
                                <h2 class="text-2xl font-bold text-baytgo sm:text-3xl">{{ __('welcome.popular_title') }}</h2>
                                <p class="mt-2 max-w-md text-sm text-slate-600">Dipilih jamaah berdasarkan rating, pengalaman, dan pelayanan terbaik.</p>
                            </div>
                            <a href="{{ route('layanan.index') }}" class="inline-flex items-center gap-1 text-sm font-semibold text-gold-muted transition hover:text-baytgo">
                                {{ __('welcome.popular_see_all') }}
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5 15.75 12l-7.5 7.5"/></svg>
                            </a>
                        </div>

                        @if ($featuredMuthowifs->isEmpty())
                            <p class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 py-12 text-center text-slate-600">{{ __('welcome.popular_empty') }}</p>
                        @else
                            <div class="relative" x-data="{ scroll(dx) { const el = this.$refs.track; if (el) el.scrollBy({ left: dx, behavior: 'smooth' }); } }">
                                <button type="button" @click="scroll(-320)" class="absolute -left-3 top-[38%] z-10 hidden h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full border border-slate-200 bg-white text-baytgo shadow-lg transition hover:bg-slate-50 md:flex" aria-label="{{ __('welcome.carousel_prev') }}">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/></svg>
                                </button>
                                <button type="button" @click="scroll(320)" class="absolute -right-3 top-[38%] z-10 hidden h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full border border-slate-200 bg-white text-baytgo shadow-lg transition hover:bg-slate-50 md:flex" aria-label="{{ __('welcome.carousel_next') }}">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg>
                                </button>

                                <div x-ref="track" class="-mx-4 flex snap-x snap-mandatory gap-4 overflow-x-auto scroll-pl-4 px-4 pb-4 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden sm:gap-5" style="-webkit-overflow-scrolling: touch;">
                                    @foreach ($featuredMuthowifs as $profile)
                                        @php
                                            $minPrice = (int) round((float) ($profile->services->min('daily_price') ?? 0));
                                            $formatted = $minPrice > 0 ? 'Rp '.number_format($minPrice, 0, ',', '.') : '—';
                                            $rating = $profile->average_rating;
                                            $ratingStr = $rating !== null ? number_format((float) $rating, 1) : '—';
                                            $reviewCount = (int) $profile->booking_reviews_count;
                                            $languages = array_slice($profile->languagesForDisplay(), 0, 3);
                                            $langsLine = $languages !== [] ? implode(', ', $languages) : null;
                                            $loc = $profile->workLocationLabel();
                                            $metaLine = collect([$loc, $langsLine])->filter()->implode(' · ');
                                        @endphp
                                        <article class="group w-[15.5rem] flex-shrink-0 snap-start overflow-hidden rounded-2xl border border-slate-100 bg-white shadow-sm transition-all hover:-translate-y-0.5 hover:border-gold-light/50 hover:shadow-md sm:w-[16.5rem]">
                                            <a href="{{ route('layanan.show', $profile) }}" class="block focus:outline-none focus-visible:ring-2 focus-visible:ring-gold focus-visible:ring-offset-2">
                                                <div class="relative aspect-[4/5] overflow-hidden bg-slate-100">
                                                    <img src="{{ $profile->photoUrl() }}" alt="" class="h-full w-full object-cover object-top transition duration-500 group-hover:scale-105" loading="lazy" decoding="async" />
                                                    <span class="absolute left-3 top-3 inline-flex items-center gap-1 rounded-full bg-white/95 px-2.5 py-1 text-[10px] font-bold text-emerald-700 shadow-sm backdrop-blur">
                                                        <svg class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" /></svg>
                                                        {{ __('welcome.verified_badge') }}
                                                    </span>
                                                    <span class="absolute right-3 top-3 flex h-8 w-8 items-center justify-center rounded-full bg-white/95 text-slate-400 shadow-sm backdrop-blur">
                                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z" /></svg>
                                                    </span>
                                                </div>
                                                <div class="p-4">
                                                    <h3 class="flex items-center gap-1.5 font-bold text-slate-900">
                                                        <span class="line-clamp-1 min-w-0">{{ $profile->user->name ?? 'Muthowif' }}</span>
                                                        <span class="inline-flex shrink-0 text-sky-500" title="{{ __('welcome.verified_badge') }}">
                                                            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>
                                                        </span>
                                                    </h3>
                                                    @if ($metaLine !== '')
                                                        <p class="mt-1 line-clamp-1 text-xs text-slate-500">{{ $metaLine }}</p>
                                                    @endif
                                                    <div class="mt-2 flex items-center gap-1 text-sm">
                                                        <svg class="h-4 w-4 text-amber-500" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 0 0 .95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 0 0-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 0 0-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 0 0-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 0 0 .951-.69l1.07-3.292Z"/></svg>
                                                        <span class="font-semibold tabular-nums text-slate-800">{{ $ratingStr }}</span>
                                                        <span class="text-xs tabular-nums text-slate-500">({{ __('welcome.popular_reviews', ['count' => $reviewCount]) }})</span>
                                                    </div>
                                                    <p class="mt-3 border-t border-slate-100 pt-3 text-sm font-semibold text-baytgo">{{ __('welcome.popular_from', ['amount' => $formatted]) }}</p>
                                                </div>
                                            </a>
                                        </article>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </x-page-container>
                </section>

                {{-- ============ HOW IT WORKS ============ --}}
                <section id="cara-kerja" class="border-t border-slate-100 bg-welcomeCanvas py-14 sm:py-20">
                    <x-page-container>
                        <div class="mx-auto mb-10 max-w-2xl text-center sm:mb-14">
                            <h2 class="text-2xl font-bold text-baytgo sm:text-3xl">{{ __('welcome.work_title') }}</h2>
                            <p class="mt-3 text-slate-600">{{ __('welcome.work_sub') }}</p>
                            <span class="mx-auto mt-5 block h-1 w-12 rounded-full bg-gold" aria-hidden="true"></span>
                        </div>

                        <div class="grid gap-5 sm:gap-6 md:grid-cols-3">
                            @php
                                $workIcons = [
                                    'M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z',
                                    'M9 12.75 11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
                                    'M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z',
                                ];
                            @endphp
                            @foreach (__('welcome.work_steps') as $i => $step)
                                <article class="relative rounded-3xl border border-slate-100 bg-white p-6 text-center shadow-sm transition hover:-translate-y-0.5 hover:shadow-md sm:p-8">
                                    <span class="absolute right-5 top-5 text-4xl font-bold text-baytgo/5 sm:text-5xl">{{ $i + 1 }}</span>
                                    <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600 ring-1 ring-emerald-100">
                                        <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $workIcons[$i] ?? $workIcons[0] }}" /></svg>
                                    </span>
                                    <h3 class="mt-5 text-lg font-bold text-slate-900">{{ $step['title'] }}</h3>
                                    <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ $step['desc'] }}</p>
                                </article>
                            @endforeach
                        </div>
                    </x-page-container>
                </section>

                {{-- ============ GALLERY ============ --}}
                @php
                    $galleryImages = $galleryImages ?? collect();
                    $galleryChunked = $galleryImages->count() >= 2
                        ? $galleryImages->chunk((int) ceil($galleryImages->count() / 2))
                        : collect();
                    $row1 = $galleryChunked->get(0, collect());
                    $row2 = $galleryChunked->get(1, collect());
                @endphp

                @if ($galleryImages->isNotEmpty())
                <section class="relative overflow-hidden bg-baytgo-950 py-12 sm:py-16" aria-label="Galeri Perjalanan Muthowif">
                    <div class="pointer-events-none absolute inset-0 opacity-30 bg-[radial-gradient(ellipse_at_center,_#C5A059_0%,_transparent_65%)]" aria-hidden="true"></div>

                    <div class="relative z-10 mb-8 px-4 text-center">
                        <p class="mb-2 text-xs font-bold uppercase tracking-[0.22em] text-gold">Galeri Perjalanan</p>
                        <h2 class="text-2xl font-bold text-white sm:text-3xl">Momen Bersama Muthowif Kami</h2>
                        <span class="mx-auto mt-4 block h-0.5 w-10 rounded-full bg-gold/60" aria-hidden="true"></span>
                    </div>

                    <div class="marquee-wrap mb-3">
                        <div class="marquee-track">
                            @foreach ($row1->concat($row1) as $img)
                                <div class="mx-1.5 h-32 w-48 shrink-0 overflow-hidden rounded-xl shadow-lg ring-1 ring-white/10 sm:h-36 sm:w-56 lg:h-40 lg:w-64">
                                    <img src="{{ $img->publicUrl() }}" alt="" class="h-full w-full object-cover transition duration-500 hover:scale-105" loading="lazy" decoding="async">
                                </div>
                            @endforeach
                        </div>
                    </div>

                    @if ($row2->isNotEmpty())
                    <div class="marquee-wrap">
                        <div class="marquee-track-reverse">
                            @foreach ($row2->concat($row2) as $img)
                                <div class="mx-1.5 h-32 w-48 shrink-0 overflow-hidden rounded-xl shadow-lg ring-1 ring-white/10 sm:h-36 sm:w-56 lg:h-40 lg:w-64">
                                    <img src="{{ $img->publicUrl() }}" alt="" class="h-full w-full object-cover transition duration-500 hover:scale-105" loading="lazy" decoding="async">
                                </div>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    <div class="relative z-10 mt-8 text-center">
                        <a href="{{ route('layanan.index') }}" class="inline-flex items-center gap-2 rounded-full border border-white/25 bg-white/10 px-6 py-2.5 text-sm font-semibold text-white backdrop-blur-sm transition hover:bg-white/20">
                            Lihat Semua Muthowif
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg>
                        </a>
                    </div>
                </section>
                @endif

                {{-- ============ TESTIMONIALS ============ --}}
                <section class="bg-white py-14 sm:py-20">
                    <x-page-container>
                        <div class="mb-10 flex flex-wrap items-end justify-between gap-4 sm:mb-12">
                            <h2 class="text-2xl font-bold text-baytgo sm:text-3xl">{{ __('welcome.jamaah_title') }}</h2>
                            <a href="{{ route('layanan.index') }}" class="inline-flex items-center gap-1 text-sm font-semibold text-gold-muted transition hover:text-baytgo">
                                {{ __('welcome.jamaah_see_all') }}
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5 15.75 12l-7.5 7.5"/></svg>
                            </a>
                        </div>
                        <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                            @foreach (__('welcome.testimonials') as $t)
                                <blockquote class="flex flex-col rounded-3xl border border-slate-100 bg-welcomeCanvas p-6 shadow-sm">
                                    <svg class="h-8 w-8 text-gold/70" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M4.583 17.321C3.553 16.227 3 15 3 13.011c0-3.5 2.457-6.637 6.03-8.188l.893 1.378c-3.335 1.804-3.987 4.145-4.247 5.621.537-.278 1.24-.375 1.929-.311 1.804.167 3.226 1.648 3.226 3.489a3.5 3.5 0 01-3.5 3.5c-1.073 0-2.099-.49-2.748-1.179zm10 0C13.553 16.227 13 15 13 13.011c0-3.5 2.457-6.637 6.03-8.188l.893 1.378c-3.335 1.804-3.987 4.145-4.247 5.621.537-.278 1.24-.375 1.929-.311 1.804.167 3.226 1.648 3.226 3.489a3.5 3.5 0 01-3.5 3.5c-1.073 0-2.099-.49-2.748-1.179z" /></svg>
                                    <p class="mt-4 flex-1 text-sm leading-relaxed text-slate-700">{{ $t['quote'] }}</p>
                                    <footer class="mt-6 flex items-center gap-3 border-t border-slate-200/70 pt-5">
                                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-baytgo/10 text-sm font-bold text-baytgo">{{ \Illuminate\Support\Str::substr($t['name'], 0, 1) }}</span>
                                        <div class="min-w-0">
                                            <p class="font-semibold text-slate-900">{{ $t['name'] }}</p>
                                            <p class="text-xs text-slate-500">{{ $t['role'] }}</p>
                                        </div>
                                        <div class="ml-auto flex gap-0.5 text-gold" aria-label="5 stars">
                                            @for ($s = 0; $s < 5; $s++)
                                                <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 0 0 .95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 0 0-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 0 0-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 0 0-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 0 0 .951-.69l1.07-3.292Z"/></svg>
                                            @endfor
                                        </div>
                                    </footer>
                                </blockquote>
                            @endforeach
                        </div>
                    </x-page-container>
                </section>

                {{-- ============ LATEST ARTICLES ============ --}}
                @if ($latestArticles->isNotEmpty())
                    <section id="artikel-terbaru" class="border-t border-slate-100 bg-welcomeCanvas py-14 sm:py-20">
                        <x-page-container>
                            <div class="mb-9 flex flex-wrap items-end justify-between gap-4 sm:mb-10">
                                <div>
                                    <h2 class="text-2xl font-bold text-baytgo sm:text-3xl">{{ __('nav.articles') }}</h2>
                                    <span class="mt-4 block h-1 w-14 rounded-full bg-gold" aria-hidden="true"></span>
                                </div>
                                <a href="{{ route('articles.index') }}" class="inline-flex items-center gap-1 text-sm font-semibold text-gold-muted transition hover:text-baytgo">
                                    {{ __('welcome.popular_see_all') }}
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5 15.75 12l-7.5 7.5"/></svg>
                                </a>
                            </div>

                            <div class="grid gap-6 sm:grid-cols-2 sm:gap-8 lg:grid-cols-3">
                                @foreach ($latestArticles as $article)
                                    @php
                                        $body = $article->localized('body');
                                        $thumbnail = null;
                                        if (preg_match('/<img[^>]+src=["\']([^"\']+)["\']/', $body, $m)) {
                                            $thumbnail = $m[1];
                                        }
                                    @endphp
                                    <article class="group flex flex-col overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-sm transition hover:border-baytgo/25 hover:shadow-xl hover:shadow-baytgo/5">
                                        @if ($thumbnail)
                                            <a href="{{ route('articles.show', ['slug' => $article->slug]) }}" class="block aspect-[16/9] overflow-hidden bg-slate-100">
                                                <img src="{{ $thumbnail }}" alt="" class="h-full w-full object-cover transition duration-500 group-hover:scale-105" loading="lazy" />
                                            </a>
                                        @endif
                                        <div class="flex flex-1 flex-col p-6">
                                            <div class="mb-4 flex items-center justify-between">
                                                <span class="rounded-full bg-baytgo/8 px-3 py-1 text-[10px] font-bold uppercase tracking-wider text-baytgo">{{ $article->localized('category') }}</span>
                                                <time class="text-[11px] font-medium text-slate-500" datetime="{{ $article->published_at?->toIso8601String() }}">
                                                    {{ $article->published_at?->translatedFormat('d M Y') }}
                                                </time>
                                            </div>
                                            <h3 class="text-xl font-bold leading-snug text-slate-900 transition-colors group-hover:text-baytgo">
                                                <a href="{{ route('articles.show', ['slug' => $article->slug]) }}" class="focus:outline-none">{{ $article->localized('title') }}</a>
                                            </h3>
                                            <p class="mt-4 line-clamp-3 flex-1 text-sm leading-relaxed text-slate-600">{{ $article->localized('excerpt') }}</p>
                                            <div class="mt-6 flex items-center gap-2 border-t border-slate-100 pt-5">
                                                <span class="text-[11px] font-bold uppercase tracking-tight text-baytgo/80">{{ __('articles.reading_minutes', ['count' => $article->readingMinutes()]) }}</span>
                                                <span class="text-slate-300">•</span>
                                                <span class="text-[11px] font-medium uppercase tracking-tight text-slate-500">{{ $article->localized('author') }}</span>
                                            </div>
                                        </div>
                                    </article>
                                @endforeach
                            </div>
                        </x-page-container>
                    </section>
                @endif

                {{-- ============ PRICING ============ --}}
                <section id="harga" class="border-t border-slate-100 bg-white py-14 sm:py-20">
                    <x-page-container class="text-center">
                        <h2 class="text-2xl font-bold text-baytgo sm:text-3xl">{{ __('welcome.pricing_title') }}</h2>
                        <p class="mx-auto mt-3 max-w-xl text-slate-600">{{ __('welcome.pricing_sub') }}</p>
                        <a href="{{ route('layanan.index') }}" class="mt-8 inline-flex items-center rounded-xl bg-baytgo px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-baytgo/20 transition hover:bg-baytgo-800">{{ __('welcome.pricing_cta') }}</a>
                    </x-page-container>
                </section>

                {{-- ============ ABOUT ============ --}}
                <section id="tentang" class="border-t border-slate-100 bg-welcomeCanvas py-14 sm:py-16">
                    <x-page-container class="text-center">
                        <h2 class="text-2xl font-bold text-baytgo">{{ __('welcome.about_title') }}</h2>
                        <p class="mx-auto mt-4 max-w-2xl leading-relaxed text-slate-600">{{ __('welcome.about_sub') }}</p>
                    </x-page-container>
                </section>

                {{-- ============ FAQ ============ --}}
                <section id="faq" class="border-t border-slate-100 bg-white py-14 sm:py-16">
                    <x-page-container>
                        <h2 class="mb-10 text-center text-2xl font-bold text-baytgo">{{ __('welcome.faq_title') }}</h2>
                        <dl class="mx-auto max-w-3xl space-y-3">
                            @foreach (__('welcome.faq_items') as $item)
                                <div class="rounded-2xl border border-slate-200/90 bg-welcomeCanvas p-5 shadow-sm">
                                    <dt class="font-semibold text-slate-900">{{ $item['q'] }}</dt>
                                    <dd class="mt-2 text-sm leading-relaxed text-slate-600">{{ $item['a'] }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    </x-page-container>
                </section>

                {{-- ============ FINAL CTA ============ --}}
                <x-page-container tag="section" class="py-14 sm:py-16">
                    <div class="relative overflow-hidden rounded-[2rem] bg-gradient-to-br from-baytgo via-baytgo-800 to-baytgo-950 px-6 py-10 text-white shadow-xl sm:px-10 sm:py-12">
                        <div class="pointer-events-none absolute -right-16 -top-16 h-64 w-64 rounded-full bg-gold/15 blur-3xl" aria-hidden="true"></div>
                        <div class="pointer-events-none absolute -bottom-20 -left-10 h-64 w-64 rounded-full bg-emerald-400/10 blur-3xl" aria-hidden="true"></div>
                        <div class="relative flex flex-col items-center gap-8 text-center lg:flex-row lg:items-center lg:justify-between lg:text-left">
                            <div class="max-w-xl">
                                <h2 class="text-2xl font-bold leading-snug sm:text-3xl">{{ __('welcome.final_cta_title') }}</h2>
                                <p class="mt-3 text-sm text-white/85 sm:text-base">{{ __('welcome.final_cta_sub') }}</p>
                            </div>
                            <div class="flex shrink-0 flex-col items-center gap-4">
                                <a href="{{ route('layanan.index') }}" class="inline-flex items-center gap-2 rounded-xl bg-gold px-7 py-3.5 text-sm font-bold text-baytgo-950 shadow-lg transition hover:bg-gold-muted">
                                    {{ __('welcome.final_cta_button') }}
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5 15.75 12l-7.5 7.5"/></svg>
                                </a>
                                <div class="flex items-center gap-3">
                                    <div class="flex -space-x-2.5">
                                        @foreach (['A','S','M','R'] as $av)
                                            <span class="flex h-8 w-8 items-center justify-center rounded-full border-2 border-baytgo bg-gold-light text-[11px] font-bold text-baytgo-950">{{ $av }}</span>
                                        @endforeach
                                    </div>
                                    <span class="text-xs text-white/80">{{ __('welcome.final_cta_join') }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </x-page-container>
            </main>

            <footer class="mt-auto border-t border-slate-200 bg-white">
                <x-page-container class="flex flex-col gap-4 py-8 text-sm text-slate-500 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex flex-col items-center gap-2 sm:flex-row sm:items-start sm:gap-4">
                        <span>&copy; {{ date('Y') }} {{ config('app.name') }}</span>
                        <a href="{{ route('terms') }}" class="text-xs font-semibold text-baytgo hover:text-baytgo-800">{{ __('terms.footer_link') }}</a>
                    </div>
                    <div class="flex flex-col items-center gap-1 sm:items-end">
                        <span class="text-xs text-slate-400">{{ __('welcome.footer_tagline') }}</span>
                        @if ($contactLink)
                            <a href="{{ $contactLink }}" target="_blank" rel="noopener noreferrer" class="text-xs font-medium text-baytgo hover:text-baytgo-800">
                                {{ __('marketplace.footer_contact', ['contact' => $contactRaw]) }}
                            </a>
                        @endif
                    </div>
                </x-page-container>
            </footer>
        </div>
    </body>
</html>
