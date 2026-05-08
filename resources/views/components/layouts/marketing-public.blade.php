@props([
    'title',
    'metaDescription' => null,
    'activeNav' => null,
])
@php
    $rtl = app()->getLocale() === 'ar';
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ $rtl ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} — {{ config('app.name') }}</title>
    @if ($metaDescription)
        <meta name="description" content="{{ \Illuminate\Support\Str::limit(strip_tags($metaDescription), 155, '') }}">
    @endif
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&family=plus-jakarta-sans:400,500,600,700&display=swap" rel="stylesheet">
    @if ($rtl)
        <link href="https://fonts.bunny.net/css?family=noto-sans-arabic:400,500,600,700&display=swap" rel="stylesheet">
    @endif
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-welcome antialiased text-slate-800 bg-white min-h-screen selection:bg-gold-light selection:text-baytgo-950 {{ $rtl ? 'marketing-rtl' : '' }}">
<div class="min-h-screen flex flex-col">
    <x-marketing-public-header :active="$activeNav"/>

    <main class="flex-1">
        {{ $slot }}
    </main>

    <footer class="border-t border-slate-200 bg-welcomeCanvas.soft mt-auto">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-6">
            <div>
                <p class="text-sm font-semibold text-baytgo">{{ config('app.name') }}</p>
                <p class="mt-1 text-sm text-slate-600 max-w-md">{{ __('articles.footer_tagline') }}</p>
            </div>
            <div class="flex flex-col sm:items-end gap-3 text-sm">
                <div class="flex flex-wrap gap-x-4 gap-y-1 text-slate-600">
                    <a href="{{ route('welcome') }}" class="font-medium hover:text-baytgo">{{ __('nav.home') }}</a>
                    <a href="{{ route('articles.index') }}" class="font-medium hover:text-baytgo">{{ __('nav.articles') }}</a>
                    <a href="{{ route('layanan.index') }}" class="font-medium hover:text-baytgo">{{ __('welcome.nav_muthowif') }}</a>
                </div>
                <x-language-switcher variant="segment" />
                <p class="text-xs text-slate-500">&copy; {{ date('Y') }} {{ config('app.name') }}</p>
            </div>
        </div>
    </footer>
</div>
</body>
</html>
