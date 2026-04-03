@props(['title' => null])

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
                        @auth
                            <a href="{{ route('dashboard') }}" class="px-3 py-2 rounded-xl font-medium text-slate-700 hover:bg-slate-100 transition">Beranda</a>
                            @if (Auth::user()->isCustomer())
                                <a href="{{ route('bookings.index') }}" class="px-3 py-2 rounded-xl font-medium text-slate-700 hover:bg-slate-100 transition {{ request()->routeIs('bookings.*') ? 'bg-brand-50 text-brand-800' : '' }}">Booking saya</a>
                            @endif
                        @else
                            @if (Route::has('login'))
                                <a href="{{ route('login') }}" class="px-3 py-2 rounded-xl font-medium text-slate-700 hover:bg-slate-100 transition">Masuk</a>
                            @endif
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="px-4 py-2 rounded-xl font-semibold bg-brand-600 text-white shadow-sm hover:bg-brand-700 transition">Daftar</a>
                            @endif
                        @endauth
                    </nav>
                </div>
            </header>

            <main class="flex-1 w-full max-w-6xl mx-auto px-4 sm:px-6 py-8 sm:py-10">
                {{ $slot }}
            </main>

            <footer class="border-t border-slate-200/80 bg-white/60 mt-auto">
                <div class="max-w-6xl mx-auto px-4 sm:px-6 py-6 text-center text-xs text-slate-500">
                    &copy; {{ date('Y') }} {{ config('app.name') }}
                </div>
            </footer>
        </div>
    </body>
</html>
