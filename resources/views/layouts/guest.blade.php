<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'BaytGo') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-slate-800 antialiased min-h-screen bg-gradient-to-b from-brand-50 via-white to-amber-50/40">
        <div class="min-h-screen flex flex-col">
            <header class="shrink-0 px-4 sm:px-6 py-4 flex items-center justify-between max-w-lg mx-auto w-full sm:max-w-md">
                <a href="{{ url('/') }}" class="flex items-center gap-2 group">
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-brand-600 text-white shadow-market text-sm font-bold shadow-brand-600/20">BG</span>
                    <span class="text-lg font-semibold tracking-tight text-slate-900">Bayt<span class="text-brand-600">Go</span></span>
                </a>
                <nav class="flex items-center gap-2 text-sm">
                    @auth
                        <a href="{{ route('dashboard') }}" class="text-slate-600 hover:text-brand-700 font-medium transition">Beranda</a>
                    @else
                        @if (Route::has('login'))
                            <a href="{{ route('login') }}" class="px-3 py-1.5 rounded-lg text-slate-600 hover:bg-white/80 font-medium transition">Masuk</a>
                        @endif
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="px-3 py-1.5 rounded-lg bg-brand-600 text-white font-medium shadow-sm hover:bg-brand-700 transition">Daftar</a>
                        @endif
                    @endauth
                </nav>
            </header>

            <main class="flex-1 flex flex-col justify-center px-4 pb-10 sm:px-6">
                <div class="w-full max-w-md mx-auto">
                    <div class="rounded-2xl bg-white/90 backdrop-blur-sm border border-slate-200/80 shadow-market p-6 sm:p-8">
                        {{ $slot }}
                    </div>
                    <p class="mt-6 text-center text-xs text-slate-500">
                        Marketplace umrah — jamaah &amp; muthowif dalam satu platform.
                    </p>
                </div>
            </main>
        </div>
    </body>
</html>
