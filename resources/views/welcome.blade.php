<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
@php
    use App\Support\WelcomeLanding;

    $contactRaw = (string) (config('app.contact_whatsapp') ?: config('app.contact_phone'));
    $contactDigits = preg_replace('/\D+/', '', $contactRaw ?? '') ?? '';
    $contactLink = $contactDigits !== '' ? 'https://wa.me/'.$contactDigits : null;
    $featuredMuthowifs = $featuredMuthowifs ?? collect();

    $heroImage = WelcomeLanding::resolvedHeroImageUrl();
    $heroImageClasses = WelcomeLanding::heroImageTailwindClasses();
@endphp
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name', 'BaytGo') }} — {{ __('welcome.page_title') }}</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&family=plus-jakarta-sans:400,500,600,700&display=swap" rel="stylesheet">
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-welcome antialiased text-slate-800 bg-white min-h-screen selection:bg-gold-light selection:text-baytgo-950">
        <div class="min-h-screen flex flex-col">
            {{-- Top navigation: desktop center links at lg+; drawer + hamburger below lg --}}
            <header class="sticky top-0 z-[100] border-b border-slate-100 bg-white shadow-sm" x-data="{ open: false }" @keydown.window.escape="open = false" @resize.window="if (window.innerWidth >= 1024) open = false">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative flex min-h-[4.25rem] items-center justify-between gap-3 lg:gap-6">
                    <a href="{{ url('/') }}" class="relative z-10 flex min-w-0 shrink-0 items-center gap-2.5 group">
                        <x-site-logo variant="welcome" class="rounded-xl ring-1 ring-slate-200/70 shrink-0" />
                        <span class="truncate text-lg font-bold tracking-tight text-baytgo">Bayt<span class="text-gold-muted">Go</span></span>
                    </a>

                    <nav class="hidden lg:flex absolute left-1/2 top-1/2 max-w-none -translate-x-1/2 -translate-y-1/2 items-center gap-0.5 text-sm font-semibold" aria-label="{{ __('welcome.nav_primary_aria') }}">
                        <a href="{{ route('welcome') }}" class="{{ request()->routeIs('welcome') ? 'relative px-3 py-2 text-baytgo after:absolute after:inset-x-3 after:-bottom-0.5 after:h-0.5 after:rounded-full after:bg-gold' : 'rounded-lg px-3 py-2 text-slate-600 transition hover:text-baytgo' }}">{{ __('welcome.nav_home') }}</a>
                        <a href="#cara-kerja" class="rounded-lg px-3 py-2 text-slate-600 transition hover:text-baytgo">{{ __('welcome.nav_how') }}</a>
                        <a href="{{ route('layanan.index') }}" class="rounded-lg px-3 py-2 text-slate-600 transition hover:text-baytgo">{{ __('welcome.nav_muthowif') }}</a>
                        <a href="#harga" class="rounded-lg px-3 py-2 text-slate-600 transition hover:text-baytgo">{{ __('welcome.nav_pricing') }}</a>
                        <a href="#tentang" class="rounded-lg px-3 py-2 text-slate-600 transition hover:text-baytgo">{{ __('welcome.nav_about') }}</a>
                        <a href="#faq" class="rounded-lg px-3 py-2 text-slate-600 transition hover:text-baytgo">{{ __('welcome.nav_faq') }}</a>
                    </nav>

                    <div class="relative z-10 flex shrink-0 items-center gap-2">
                        <div class="hidden items-center gap-2 sm:gap-3 lg:flex">
                            <x-language-switcher variant="segment" />
                            @if ($contactLink)
                                <a href="{{ $contactLink }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center rounded-xl bg-baytgo px-4 py-2.5 text-sm font-semibold text-white shadow-md shadow-baytgo/20 transition hover:bg-baytgo-800">
                                    {{ __('nav.contact_us') }}
                                </a>
                            @endif
                            @auth
                                <a href="{{ route('dashboard') }}" class="inline-flex rounded-xl border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-700 transition hover:border-baytgo hover:text-baytgo">{{ __('nav.home') }}</a>
                            @else
                                @if (Route::has('login'))
                                    <a href="{{ route('login') }}" class="inline-flex rounded-xl px-3 py-2 text-sm font-semibold text-slate-700 transition hover:text-baytgo">{{ __('layanan.guest_header_login') }}</a>
                                @endif
                            @endauth
                        </div>

                        <button
                            type="button"
                            class="inline-flex shrink-0 items-center justify-center rounded-xl border border-slate-200/90 bg-white p-2 text-slate-600 shadow-sm transition hover:border-baytgo/30 hover:bg-slate-50 hover:text-baytgo focus:outline-none focus:ring-2 focus:ring-baytgo/20 lg:hidden"
                            @click="open = ! open"
                            :aria-expanded="open"
                            aria-controls="welcome-mobile-nav"
                        >
                            <span class="sr-only">{{ __('nav.open_menu') }}</span>
                            <svg class="h-6 w-6 shrink-0" stroke="currentColor" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                                <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                <div
                    id="welcome-mobile-nav"
                    :class="{'block': open, 'hidden': ! open}"
                    class="hidden border-t border-slate-100 bg-white lg:hidden"
                >
                    <nav class="max-w-7xl mx-auto space-y-0.5 px-4 py-4 sm:px-6" aria-label="{{ __('welcome.nav_mobile_aria') }}">
                        <a href="{{ route('welcome') }}" @click="open = false" class="block rounded-lg px-3 py-2.5 text-sm font-semibold {{ request()->routeIs('welcome') ? 'bg-baytgo/8 text-baytgo' : 'text-slate-700 hover:bg-slate-50' }}">{{ __('welcome.nav_home') }}</a>
                        <a href="#cara-kerja" @click="open = false" class="block rounded-lg px-3 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">{{ __('welcome.nav_how') }}</a>
                        <a href="{{ route('layanan.index') }}" @click="open = false" class="block rounded-lg px-3 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">{{ __('welcome.nav_muthowif') }}</a>
                        <a href="#harga" @click="open = false" class="block rounded-lg px-3 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">{{ __('welcome.nav_pricing') }}</a>
                        <a href="#tentang" @click="open = false" class="block rounded-lg px-3 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">{{ __('welcome.nav_about') }}</a>
                        <a href="#faq" @click="open = false" class="block rounded-lg px-3 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">{{ __('welcome.nav_faq') }}</a>
                    </nav>
                    <div class="border-t border-slate-100 px-4 py-4 sm:px-6">
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">{{ __('nav.language') }}</p>
                        <div class="mt-3 flex justify-start">
                            <x-language-switcher variant="segment" />
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-2 border-t border-slate-100 px-4 py-4 sm:px-6">
                        @if ($contactLink)
                            <a href="{{ $contactLink }}" target="_blank" rel="noopener noreferrer" class="inline-flex flex-1 min-w-[8rem] items-center justify-center rounded-xl bg-baytgo px-4 py-2.5 text-sm font-semibold text-white shadow-md shadow-baytgo/20 transition hover:bg-baytgo-800">{{ __('nav.contact_us') }}</a>
                        @endif
                        @auth
                            <a href="{{ route('dashboard') }}" class="inline-flex flex-1 min-w-[8rem] items-center justify-center rounded-xl border border-slate-200 px-3 py-2.5 text-sm font-semibold text-slate-700 transition hover:border-baytgo hover:text-baytgo">{{ __('nav.home') }}</a>
                        @else
                            @if (Route::has('login'))
                                <a href="{{ route('login') }}" class="inline-flex flex-1 min-w-[8rem] items-center justify-center rounded-xl border border-slate-200 px-3 py-2.5 text-sm font-semibold text-slate-700 transition hover:text-baytgo">{{ __('layanan.guest_header_login') }}</a>
                            @endif
                        @endauth
                    </div>
                </div>
            </header>

            <main class="flex-1">
                {{-- Hero: full-bleed photo + cream blend (no boxed image) --}}
                <section class="relative min-h-[34rem] overflow-hidden bg-welcomeCanvas pb-10 sm:min-h-[38rem] sm:pb-12 lg:min-h-[40rem] lg:pb-14">
                    {{-- Background photo: full width --}}
                    {{-- Geser foto: hanya via object-position; gradient overlay pakai stop bawaan --}}
                    <div class="pointer-events-none absolute inset-0 z-0 overflow-hidden" aria-hidden="true">
                        <img
                            src="{{ $heroImage }}"
                            alt=""
                            class="{{ $heroImageClasses }}"
                            loading="eager"
                            decoding="async"
                        />
                    </div>
                    {{-- Mobile: strong cream from top so headline reads --}}
                    <div class="pointer-events-none absolute inset-0 z-[1] bg-gradient-to-b from-welcomeCanvas via-welcomeCanvas/90 to-welcomeCanvas/35 sm:hidden" aria-hidden="true"></div>
                    {{-- sm+: horizontal blend — cream left, foto bercampur ke kanan --}}
                    <div class="pointer-events-none absolute inset-0 z-[1] hidden bg-gradient-to-r from-welcomeCanvas from-[22%] via-welcomeCanvas/90 via-[48%] to-welcomeCanvas/5 sm:block lg:from-[20%] lg:via-[42%] lg:to-transparent" aria-hidden="true"></div>
                    {{-- Soft bottom fade toward search card / section end --}}
                    <div class="pointer-events-none absolute inset-x-0 bottom-0 z-[1] h-40 bg-gradient-to-t from-welcomeCanvas to-transparent sm:h-48" aria-hidden="true"></div>

                    <div class="relative z-10 mx-auto max-w-7xl px-4 pt-14 sm:px-6 sm:pt-16 lg:px-8 lg:pt-20">
                        <div class="max-w-xl sm:max-w-lg">
                                <p class="mb-8 inline-flex items-center gap-2.5 rounded-full border border-emerald-200/60 bg-emerald-50 px-5 py-2.5 text-[11px] font-bold uppercase tracking-[0.12em] text-emerald-900">
                                    <span class="flex h-6 w-6 items-center justify-center rounded-full bg-white text-emerald-600 shadow-sm shadow-emerald-900/10">
                                        <svg class="h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                            <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                                        </svg>
                                    </span>
                                    {{ __('welcome.hero_kicker') }}
                                </p>
                                <h1 class="text-[1.75rem] font-bold leading-[1.15] tracking-tight text-baytgo sm:text-4xl lg:text-[2.5rem] xl:text-[2.75rem] lg:leading-[1.12]">
                                    {{ __('welcome.hero_title') }}
                                </h1>
                                <p class="mt-6 max-w-md text-[1.0625rem] leading-relaxed text-slate-700 sm:text-lg">
                                    {{ __('welcome.hero_sub') }}
                                </p>

                                <div class="mt-10 flex flex-wrap gap-3 lg:flex-nowrap lg:items-center lg:justify-start lg:gap-4">
                                    @foreach ([
                                        [
                                            'label' => __('welcome.badge_1'),
                                            'icon' => 'M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.726 0-1.452-.219-2.004-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.004 0l.851.659M21 12a9 9 0 11-18 0 9 9 0 0118 0z'
                                        ],
                                        [
                                            'label' => __('welcome.badge_2'),
                                            'icon' => 'M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5a2.25 2.25 0 002.25-2.25m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5a2.25 2.25 0 012.25 2.25v7.5'
                                        ],
                                        [
                                            'label' => __('welcome.badge_3'),
                                            'icon' => 'M3.75 13.5 10.5 6.75l4.125 4.125m7.5 0-7.5 7.5'
                                        ],
                                    ] as $badge)
                                        <div class="inline-flex shrink-0 items-center gap-2.5 rounded-full bg-gold-light/35 px-4 py-2.5 text-sm font-semibold text-baytgo ring-1 ring-gold/20 backdrop-blur-[2px] sm:backdrop-blur-none">
                                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-white/85 text-baytgo">
                                                <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $badge['icon'] }}" />
                                                </svg>
                                            </span>
                                            <span class="text-slate-800">{{ $badge['label'] }}</span>
                                        </div>
                                    @endforeach
                                </div>
                        </div>
                    </div>

                    <div class="relative z-20 mx-auto mt-12 max-w-7xl w-full px-4 pb-8 sm:mt-14 sm:px-6 lg:mt-16 lg:px-8">

                        <div class="rounded-3xl border border-gray-100/90 bg-white shadow-[0_24px_64px_-12px_rgba(15,42,37,0.12),0_12px_24px_-14px_rgba(0,0,0,0.06)]">
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
                    </div>
                </section>

                {{-- <div class="h-14 bg-white sm:h-16" aria-hidden="true"></div> --}}

                {{-- Why choose --}}
                <section id="kenapa" class="border-t border-slate-100/80 bg-welcomeCanvas py-16 sm:py-20">
                    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                        <div class="text-center">
                            <h2 class="text-2xl font-bold text-baytgo sm:text-3xl">{{ __('welcome.why_title') }}</h2>
                            <span class="mx-auto mt-4 block h-1 w-14 rounded-full bg-gold" aria-hidden="true"></span>
                        </div>
                        <div class="mt-12 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                            @foreach (__('welcome.why_cards') as $i => $card)
                                <article class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm transition hover:border-gold-light/40 hover:shadow-md">
                                    <span class="flex h-12 w-12 items-center justify-center rounded-full bg-emerald-50 text-baytgo ring-1 ring-emerald-100/80">
                                        @if ($i === 0)
                                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12.75 11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 0 1-1.043 3.296 3.745 3.745 0 0 1-3.296 1.043A3.745 3.745 0 0 1 12 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 0 1-3.296-1.043 3.745 3.745 0 0 1-1.043-3.296A3.745 3.745 0 0 1 3 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 0 1 1.043-3.296 3.746 3.746 0 0 1 3.296-1.043A3.746 3.746 0 0 1 12 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 0 1 3.296 1.043 3.746 3.746 0 0 1 1.043 3.296A3.745 3.745 0 0 1 21 12Z"/></svg>
                                        @elseif ($i === 1)
                                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 0 1-.659 1.591l-5.432 5.432a2.25 2.25 0 0 0-.659 1.591v2.927a2.25 2.25 0 0 1-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 0 0-.659-1.591L3.659 7.409A2.25 2.25 0 0 1 3 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0 1 12 3Z"/></svg>
                                        @elseif ($i === 2)
                                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 1 0-4.681-2.72"/></svg>
                                        @else
                                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337 5.972 5.972 0 0 1-.327-.377c0-.068.021-.13.041-.18.02-.05.08-.15.16-.23.16-.16.38-.24.6-.24h.01c.43 0 .84.06 1.225.18.38.12.77.29 1.15.51.38.22.75.49 1.1.8.35.31.68.66.98 1.03.3-.37.63-.72.98-1.03.35-.31.72-.58 1.1-.8.38-.22.77-.39 1.15-.51.38-.12.79-.18 1.225-.18h.01c.22 0 .44.08.6.24.08.08.14.18.16.23.02.05.041.112.041.18a5.98 5.98 0 0 1-.327.377A9.764 9.764 0 0 1 21 12Z"/></svg>
                                        @endif
                                    </span>
                                    <h3 class="mt-4 font-bold text-slate-900">{{ $card['title'] }}</h3>
                                    <p class="mt-2 text-sm text-slate-600 leading-relaxed">{{ $card['desc'] }}</p>
                                </article>
                            @endforeach
                        </div>
                    </div>
                </section>

                {{-- Popular muthowif carousel --}}
                <section id="muthowif-populer" class="py-16 sm:py-20 bg-white">
                    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                        <div class="flex flex-wrap items-end justify-between gap-4 mb-8">
                            <h2 class="text-2xl sm:text-3xl font-bold text-baytgo">{{ __('welcome.popular_title') }}</h2>
                            <a href="{{ route('layanan.index') }}" class="text-sm font-semibold text-gold-muted hover:text-baytgo transition inline-flex items-center gap-1">
                                {{ __('welcome.popular_see_all') }}
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5 15.75 12l-7.5 7.5"/></svg>
                            </a>
                        </div>

                        @if ($featuredMuthowifs->isEmpty())
                            <p class="text-slate-600 text-center py-12 rounded-2xl bg-slate-50 border border-dashed border-slate-200">{{ __('welcome.popular_empty') }}</p>
                        @else
                            <div class="relative" x-data="{ scroll(dx) { const el = this.$refs.track; if (el) el.scrollBy({ left: dx, behavior: 'smooth' }); } }">
                                <button type="button" @click="scroll(-340)" class="-ml-1 absolute left-0 top-1/2 z-10 hidden h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full bg-baytgo text-white shadow-lg shadow-baytgo/30 transition hover:bg-baytgo-800 md:flex" aria-label="{{ __('welcome.carousel_prev') }}">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/></svg>
                                </button>
                                <button type="button" @click="scroll(340)" class="-mr-1 absolute right-0 top-1/2 z-10 hidden h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full bg-baytgo text-white shadow-lg shadow-baytgo/30 transition hover:bg-baytgo-800 md:flex" aria-label="{{ __('welcome.carousel_next') }}">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg>
                                </button>
                                <div x-ref="track" class="-mx-1 flex gap-5 overflow-x-auto scroll-pl-4 snap-x snap-mandatory px-1 pb-4 md:px-12" style="-webkit-overflow-scrolling: touch;">
                                    @foreach ($featuredMuthowifs as $profile)
                                        @php
                                            $minPrice = (int) round((float) ($profile->services->min('daily_price') ?? 0));
                                            $formatted = $minPrice > 0 ? 'Rp '.number_format($minPrice, 0, ',', '.') : '—';
                                            $rating = $profile->booking_reviews_avg_rating;
                                            $ratingStr = $rating !== null ? number_format((float) $rating, 1) : '—';
                                            $reviewCount = (int) $profile->booking_reviews_count;
                                            $languages = array_slice($profile->languagesForDisplay(), 0, 5);
                                            $langsLine = $languages !== [] ? implode(', ', $languages) : null;
                                        @endphp
                                        <article class="min-w-[16.5rem] sm:min-w-[18rem] max-w-[18rem] flex-shrink-0 snap-start overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm transition-all hover:border-gold-light/40 hover:shadow-md">
                                            <a href="{{ route('layanan.show', $profile) }}" class="block rounded-2xl focus:outline-none focus-visible:ring-2 focus-visible:ring-gold focus-visible:ring-offset-2">
                                                <div class="relative aspect-[4/5] overflow-hidden bg-slate-100">
                                                    <img src="{{ route('layanan.photo', $profile) }}" alt="" class="h-full w-full object-cover object-top" loading="lazy" decoding="async" />
                                                </div>
                                                <div class="p-4">
                                                    <h3 class="flex items-center gap-1.5 font-bold text-slate-900">
                                                        <span class="line-clamp-1 min-w-0">{{ $profile->user->name ?? 'Muthowif' }}</span>
                                                        <span class="inline-flex shrink-0 text-sky-600" title="{{ __('welcome.verified_badge') }}">
                                                            <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                                <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                                            </svg>
                                                        </span>
                                                    </h3>
                                                    @if ($langsLine !== null)
                                                        <p class="mt-1 line-clamp-2 text-xs text-slate-500">{{ $langsLine }}</p>
                                                    @endif
                                                    <div class="mt-2 flex items-center gap-1 text-sm">
                                                        <svg class="h-4 w-4 text-amber-500" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 0 0 .95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 0 0-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 0 0-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 0 0-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 0 0 .951-.69l1.07-3.292Z"/></svg>
                                                        <span class="font-semibold tabular-nums text-slate-800">{{ $ratingStr }}</span>
                                                        <span class="text-xs tabular-nums text-slate-500">({{ $reviewCount }})</span>
                                                    </div>
                                                    <p class="mt-3 text-sm font-semibold text-baytgo">{{ __('welcome.popular_from', ['amount' => $formatted]) }}</p>
                                                </div>
                                            </a>
                                        </article>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                </section>

                {{-- Stats --}}
                <section class="bg-white py-10 sm:py-12">
                    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                        <div class="rounded-2xl bg-baytgo px-6 py-10 text-white shadow-lg shadow-slate-900/15 sm:px-10 sm:py-12 lg:px-14">
                            <div class="relative overflow-hidden rounded-xl sm:rounded-2xl">
                                <div class="pointer-events-none absolute inset-0 opacity-[0.08] bg-[radial-gradient(circle_at_20%_0%,#C5A059,transparent_55%)]" aria-hidden="true"></div>
                                <div class="relative grid grid-cols-2 gap-y-10 text-center lg:grid-cols-4 lg:gap-y-0">
                                    @foreach (__('welcome.stats') as $stat)
                                        <div class="px-2">
                                            <p class="text-2xl font-bold tabular-nums text-gold sm:text-3xl">{{ $stat['value'] }}</p>
                                            <p class="mt-1.5 text-sm font-medium text-white/85">{{ $stat['label'] }}</p>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                {{-- Testimonials --}}
                <section class="border-t border-slate-100/80 bg-welcomeCanvas py-16 sm:py-20">
                    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                        <div class="text-center">
                            <h2 class="text-2xl font-bold text-baytgo sm:text-3xl">{{ __('welcome.testimonials_title') }}</h2>
                            <span class="mx-auto mt-4 block h-1 w-14 rounded-full bg-gold" aria-hidden="true"></span>
                        </div>
                        <div class="mt-12 grid grid-cols-1 gap-8 md:grid-cols-3">
                            @foreach (__('welcome.testimonials') as $t)
                                <blockquote class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                                    <div class="text-baytgo" aria-hidden="true">
                                        <svg class="h-8 w-8 opacity-90" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                                            <path d="M4.583 17.321C3.553 16.227 3 15 3 13.011c0-3.5 2.457-6.637 6.03-8.188l.893 1.378c-3.335 1.804-3.987 4.145-4.247 5.621.537-.278 1.24-.375 1.929-.311 1.804.167 3.226 1.648 3.226 3.489a3.5 3.5 0 01-3.5 3.5c-1.073 0-2.099-.49-2.748-1.179zm10 0C13.553 16.227 13 15 13 13.011c0-3.5 2.457-6.637 6.03-8.188l.893 1.378c-3.335 1.804-3.987 4.145-4.247 5.621.537-.278 1.24-.375 1.929-.311 1.804.167 3.226 1.648 3.226 3.489a3.5 3.5 0 01-3.5 3.5c-1.073 0-2.099-.49-2.748-1.179z" />
                                        </svg>
                                    </div>
                                    <p class="mt-4 text-sm leading-relaxed text-slate-700">{{ $t['quote'] }}</p>
                                    <footer class="mt-6 flex items-center gap-3 border-t border-slate-100 pt-5">
                                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-baytgo/10 text-sm font-bold text-baytgo">{{ \Illuminate\Support\Str::substr($t['name'], 0, 1) }}</span>
                                        <div class="min-w-0">
                                            <p class="font-semibold text-slate-900">{{ $t['name'] }}</p>
                                            <p class="text-xs text-slate-500">{{ $t['role'] }}</p>
                                            <div class="mt-1 flex gap-0.5 text-gold" aria-label="5 stars">
                                                @for ($s = 0; $s < 5; $s++)
                                                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 0 0 .95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 0 0-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 0 0-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 0 0-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 0 0 .951-.69l1.07-3.292Z"/></svg>
                                                @endfor
                                            </div>
                                        </div>
                                    </footer>
                                </blockquote>
                            @endforeach
                        </div>
                    </div>
                </section>

                {{-- Pricing / Directory --}}
                <section id="harga" class="py-16 sm:py-20 bg-white border-t border-slate-100">
                    <div class="max-w-3xl mx-auto px-4 text-center">
                        <h2 class="text-2xl sm:text-3xl font-bold text-baytgo">{{ __('welcome.pricing_title') }}</h2>
                        <p class="mt-3 text-slate-600">{{ __('welcome.pricing_sub') }}</p>
                        <a href="{{ route('layanan.index') }}" class="mt-8 inline-flex items-center rounded-xl bg-baytgo px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-baytgo/20 hover:bg-baytgo-800 transition">{{ __('welcome.pricing_cta') }}</a>
                    </div>
                </section>

                {{-- How it works --}}
                <section id="cara-kerja" class="py-16 sm:py-20 bg-slate-50 border-t border-slate-100">
                    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                        <div class="max-w-2xl mx-auto text-center mb-12">
                            <h2 class="text-2xl sm:text-3xl font-bold text-baytgo">{{ __('welcome.how_title') }}</h2>
                            <p class="mt-2 text-slate-600">{{ __('welcome.how_sub') }}</p>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                            @foreach (range(0, 3) as $i)
                                @php $step = __('welcome.steps')[$i]; @endphp
                                <article class="rounded-2xl bg-white border border-slate-200/80 p-6 shadow-sm text-center lg:text-left">
                                    <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-baytgo text-gold-light text-sm font-bold">{{ $i + 1 }}</span>
                                    <h3 class="mt-4 font-bold text-slate-900">{{ $step['title'] }}</h3>
                                    <p class="mt-2 text-sm text-slate-600 leading-relaxed">{{ $step['desc'] }}</p>
                                </article>
                            @endforeach
                        </div>
                    </div>
                </section>

                {{-- About --}}
                <section id="tentang" class="py-16 bg-white border-t border-slate-100">
                    <div class="max-w-3xl mx-auto px-4 text-center">
                        <h2 class="text-2xl font-bold text-baytgo">{{ __('welcome.about_title') }}</h2>
                        <p class="mt-4 text-slate-600 leading-relaxed">{{ __('welcome.about_sub') }}</p>
                    </div>
                </section>

                {{-- FAQ --}}
                <section id="faq" class="py-16 bg-slate-50 border-t border-slate-100">
                    <div class="max-w-3xl mx-auto px-4">
                        <h2 class="text-2xl font-bold text-baytgo text-center mb-10">{{ __('welcome.faq_title') }}</h2>
                        <dl class="space-y-4">
                            @foreach (__('welcome.faq_items') as $item)
                                <div class="rounded-2xl bg-white border border-slate-200/90 p-5 shadow-sm">
                                    <dt class="font-semibold text-slate-900">{{ $item['q'] }}</dt>
                                    <dd class="mt-2 text-sm text-slate-600 leading-relaxed">{{ $item['a'] }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    </div>
                </section>

                {{-- CTA --}}
                <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-14">
                    <div class="rounded-2xl bg-gradient-to-br from-baytgo via-baytgo-800 to-baytgo-950 p-8 sm:p-10 text-white shadow-xl relative overflow-hidden">
                        <div class="absolute top-0 right-0 w-64 h-64 bg-gold/10 rounded-full blur-3xl -translate-y-1/2 translate-x-1/2 pointer-events-none" aria-hidden="true"></div>
                        <div class="relative flex flex-col lg:flex-row lg:items-center lg:justify-between gap-8">
                            <div>
                                <p class="font-bold text-xl sm:text-2xl">{{ __('welcome.cta_title') }}</p>
                                <p class="mt-2 text-white/85 text-sm sm:text-base max-w-xl">{{ __('welcome.cta_sub') }}</p>
                            </div>
                            <div class="flex flex-col sm:flex-row gap-3 shrink-0">
                                @guest
                                    <a href="{{ route('register') }}" class="inline-flex justify-center items-center px-6 py-3 rounded-xl bg-gold font-semibold text-baytgo-950 shadow hover:bg-gold-muted transition">{{ __('welcome.cta_register_pilgrim') }}</a>
                                    <a href="{{ route('register') }}" class="inline-flex justify-center items-center px-6 py-3 rounded-xl border-2 border-white/30 text-white font-semibold hover:bg-white/10 transition">{{ __('welcome.cta_register_muthowif') }}</a>
                                @else
                                    <a href="{{ route('dashboard') }}" class="inline-flex justify-center items-center px-6 py-3 rounded-xl bg-gold font-semibold text-baytgo-950 shadow hover:bg-gold-muted transition">{{ __('welcome.cta_dashboard') }}</a>
                                @endguest
                            </div>
                        </div>
                    </div>
                </section>
            </main>

            <footer class="border-t border-slate-200 bg-white mt-auto">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 flex flex-col sm:flex-row items-center justify-between gap-4 text-sm text-slate-500">
                    <div class="flex flex-col items-center gap-2 sm:items-start sm:flex-row sm:gap-4">
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
                </div>
            </footer>
        </div>
    </body>
</html>
