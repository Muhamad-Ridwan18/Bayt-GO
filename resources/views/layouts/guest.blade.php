<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'BaytGo') }}</title>

        <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
        <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
        <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
        <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
        <link rel="manifest" href="{{ asset('site.webmanifest') }}">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    @php
        $isWide = $wide ?? false;
        $authVariant = $variant ?? 'default';
        $formMaxClass = $isWide ? 'max-w-3xl' : 'max-w-lg';
    @endphp
    <body class="min-h-screen bg-[#f4f6f5] font-sans text-slate-800 antialiased">
        <x-ui.toast-stack />
        <div class="min-h-screen lg:grid lg:grid-cols-2">
            @include('partials.auth-hero-panel', ['variant' => $authVariant, 'heroImage' => $heroImage])

            <div class="relative flex min-h-screen flex-col">
                {{-- Mobile hero strip --}}
                <div class="relative overflow-hidden border-b border-emerald-100 bg-welcomeCanvas px-4 py-6 sm:px-6 lg:hidden">
                    <a href="{{ url('/') }}" class="inline-flex items-center gap-2 text-baytgo">
                        <x-site-logo variant="guest" />
                        <span class="text-base font-bold tracking-tight">Bayt<span class="text-gold-muted">Go</span></span>
                    </a>
                    <p class="mt-3 text-[10px] font-bold uppercase tracking-[0.16em] text-emerald-800/80">{{ __('guest.panel_kicker') }}</p>
                    <h2 class="mt-2 text-lg font-bold leading-snug text-baytgo">
                        {{ $authVariant === 'register' ? __('guest.panel_register_title') : __('guest.panel_login_title') }}
                    </h2>
                </div>

                <header class="relative shrink-0 px-4 py-5 sm:px-6 lg:px-10 xl:px-12">
                    <div class="mx-auto flex w-full {{ $formMaxClass }} items-center justify-between lg:mx-0 lg:max-w-none">
                        <a href="{{ url('/') }}" class="inline-flex items-center gap-2 text-sm font-medium text-slate-500 transition hover:text-baytgo">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" /></svg>
                            {{ __('guest.back_home') }}
                        </a>
                        <nav class="flex items-center gap-2 text-sm">
                            @auth
                                <a href="{{ route('dashboard') }}" class="rounded-lg px-3 py-1.5 font-medium text-slate-600 transition hover:bg-white hover:text-baytgo">{{ __('nav.home') }}</a>
                            @else
                                @if (Route::has('login') && $authVariant !== 'login')
                                    <a href="{{ route('login') }}" class="rounded-lg px-3 py-1.5 font-medium text-slate-600 transition hover:bg-white hover:text-baytgo">{{ __('layanan.guest_header_login') }}</a>
                                @endif
                                @if (Route::has('register') && $authVariant !== 'register')
                                    <a href="{{ route('register') }}" class="rounded-xl bg-baytgo px-4 py-2.5 font-semibold text-white shadow-sm transition hover:bg-baytgo-800">{{ __('layanan.guest_header_register') }}</a>
                                @endif
                            @endauth
                        </nav>
                    </div>
                </header>

                <main class="relative flex flex-1 flex-col justify-center px-4 pb-10 sm:px-6 lg:px-10 lg:pb-12 xl:px-12">
                    <div class="mx-auto w-full {{ $formMaxClass }} lg:mx-0 lg:max-w-none">
                        <div class="rounded-[1.75rem] border border-slate-200/80 bg-white p-6 shadow-[0_18px_48px_-24px_rgba(26,61,52,0.28)] sm:p-8 {{ $isWide ? 'sm:p-9' : 'sm:p-10' }}">
                            {{ $slot }}
                        </div>

                        <div class="mt-6">
                            @include('partials.auth-trust-chips')
                        </div>

                        <p class="mt-5 text-center text-xs text-slate-500 lg:text-start">
                            {{ __('guest.footer_tagline') }}
                        </p>
                    </div>
                </main>
            </div>
        </div>
    </body>
</html>
