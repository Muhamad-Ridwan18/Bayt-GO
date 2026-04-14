@props(['title' => null])
@php
    $contactRaw = (string) (config('app.contact_whatsapp') ?: config('app.contact_phone'));
    $contactDigits = preg_replace('/\D+/', '', $contactRaw ?? '') ?? '';
    $contactLink = $contactDigits !== '' ? 'https://wa.me/'.$contactDigits : null;
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ $title ? $title.' — '.config('app.name') : config('app.name') }}</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased text-slate-800 bg-gradient-to-b from-brand-50 via-white to-amber-50/50 min-h-screen">
        <div class="min-h-screen flex flex-col">
            @auth
                @include('layouts.navigation')
            @else
                <header class="border-b border-slate-200/80 bg-white/80 backdrop-blur-md sticky top-0 z-10">
                    <div class="max-w-6xl mx-auto px-4 sm:px-6 py-4 flex items-center justify-between gap-4">
                        <a href="{{ url('/') }}" class="flex items-center gap-2">
                            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-brand-600 text-white text-sm font-bold shadow-market">BG</span>
                            <span class="text-lg font-semibold tracking-tight">Bayt<span class="text-brand-600">Go</span></span>
                        </a>
                        <nav class="flex items-center gap-2 text-sm flex-wrap justify-end">
                            <a href="{{ route('layanan.index') }}" class="px-3 py-2 rounded-xl font-medium text-slate-700 hover:bg-slate-100 transition {{ request()->routeIs('layanan.*') ? 'bg-brand-50 text-brand-800' : '' }}">
                                Cari muthowif
                            </a>
                            @if ($contactLink)
                                <a href="{{ $contactLink }}" target="_blank" rel="noopener noreferrer" class="px-3 py-2 rounded-xl font-medium text-brand-700 hover:bg-slate-100 transition">
                                    Contact us
                                </a>
                            @endif
                            @if (Route::has('login'))
                                <a href="{{ route('login') }}" class="px-3 py-2 rounded-xl font-medium text-slate-700 hover:bg-slate-100 transition">Masuk</a>
                            @endif
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="px-4 py-2 rounded-xl font-semibold bg-brand-600 text-white shadow-sm hover:bg-brand-700 transition">Daftar</a>
                            @endif
                        </nav>
                    </div>
                </header>
            @endauth

            <main class="flex-1 w-full max-w-6xl mx-auto px-4 sm:px-6 py-8 sm:py-10">
                {{ $slot }}
            </main>

            <footer class="border-t border-slate-200/80 bg-white/60 mt-auto">
                <div class="max-w-6xl mx-auto px-4 sm:px-6 py-6 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between text-xs text-slate-500">
                    <span>&copy; {{ date('Y') }} {{ config('app.name') }}</span>
                    @if ($contactLink)
                        <a href="{{ $contactLink }}" target="_blank" rel="noopener noreferrer" class="font-medium text-brand-700 hover:text-brand-800">
                            Contact us: {{ $contactRaw }}
                        </a>
                    @endif
                </div>
            </footer>
        </div>
    </body>
</html>
