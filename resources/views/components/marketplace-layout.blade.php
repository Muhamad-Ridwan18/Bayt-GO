@props(['title' => null])
@php
    $contactRaw = (string) (config('app.contact_whatsapp') ?: config('app.contact_phone'));
    $contactDigits = preg_replace('/\D+/', '', $contactRaw ?? '') ?? '';
    $contactLink = $contactDigits !== '' ? 'https://wa.me/'.$contactDigits : null;
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="overflow-x-hidden">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ $title ? $title.' — '.config('app.name') : config('app.name') }}</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen overflow-x-hidden bg-gradient-to-b from-slate-50 via-white to-amber-50/40 font-sans antialiased text-slate-800 selection:bg-brand-200/60">
        <div class="flex min-h-screen min-w-0 flex-col">
            @auth
                @include('layouts.navigation')
            @else
                <header class="sticky top-0 z-20 w-full border-b border-white/40 bg-white/75 shadow-sm shadow-slate-900/5 backdrop-blur-xl">
                    <div class="mx-auto flex w-full min-w-0 max-w-6xl items-center justify-between gap-4 px-4 py-3.5 sm:px-6">
                        <a href="{{ url('/') }}" class="group flex items-center gap-3">
                            <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-brand-500 to-brand-700 text-sm font-bold text-white shadow-lg shadow-brand-600/25 ring-2 ring-white/50 transition group-hover:shadow-brand-600/40">BG</span>
                            <span class="text-lg font-bold tracking-tight text-slate-900">Bayt<span class="text-brand-600">Go</span></span>
                        </a>
                        <nav class="flex flex-wrap items-center justify-end gap-1.5 text-sm sm:gap-2">
                            <a href="{{ route('layanan.index') }}" class="rounded-xl px-3 py-2 font-medium text-slate-700 transition hover:bg-slate-100 {{ request()->routeIs('layanan.*') ? 'bg-brand-50 font-semibold text-brand-800 ring-1 ring-brand-200/70' : '' }}">
                                {{ __('layanan.find_muthowif') }}
                            </a>
                            @if ($contactLink)
                                <a href="{{ $contactLink }}" target="_blank" rel="noopener noreferrer" class="rounded-xl px-3 py-2 font-medium text-brand-700 transition hover:bg-brand-50/80">
                                    {{ __('marketplace.layout.contact_us') }}
                                </a>
                            @endif
                            @if (Route::has('login'))
                                <a href="{{ route('login') }}" class="rounded-xl px-3 py-2 font-medium text-slate-700 transition hover:bg-slate-100">{{ __('layanan.guest_header_login') }}</a>
                            @endif
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="rounded-xl bg-gradient-to-r from-brand-600 to-brand-700 px-4 py-2 font-semibold text-white shadow-md shadow-brand-600/20 transition hover:from-brand-500 hover:to-brand-600">{{ __('layanan.guest_header_register') }}</a>
                            @endif
                        </nav>
                    </div>
                </header>
            @endauth

            <main class="w-full min-w-0 flex-1 px-4 py-8 sm:px-6 sm:py-12">
                <div class="mx-auto w-full min-w-0 max-w-6xl">
                    {{ $slot }}
                </div>
            </main>

            <footer class="mt-auto w-full border-t border-slate-200/80 bg-gradient-to-b from-white to-slate-50/90">
                <div class="mx-auto flex w-full min-w-0 max-w-6xl flex-col gap-3 px-4 py-8 text-xs text-slate-500 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                    <span class="font-medium">&copy; {{ date('Y') }} {{ config('app.name') }}</span>
                    @if ($contactLink)
                        <a href="{{ $contactLink }}" target="_blank" rel="noopener noreferrer" class="font-semibold text-brand-700 transition hover:text-brand-800">
                            {{ __('marketplace.footer_contact', ['contact' => $contactRaw]) }}
                        </a>
                    @endif
                </div>
            </footer>
        </div>
    </body>
</html>
