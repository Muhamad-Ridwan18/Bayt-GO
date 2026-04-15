<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
@php
    $contactRaw = (string) (config('app.contact_whatsapp') ?: config('app.contact_phone'));
    $contactDigits = preg_replace('/\D+/', '', $contactRaw ?? '') ?? '';
    $contactLink = $contactDigits !== '' ? 'https://wa.me/'.$contactDigits : null;
@endphp
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name', 'BaytGo') }} — {{ __('welcome.page_title') }}</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased text-slate-800 bg-slate-50 min-h-screen">
        <div class="min-h-screen flex flex-col">
            {{-- Top bar --}}
            <header class="sticky top-0 z-20 border-b border-white/10 bg-slate-900/95 backdrop-blur-md text-white">
                <div class="max-w-6xl mx-auto px-4 sm:px-6 py-3.5 flex items-center justify-between gap-4">
                    <a href="{{ url('/') }}" class="flex items-center gap-2.5 group">
                        <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-brand-400 to-brand-700 text-white text-sm font-bold shadow-lg shadow-brand-900/40 ring-1 ring-white/20">BG</span>
                        <span class="text-lg font-bold tracking-tight">Bayt<span class="text-brand-300">Go</span></span>
                    </a>
                    <nav class="flex items-center gap-1 sm:gap-2 text-sm flex-wrap justify-end">
                        <a href="{{ route('layanan.index') }}" class="px-3 sm:px-4 py-2 rounded-xl font-medium text-white/90 hover:bg-white/10 transition">{{ __('layanan.find_muthowif') }}</a>
                        @if ($contactLink)
                            <a href="{{ $contactLink }}" target="_blank" rel="noopener noreferrer" class="px-3 sm:px-4 py-2 rounded-xl font-medium text-brand-100 hover:bg-white/10 transition">{{ __('nav.contact_us') }}</a>
                        @endif
                        @auth
                            <a href="{{ route('dashboard') }}" class="px-3 sm:px-4 py-2 rounded-xl font-medium text-white/90 hover:bg-white/10 transition">{{ __('nav.home') }}</a>
                        @else
                            @if (Route::has('login'))
                                <a href="{{ route('login') }}" class="px-3 sm:px-4 py-2 rounded-xl font-medium text-white/90 hover:bg-white/10 transition">{{ __('layanan.guest_header_login') }}</a>
                            @endif
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="px-4 py-2 rounded-xl font-semibold bg-brand-500 text-white shadow-md hover:bg-brand-400 transition ring-1 ring-white/20">{{ __('layanan.guest_header_register') }}</a>
                            @endif
                        @endauth
                    </nav>
                </div>
            </header>

            <main class="flex-1">
                {{-- Hero + search (marketplace core) --}}
                <section class="relative overflow-hidden bg-gradient-to-br from-slate-900 via-brand-900 to-amber-950 text-white">
                    <div class="absolute inset-0 bg-[url('data:image/svg+xml,%3Csvg width=\'60\' height=\'60\' viewBox=\'0 0 60 60\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'none\' fill-rule=\'evenodd\'%3E%3Cg fill=\'%23ffffff\' fill-opacity=\'0.04\'%3E%3Cpath d=\'M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E')] opacity-50"></div>
                    <div class="absolute top-0 right-0 w-96 h-96 bg-brand-500/20 rounded-full blur-3xl -translate-y-1/2 translate-x-1/3"></div>
                    <div class="absolute bottom-0 left-0 w-80 h-80 bg-amber-500/15 rounded-full blur-3xl translate-y-1/2 -translate-x-1/4"></div>

                    <div class="relative max-w-6xl mx-auto px-4 sm:px-6 pt-12 sm:pt-16 pb-12 sm:pb-16">
                        <div class="max-w-3xl">
                            <p class="inline-flex items-center gap-2 rounded-full bg-white/10 text-brand-100 text-xs font-semibold px-3 py-1.5 mb-5 ring-1 ring-white/20">
                                <span class="relative flex h-2 w-2">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-400"></span>
                                </span>
                                Marketplace pendamping umrah
                            </p>
                            <h1 class="text-3xl sm:text-5xl font-bold leading-[1.1] tracking-tight">
                                Booking muthowif seperti booking hotel — <span class="text-transparent bg-clip-text bg-gradient-to-r from-brand-200 to-amber-200">pilih tanggal, lihat yang siap.</span>
                            </h1>
                            <p class="mt-5 text-base sm:text-lg text-brand-100/90 leading-relaxed max-w-2xl">
                                Harga transparan (group &amp; private), jadwal libur &amp; slot booking otomatis, semua di satu tempat.
                            </p>
                        </div>

                        <div class="mt-10 max-w-4xl">
                            <p class="text-sm font-medium text-brand-100/90 mb-3">{{ __('welcome.search_label') }}</p>
                            @include('layanan.partials.date-search-form', [
                                'startDate' => '',
                                'endDate' => '',
                                'searchQuery' => '',
                            ])
                        </div>

                        <div class="mt-10 flex flex-wrap gap-3 sm:gap-4 text-sm">
                            @foreach ([
                                ['label' => __('welcome.badge_1'), 'icon' => 'M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
                                ['label' => __('welcome.badge_2'), 'icon' => 'M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5a2.25 2.25 0 002.25-2.25m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5a2.25 2.25 0 012.25 2.25v7.5'],
                                ['label' => __('welcome.badge_3'), 'icon' => 'M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3z'],
                            ] as $badge)
                                <div class="inline-flex items-center gap-2 rounded-2xl bg-white/10 px-4 py-2.5 ring-1 ring-white/15 backdrop-blur-sm">
                                    <svg class="h-5 w-5 text-amber-300 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $badge['icon'] }}" />
                                    </svg>
                                    <span class="font-medium text-white/95">{{ $badge['label'] }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </section>

                {{-- CTA strip --}}
                <section class="max-w-6xl mx-auto px-4 sm:px-6 -mt-6 relative z-10">
                    <div class="rounded-2xl bg-white shadow-market border border-slate-200/80 p-5 sm:p-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div>
                            <p class="font-semibold text-slate-900">{{ __('welcome.cta_title') }}</p>
                            <p class="mt-1 text-sm text-slate-600">{{ __('welcome.cta_sub') }}</p>
                        </div>
                        <div class="flex flex-col sm:flex-row gap-3 shrink-0">
                            @guest
                                <a href="{{ route('register') }}" class="inline-flex justify-center items-center px-6 py-3 rounded-xl bg-brand-600 text-white font-semibold shadow-md hover:bg-brand-700 transition text-center">
                                    {{ __('welcome.cta_register_pilgrim') }}
                                </a>
                                <a href="{{ route('register') }}" class="inline-flex justify-center items-center px-6 py-3 rounded-xl border-2 border-slate-200 text-slate-800 font-semibold bg-white hover:border-brand-300 hover:text-brand-800 transition text-center">
                                    {{ __('welcome.cta_register_muthowif') }}
                                </a>
                            @else
                                <a href="{{ route('dashboard') }}" class="inline-flex justify-center items-center px-6 py-3 rounded-xl bg-brand-600 text-white font-semibold shadow-md hover:bg-brand-700 transition">
                                    {{ __('welcome.cta_dashboard') }}
                                </a>
                            @endguest
                        </div>
                    </div>
                </section>

                {{-- How it works --}}
                <section class="max-w-6xl mx-auto px-4 sm:px-6 py-16 sm:py-20">
                    <div class="max-w-2xl mb-10">
                        <h2 class="text-2xl sm:text-3xl font-bold text-slate-900">{{ __('welcome.how_title') }}</h2>
                        <p class="mt-2 text-slate-600">{{ __('welcome.how_sub') }}</p>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                        @foreach (array_map(fn ($i) => array_merge(['n' => (string) ($i + 1)], __('welcome.steps')[$i]), range(0, 3)) as $step)
                            <article class="group relative rounded-2xl bg-white border border-slate-200/80 p-6 shadow-sm hover:shadow-market hover:border-brand-200/60 transition-all">
                                <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-brand-500 to-brand-700 text-white text-sm font-bold shadow-md">{{ $step['n'] }}</span>
                                <h3 class="mt-4 font-semibold text-slate-900 group-hover:text-brand-800 transition-colors">{{ $step['title'] }}</h3>
                                <p class="mt-2 text-sm text-slate-600 leading-relaxed">{{ $step['desc'] }}</p>
                            </article>
                        @endforeach
                    </div>
                </section>
            </main>

            <footer class="border-t border-slate-200 bg-white mt-auto">
                <div class="max-w-6xl mx-auto px-4 sm:px-6 py-8 flex flex-col sm:flex-row items-center justify-between gap-4 text-sm text-slate-500">
                    <span>&copy; {{ date('Y') }} {{ config('app.name') }}</span>
                    <div class="flex flex-col items-center gap-1 sm:items-end">
                        <span class="text-xs text-slate-400">{{ __('welcome.footer_tagline') }}</span>
                        @if ($contactLink)
                            <a href="{{ $contactLink }}" target="_blank" rel="noopener noreferrer" class="text-xs font-medium text-brand-700 hover:text-brand-800">
                                {{ __('marketplace.footer_contact', ['contact' => $contactRaw]) }}
                            </a>
                        @endif
                    </div>
                </div>
            </footer>
        </div>
    </body>
</html>
