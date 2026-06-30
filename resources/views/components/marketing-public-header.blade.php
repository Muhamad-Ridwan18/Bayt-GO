@props([
    'active' => null,
])
@php
    $contactRaw = (string) (config('app.contact_whatsapp') ?: config('app.contact_phone'));
    $contactDigits = preg_replace('/\D+/', '', $contactRaw ?? '') ?? '';
    $contactLink = $contactDigits !== '' ? 'https://wa.me/'.$contactDigits : null;
    $homeUrl = url('/');
    $linkBase = 'rounded-lg px-1.5 xl:px-3 py-2 transition text-xs xl:text-sm font-semibold whitespace-nowrap';
    $inactive = 'text-slate-600 hover:text-baytgo';
    $activeClass = 'relative text-baytgo after:absolute after:inset-x-1.5 xl:after:inset-x-3 after:-bottom-0.5 after:h-0.5 after:rounded-full after:bg-gold';
@endphp
<header class="sticky top-0 z-[100] border-b border-slate-100 bg-white shadow-sm" x-data="{ open: false }" @keydown.window.escape="open = false" @resize.window="if (window.innerWidth >= 1024) open = false">
    <x-page-container class="relative flex min-h-[4.25rem] items-center justify-between gap-3 lg:gap-6">
        <a href="{{ route('welcome') }}" class="relative z-10 flex min-w-0 shrink-0 items-center gap-2.5 group">
            <x-site-logo variant="welcome" class="rounded-xl ring-1 ring-slate-200/70 shrink-0" />
            <span class="truncate text-lg font-bold tracking-tight text-baytgo">Bayt<span class="text-gold-muted">Go</span></span>
        </a>

        <nav class="hidden lg:flex absolute left-1/2 top-1/2 max-w-none -translate-x-1/2 -translate-y-1/2 items-center gap-0 xl:gap-0.5" aria-label="{{ __('welcome.nav_primary_aria') }}">
            <a href="{{ route('welcome') }}" class="{{ $linkBase }} {{ $active === 'welcome' ? $activeClass : $inactive }}">{{ __('welcome.nav_home') }}</a>
            <a href="{{ $homeUrl }}#cara-kerja" class="{{ $linkBase }} {{ $inactive }}">{{ __('welcome.nav_how') }}</a>
            <a href="{{ route('layanan.index') }}" class="{{ $linkBase }} {{ $active === 'layanan' ? $activeClass : $inactive }}">{{ __('welcome.nav_muthowif') }}</a>
            {{-- <a href="{{ route('layanan-pendukung.index') }}" class="{{ $linkBase }} {{ $active === 'layanan_pendukung' ? $activeClass : $inactive }}">{{ __('layanan_pendukung.page_title') }}</a> --}}
            <a href="{{ route('articles.index') }}" class="{{ $linkBase }} {{ $active === 'articles' ? $activeClass : $inactive }}">{{ __('nav.articles') }}</a>
            <a href="{{ $homeUrl }}#harga" class="{{ $linkBase }} {{ $inactive }}">{{ __('welcome.nav_pricing') }}</a>
            <a href="{{ $homeUrl }}#tentang" class="{{ $linkBase }} {{ $inactive }}">{{ __('welcome.nav_about') }}</a>
            <a href="{{ $homeUrl }}#faq" class="{{ $linkBase }} {{ $inactive }}">{{ __('welcome.nav_faq') }}</a>
        </nav>

        <div class="relative z-10 flex shrink-0 items-center gap-2">
            <div class="hidden items-center gap-2 sm:gap-3 lg:flex">
                <x-language-switcher variant="segment" />
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
                aria-controls="marketing-mobile-nav"
            >
                <span class="sr-only">{{ __('nav.open_menu') }}</span>
                <svg class="h-6 w-6 shrink-0" stroke="currentColor" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                    <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </x-page-container>

    <div
        id="marketing-mobile-nav"
        :class="{'block': open, 'hidden': ! open}"
        class="hidden border-t border-slate-100 bg-white lg:hidden"
    >
        <x-page-container class="space-y-0.5 py-4" tag="nav" aria-label="{{ __('welcome.nav_mobile_aria') }}">
            <a href="{{ route('welcome') }}" @click="open = false" class="block rounded-lg px-3 py-2.5 text-sm font-semibold {{ $active === 'welcome' ? 'bg-baytgo/8 text-baytgo' : 'text-slate-700 hover:bg-slate-50' }}">{{ __('welcome.nav_home') }}</a>
            <a href="{{ $homeUrl }}#cara-kerja" @click="open = false" class="block rounded-lg px-3 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">{{ __('welcome.nav_how') }}</a>
            <a href="{{ route('layanan.index') }}" @click="open = false" class="block rounded-lg px-3 py-2.5 text-sm font-semibold {{ $active === 'layanan' ? 'bg-baytgo/8 text-baytgo' : 'text-slate-700 hover:bg-slate-50' }}">{{ __('welcome.nav_muthowif') }}</a>
            <a href="{{ route('articles.index') }}" @click="open = false" class="block rounded-lg px-3 py-2.5 text-sm font-semibold {{ $active === 'articles' ? 'bg-baytgo/8 text-baytgo' : 'text-slate-700 hover:bg-slate-50' }}">{{ __('nav.articles') }}</a>
            <a href="{{ $homeUrl }}#harga" @click="open = false" class="block rounded-lg px-3 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">{{ __('welcome.nav_pricing') }}</a>
            <a href="{{ $homeUrl }}#tentang" @click="open = false" class="block rounded-lg px-3 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">{{ __('welcome.nav_about') }}</a>
            <a href="{{ $homeUrl }}#faq" @click="open = false" class="block rounded-lg px-3 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">{{ __('welcome.nav_faq') }}</a>
        </x-page-container>
        <x-page-container class="border-t border-slate-100 py-4">
            <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">{{ __('nav.language') }}</p>
            <div class="mt-3 flex justify-start">
                <x-language-switcher variant="segment" />
            </div>
        </x-page-container>
        <x-page-container class="flex flex-wrap gap-2 border-t border-slate-100 py-4">
            @auth
                <a href="{{ route('dashboard') }}" class="inline-flex flex-1 min-w-[8rem] items-center justify-center rounded-xl border border-slate-200 px-3 py-2.5 text-sm font-semibold text-slate-700 transition hover:border-baytgo hover:text-baytgo">{{ __('nav.home') }}</a>
            @else
                @if (Route::has('login'))
                    <a href="{{ route('login') }}" class="inline-flex flex-1 min-w-[8rem] items-center justify-center rounded-xl border border-slate-200 px-3 py-2.5 text-sm font-semibold text-slate-700 transition hover:text-baytgo">{{ __('layanan.guest_header_login') }}</a>
                @endif
            @endauth
        </x-page-container>
    </div>
</header>
