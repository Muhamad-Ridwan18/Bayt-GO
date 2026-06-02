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

                    <div class="relative z-20 mx-auto mt-8 max-w-7xl w-full px-4 sm:px-6 lg:px-8">
                        <x-campaign-carousel :campaigns="$activeCampaigns ?? collect()" />
                    </div>

                    @if(isset($landingPages) && $landingPages->isNotEmpty())
                        <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
                            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                                <div class="flex flex-col gap-6 md:flex-row md:items-end md:justify-between">
                                    <div>
                                        <p class="text-sm font-semibold uppercase tracking-[0.24em] text-baytgo">Kategori Pencarian</p>
                                        <h2 class="mt-3 text-2xl font-bold text-slate-900">Temukan muthowif berdasarkan kebutuhan ibadah Anda</h2>
                                    </div>
                                    <a href="{{ route('layanan.index') }}" class="text-sm font-semibold text-baytgo hover:text-baytgo-700">Lihat semua layanan</a>
                                </div>

                                <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                    @foreach($landingPages as $landing)
                                        <a href="{{ route('seo.landing', ['keyword' => $landing['slug']]) }}" class="group rounded-3xl border border-slate-200 p-6 transition hover:border-baytgo/50 hover:bg-baytgo/5">
                                            <p class="text-sm font-semibold uppercase tracking-[0.24em] text-baytgo">{{ $landing['title'] }}</p>
                                            <p class="mt-3 text-sm leading-relaxed text-slate-600">{{ $landing['subtitle'] }}</p>
                                            <span class="mt-5 inline-flex items-center gap-2 text-sm font-semibold text-baytgo group-hover:text-baytgo-800">
                                                Jelajahi
                                                <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                    <path fill-rule="evenodd" d="M10.293 15.707a1 1 0 010-1.414L13.586 11H4a1 1 0 110-2h9.586l-3.293-3.293a1 1 0 111.414-1.414l5 5a1 1 0 010 1.414l-5 5a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                                </svg>
                                            </span>
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        </section>
                    @endif
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

                {{-- <div class="h-14 bg-white sm:h-16" aria-hidden="true"></div> --}}

                {{-- Value Proposition --}}
                <section class="bg-white pt-12 pb-16 sm:pt-16 sm:pb-20">
                    <x-page-container>
                        <div class="mb-10 text-center sm:mb-12">
                            <p class="text-xs font-bold uppercase tracking-widest text-gold mb-3">{{ __('welcome.value_kicker') }}</p>
                            <h2 class="text-xl font-bold text-slate-900 sm:text-2xl">{{ __('welcome.value_title') }}</h2>
                            <span class="mx-auto mt-5 block h-1 w-12 rounded-full bg-gold" aria-hidden="true"></span>
                        </div>

                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 lg:gap-8 mb-8">
                            {{-- For Customer --}}
                            <div class="rounded-3xl border border-emerald-100/70 bg-white p-6 sm:p-8 shadow-[0_2px_12px_-4px_rgba(16,185,129,0.06)] transition-all hover:shadow-[0_4px_20px_-4px_rgba(16,185,129,0.1)] hover:border-emerald-200/80 group">
                                <div class="flex items-center gap-4 mb-8">
                                    <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-[1rem] bg-emerald-50 text-emerald-600 ring-1 ring-emerald-100/80 transition-transform group-hover:scale-105 group-hover:bg-emerald-100">
                                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" /></svg>
                                    </span>
                                    <div>
                                        <h3 class="text-lg font-bold text-slate-900">{{ __('welcome.value_customer_title') }}</h3>
                                        <p class="mt-0.5 text-[13px] text-slate-600 leading-snug">{{ __('welcome.value_customer_sub') }}</p>
                                    </div>
                                </div>
                                
                                <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-2 xl:grid-cols-4 gap-x-4 gap-y-6">
                                    @php
                                        $customerIcons = [
                                            '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5a2.25 2.25 0 002.25-2.25m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5a2.25 2.25 0 012.25 2.25v7.5" />',
                                            '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />',
                                            '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3z" />',
                                            '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />',
                                        ];
                                        $c = 0;
                                    @endphp
                                    @foreach(__('welcome.value_customer_features') as $fTitle => $fDesc)
                                        <div class="text-center sm:text-left xl:text-center">
                                            <span class="inline-flex xl:mx-auto h-10 w-10 items-center justify-center rounded-[0.85rem] bg-emerald-50 text-emerald-600 mb-2.5 ring-1 ring-emerald-100/50">
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">{!! $customerIcons[$c] !!}</svg>
                                            </span>
                                            <h4 class="text-xs font-bold text-slate-900 mb-1.5">{{ $fTitle }}</h4>
                                            <p class="text-[11px] text-slate-500 leading-relaxed">{{ $fDesc }}</p>
                                        </div>
                                        @php $c++; @endphp
                                    @endforeach
                                </div>
                            </div>

                            {{-- For Muthowif --}}
                            <div class="rounded-3xl border border-amber-100/70 bg-white p-6 sm:p-8 shadow-[0_2px_12px_-4px_rgba(245,158,11,0.06)] transition-all hover:shadow-[0_4px_20px_-4px_rgba(245,158,11,0.1)] hover:border-amber-200/80 group">
                                <div class="flex items-center gap-4 mb-8">
                                    <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-[1rem] bg-amber-50 text-amber-600 ring-1 ring-amber-100/80 transition-transform group-hover:scale-105 group-hover:bg-amber-100">
                                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M17.982 18.725A7.488 7.488 0 0012 15.75a7.488 7.488 0 00-5.982 2.975m11.963 0a9 9 0 10-11.963 0m11.963 0A8.966 8.966 0 0112 21a8.966 8.966 0 01-5.982-2.275M15 9.75a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                    </span>
                                    <div>
                                        <h3 class="text-lg font-bold text-slate-900">{{ __('welcome.value_muthowif_title') }}</h3>
                                        <p class="mt-0.5 text-[13px] text-slate-600 leading-snug">{{ __('welcome.value_muthowif_sub') }}</p>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-2 xl:grid-cols-4 gap-x-4 gap-y-6">
                                    @php
                                        $muthowifIcons = [
                                            '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5a2.25 2.25 0 002.25-2.25m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5a2.25 2.25 0 012.25 2.25v7.5" />',
                                            '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M20.25 14.15v4.25c0 1.094-.787 2.036-1.872 2.18-2.087.277-4.216.42-6.378.42s-4.291-.143-6.378-.42c-1.085-.144-1.872-1.086-1.872-2.18v-4.25m16.5 0a2.18 2.18 0 00.75-1.661V8.706c0-1.081-.768-2.015-1.837-2.175a48.114 48.114 0 00-3.413-.387m4.5 8.006c-.194.165-.42.295-.673.38A23.978 23.978 0 0112 15.75c-2.648 0-5.195-.429-7.577-1.22a2.016 2.016 0 01-.673-.38m0 0A2.18 2.18 0 013 12.489V8.706c0-1.081.768-2.015 1.837-2.175a48.111 48.111 0 013.413-.387m7.5 0V5.25A2.25 2.25 0 0013.5 3h-3a2.25 2.25 0 00-2.25 2.25v.894m7.5 0a48.667 48.667 0 00-7.5 0M12 12.75h.008v.008H12v-.008z" />',
                                            '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.726 0-1.452-.219-2.004-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.004 0l.851.659M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />',
                                            '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-1.64 1.505l1.21 5.312c.11.486-.411.867-.84.621L12 18.061a.563.563 0 00-.56 0l-4.724 2.825c-.429.246-.95-.135-.84-.621l1.21-5.312a.563.563 0 00-1.64-1.505l-4.204-3.602c-.38-.325-.178-.948.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z" />',
                                        ];
                                        $c = 0;
                                    @endphp
                                    @foreach(__('welcome.value_muthowif_features') as $fTitle => $fDesc)
                                        <div class="text-center sm:text-left xl:text-center">
                                            <span class="inline-flex xl:mx-auto h-10 w-10 items-center justify-center rounded-[0.85rem] bg-amber-50 text-amber-600 mb-2.5 ring-1 ring-amber-100/50">
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">{!! $muthowifIcons[$c] !!}</svg>
                                            </span>
                                            <h4 class="text-xs font-bold text-slate-900 mb-1.5">{{ $fTitle }}</h4>
                                            <p class="text-[11px] text-slate-500 leading-relaxed">{{ $fDesc }}</p>
                                        </div>
                                        @php $c++; @endphp
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        {{-- Bottom Banner --}}
                        <div class="rounded-[2rem] border border-slate-200 bg-[#FBFBFA] p-6 sm:px-8 sm:py-6">
                            <div class="flex flex-col lg:flex-row items-center gap-6 lg:gap-10">
                                <div class="flex items-center gap-4 shrink-0 lg:border-r lg:border-slate-200 lg:pr-8">
                                    <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-white text-slate-700 shadow-sm ring-1 ring-slate-200">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12.75L11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 01-1.043 3.296 3.745 3.745 0 01-3.296 1.043A3.745 3.745 0 0112 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 01-3.296-1.043 3.745 3.745 0 01-1.043-3.296A3.745 3.745 0 013 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 011.043-3.296 3.746 3.746 0 013.296-1.043A3.746 3.746 0 0112 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 013.296 1.043 3.746 3.746 0 011.043 3.296A3.745 3.745 0 0121 12z" /></svg>
                                    </span>
                                    <div>
                                        <h3 class="text-sm font-bold text-slate-900">{{ __('welcome.value_banner_title') }}</h3>
                                        <p class="text-[13px] text-slate-500 mt-0.5 max-w-[280px] leading-snug">{{ __('welcome.value_banner_sub') }}</p>
                                    </div>
                                </div>

                                <div class="flex-1 w-full grid grid-cols-2 sm:grid-cols-4 gap-5">
                                    @php
                                        $bannerIcons = [
                                            '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />',
                                            '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />',
                                            '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 3v17.25m0 0c-1.472 0-2.882.265-4.185.75M12 20.25c1.472 0 2.882.265 4.185.75M18.75 4.97A48.416 48.416 0 0012 4.5c-2.291 0-4.545.16-6.75.47m13.5 0c1.01.143 2.01.317 3 .52m-3-.52l2.62 10.726c.122.499-.106 1.028-.589 1.202a5.988 5.988 0 01-2.031.352 5.988 5.988 0 01-2.031-.352c-.483-.174-.711-.703-.59-1.202L18.75 4.971zm-16.5.52c.99-.203 1.99-.377 3-.52m0 0l2.62 10.726c.122.499-.106 1.028-.589 1.202a5.989 5.989 0 01-2.031.352 5.989 5.989 0 01-2.031-.352c-.483-.174-.711-.703-.59-1.202L5.25 4.971z" />',
                                            '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M8.625 9.75a3.375 3.375 0 116.75 0v8.25a.75.75 0 01-.75.75h-5.25a.75.75 0 01-.75-.75v-8.25zM3.75 16.5v-7a8.25 8.25 0 1116.5 0v7h-3v-5.5a5.25 5.25 0 10-10.5 0v5.5h-3z" />',
                                        ];
                                        $c = 0;
                                    @endphp
                                    @foreach(__('welcome.value_banner_features') as $fTitle => $fDesc)
                                        <div class="text-center sm:text-left flex flex-col items-center sm:items-start gap-1.5">
                                            <span class="text-slate-500">
                                                <svg class="h-[22px] w-[22px]" fill="none" viewBox="0 0 24 24" stroke="currentColor">{!! $bannerIcons[$c] !!}</svg>
                                            </span>
                                            <div>
                                                <h4 class="text-xs font-bold text-slate-800 mb-0.5">{{ $fTitle }}</h4>
                                                <p class="text-[11px] text-slate-500 leading-snug">{{ $fDesc }}</p>
                                            </div>
                                        </div>
                                        @php $c++; @endphp
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </x-page-container>
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

                {{-- Running Photo Gallery --}}
                @php
                    $galleryImages = $galleryImages ?? collect();
                    // Pad to at least 10 items so the strip never looks empty
                    $galleryChunked = $galleryImages->count() >= 2
                        ? $galleryImages->chunk((int) ceil($galleryImages->count() / 2))
                        : collect();
                    $row1 = $galleryChunked->get(0, collect());
                    $row2 = $galleryChunked->get(1, collect());
                @endphp

                @if ($galleryImages->isNotEmpty())
                <section class="relative overflow-hidden bg-baytgo-950 py-12 sm:py-16" aria-label="Galeri Perjalanan Muthowif">
                    {{-- Decorative radial glow --}}
                    <div class="pointer-events-none absolute inset-0 opacity-30 bg-[radial-gradient(ellipse_at_center,_#C5A059_0%,_transparent_65%)]" aria-hidden="true"></div>

                    <div class="relative z-10 mb-8 text-center px-4">
                        <p class="text-xs font-bold uppercase tracking-[0.22em] text-gold mb-2">Galeri Perjalanan</p>
                        <h2 class="text-2xl sm:text-3xl font-bold text-white">Momen Bersama Muthowif Kami</h2>
                        <span class="mx-auto mt-4 block h-0.5 w-10 rounded-full bg-gold/60" aria-hidden="true"></span>
                    </div>

                    {{-- Row 1: left-to-right --}}
                    <div class="marquee-wrap mb-3">
                        <div class="marquee-track">
                            {{-- First copy --}}
                            @foreach ($row1 as $img)
                                <div class="mx-1.5 shrink-0 overflow-hidden rounded-xl w-48 h-32 sm:w-56 sm:h-36 lg:w-64 lg:h-40 ring-1 ring-white/10 shadow-lg">
                                    <img
                                        src="{{ route('layanan.portfolio.image', $img->id) }}"
                                        alt=""
                                        class="h-full w-full object-cover transition duration-500 hover:scale-105"
                                        loading="lazy"
                                        decoding="async"
                                    >
                                </div>
                            @endforeach
                            {{-- Duplicate for seamless loop --}}
                            @foreach ($row1 as $img)
                                <div class="mx-1.5 shrink-0 overflow-hidden rounded-xl w-48 h-32 sm:w-56 sm:h-36 lg:w-64 lg:h-40 ring-1 ring-white/10 shadow-lg" aria-hidden="true">
                                    <img
                                        src="{{ route('layanan.portfolio.image', $img->id) }}"
                                        alt=""
                                        class="h-full w-full object-cover transition duration-500 hover:scale-105"
                                        loading="lazy"
                                        decoding="async"
                                    >
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- Row 2: right-to-left --}}
                    @if ($row2->isNotEmpty())
                    <div class="marquee-wrap">
                        <div class="marquee-track-reverse">
                            {{-- First copy --}}
                            @foreach ($row2 as $img)
                                <div class="mx-1.5 shrink-0 overflow-hidden rounded-xl w-48 h-32 sm:w-56 sm:h-36 lg:w-64 lg:h-40 ring-1 ring-white/10 shadow-lg">
                                    <img
                                        src="{{ route('layanan.portfolio.image', $img->id) }}"
                                        alt=""
                                        class="h-full w-full object-cover transition duration-500 hover:scale-105"
                                        loading="lazy"
                                        decoding="async"
                                    >
                                </div>
                            @endforeach
                            {{-- Duplicate for seamless loop --}}
                            @foreach ($row2 as $img)
                                <div class="mx-1.5 shrink-0 overflow-hidden rounded-xl w-48 h-32 sm:w-56 sm:h-36 lg:w-64 lg:h-40 ring-1 ring-white/10 shadow-lg" aria-hidden="true">
                                    <img
                                        src="{{ route('layanan.portfolio.image', $img->id) }}"
                                        alt=""
                                        class="h-full w-full object-cover transition duration-500 hover:scale-105"
                                        loading="lazy"
                                        decoding="async"
                                    >
                                </div>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    {{-- Bottom CTA overlay --}}
                    <div class="relative z-10 mt-8 text-center">
                        <a href="{{ route('layanan.index') }}" class="inline-flex items-center gap-2 rounded-full border border-white/25 bg-white/10 px-6 py-2.5 text-sm font-semibold text-white backdrop-blur-sm transition hover:bg-white/20">
                            Lihat Semua Muthowif
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg>
                        </a>
                    </div>
                </section>
                @endif

                {{-- Latest Articles --}}
                @if ($latestArticles->isNotEmpty())
                    <section id="artikel-terbaru" class="py-16 sm:py-20 border-t border-slate-100 bg-welcomeCanvas">
                        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                            <div class="flex flex-wrap items-end justify-between gap-4 mb-10">
                                <div>
                                    <h2 class="text-2xl sm:text-3xl font-bold text-baytgo">{{ __('nav.articles') }}</h2>
                                    <span class="mt-4 block h-1 w-14 rounded-full bg-gold" aria-hidden="true"></span>
                                </div>
                                <a href="{{ route('articles.index') }}" class="text-sm font-semibold text-gold-muted hover:text-baytgo transition inline-flex items-center gap-1">
                                    {{ __('welcome.popular_see_all') }}
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5 15.75 12l-7.5 7.5"/></svg>
                                </a>
                            </div>

                            <div class="grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
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
                                            <h3 class="text-xl font-bold text-slate-900 group-hover:text-baytgo transition-colors leading-snug">
                                                <a href="{{ route('articles.show', ['slug' => $article->slug]) }}" class="focus:outline-none">{{ $article->localized('title') }}</a>
                                            </h3>
                                            <p class="mt-4 flex-1 text-sm leading-relaxed text-slate-600 line-clamp-3">
                                                {{ $article->localized('excerpt') }}
                                            </p>
                                            <div class="mt-6 flex items-center gap-2 pt-5 border-t border-slate-100">
                                                <span class="text-[11px] font-bold text-baytgo/80 uppercase tracking-tight">{{ __('articles.reading_minutes', ['count' => $article->readingMinutes()]) }}</span>
                                                <span class="text-slate-300">•</span>
                                                <span class="text-[11px] font-medium text-slate-500 uppercase tracking-tight">{{ $article->localized('author') }}</span>
                                            </div>
                                        </div>
                                    </article>
                                @endforeach
                            </div>
                        </div>
                    </section>
                @endif

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
                    <x-page-container class="text-center">
                        <h2 class="text-2xl sm:text-3xl font-bold text-baytgo">{{ __('welcome.pricing_title') }}</h2>
                        <p class="mt-3 text-slate-600">{{ __('welcome.pricing_sub') }}</p>
                        <a href="{{ route('layanan.index') }}" class="mt-8 inline-flex items-center rounded-xl bg-baytgo px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-baytgo/20 hover:bg-baytgo-800 transition">{{ __('welcome.pricing_cta') }}</a>
                    </x-page-container>
                </section>

                {{-- How it works --}}
                <section id="cara-kerja" class="py-16 sm:py-20 bg-slate-50 border-t border-slate-100">
                    <x-page-container>
                        <div class="mx-auto mb-12 max-w-2xl text-center">
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
                    </x-page-container>
                </section>

                {{-- About --}}
                <section id="tentang" class="py-16 bg-white border-t border-slate-100">
                    <x-page-container class="text-center">
                        <h2 class="text-2xl font-bold text-baytgo">{{ __('welcome.about_title') }}</h2>
                        <p class="mt-4 text-slate-600 leading-relaxed">{{ __('welcome.about_sub') }}</p>
                    </x-page-container>
                </section>

                {{-- FAQ --}}
                <section id="faq" class="py-16 bg-slate-50 border-t border-slate-100">
                    <x-page-container>
                        <h2 class="text-2xl font-bold text-baytgo text-center mb-10">{{ __('welcome.faq_title') }}</h2>
                        <dl class="space-y-4">
                            @foreach (__('welcome.faq_items') as $item)
                                <div class="rounded-2xl bg-white border border-slate-200/90 p-5 shadow-sm">
                                    <dt class="font-semibold text-slate-900">{{ $item['q'] }}</dt>
                                    <dd class="mt-2 text-sm text-slate-600 leading-relaxed">{{ $item['a'] }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    </x-page-container>
                </section>
            </main>

            <footer class="border-t border-slate-200 bg-white mt-auto">
                <x-page-container class="flex flex-col gap-4 py-8 text-sm text-slate-500 sm:flex-row sm:items-center sm:justify-between">
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
                </x-page-container>
            </footer>
        </div>
    </body>
</html>
