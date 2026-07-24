@props([
    'active' => null,
])
@php
    $homeUrl = url('/');
    $linkBase = 'rounded-lg px-1.5 xl:px-3 py-2 transition text-xs xl:text-sm font-semibold whitespace-nowrap';
    $inactive = 'text-slate-600 hover:text-baytgo';
    $activeClass = 'relative text-baytgo after:absolute after:inset-x-1.5 xl:after:inset-x-3 after:-bottom-0.5 after:h-0.5 after:rounded-full after:bg-gold';
    $mobileLink = 'block rounded-xl px-3 py-3 text-base font-semibold text-slate-800 transition hover:bg-slate-50';
    $mobileLinkActive = 'block rounded-xl bg-baytgo/8 px-3 py-3 text-base font-semibold text-baytgo';
@endphp
<header
    class="sticky top-0 z-[100] border-b border-slate-100 bg-white shadow-sm"
    x-data="{
        open: false,
        init() {
            this.$watch('open', (value) => {
                document.body.classList.toggle('overflow-hidden', value && window.innerWidth < 1024);
            });
        },
    }"
    @keydown.window.escape="open = false"
    @resize.window="if (window.innerWidth >= 1024) { open = false }"
>
    <x-page-container class="relative flex min-h-[4.25rem] items-center justify-between gap-3 lg:gap-6">
        <a href="{{ route('welcome') }}" class="relative z-10 flex shrink-0 items-center gap-2.5 group">
            <x-site-logo variant="welcome" class="rounded-xl ring-1 ring-slate-200/70 shrink-0" />
            <span class="shrink-0 whitespace-nowrap text-lg font-bold tracking-tight text-baytgo">Bayt<span class="text-gold-muted">Go</span></span>
        </a>

        <nav class="hidden lg:flex absolute left-1/2 top-1/2 max-w-none -translate-x-1/2 -translate-y-1/2 items-center gap-0 xl:gap-0.5" aria-label="{{ __('welcome.nav_primary_aria') }}">
            <a href="{{ route('welcome') }}" class="{{ $linkBase }} {{ $active === 'welcome' ? $activeClass : $inactive }}">{{ __('welcome.nav_home') }}</a>
            <a href="{{ route('articles.index') }}" class="{{ $linkBase }} {{ $active === 'articles' ? $activeClass : $inactive }}">{{ __('nav.articles') }}</a>
            <a href="{{ $homeUrl }}#cara-kerja" class="{{ $linkBase }} {{ $inactive }}">{{ __('welcome.nav_how') }}</a>
            <a href="{{ $homeUrl }}#faq" class="{{ $linkBase }} {{ $inactive }}">{{ __('welcome.nav_faq') }}</a>
            <a href="{{ $homeUrl }}#tentang" class="{{ $linkBase }} {{ $inactive }}">{{ __('welcome.nav_about') }}</a>
        </nav>

        <div class="relative z-10 flex shrink-0 items-center gap-2">
            <div class="hidden items-center gap-2 sm:gap-3 lg:flex">
                <x-language-switcher variant="segment" />
                @auth
                    <a href="{{ route('dashboard') }}" class="inline-flex rounded-xl border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-700 transition hover:border-baytgo hover:text-baytgo">{{ __('welcome.cta_dashboard') }}</a>
                @else
                    @if (Route::has('login'))
                        <a href="{{ route('login') }}" class="inline-flex rounded-xl bg-baytgo px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-baytgo-800">{{ __('welcome.nav_login_register') }}</a>
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

    <template x-teleport="body">
        <div
            id="marketing-mobile-nav"
            x-show="open"
            x-cloak
            class="fixed inset-0 z-[300] flex h-[100dvh] flex-col bg-white lg:hidden"
            role="dialog"
            aria-modal="true"
            :aria-hidden="(! open).toString()"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-y-2"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 translate-y-2"
        >
            <div class="flex min-h-[4.25rem] shrink-0 items-center justify-between gap-3 border-b border-slate-100 px-4">
                <a href="{{ route('welcome') }}" @click="open = false" class="flex shrink-0 items-center gap-2.5">
                    <x-site-logo variant="welcome" class="rounded-xl ring-1 ring-slate-200/70 shrink-0" />
                    <span class="shrink-0 whitespace-nowrap text-lg font-bold tracking-tight text-baytgo">Bayt<span class="text-gold-muted">Go</span></span>
                </a>
                <button
                    type="button"
                    class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-slate-200 text-slate-600 transition hover:bg-slate-50"
                    @click="open = false"
                >
                    <span class="sr-only">{{ __('welcome.landing_gallery_close') }}</span>
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="min-h-0 flex-1 overflow-y-auto px-4 py-4">
                <nav class="space-y-1" aria-label="{{ __('welcome.nav_mobile_aria') }}">
                    <a href="{{ route('welcome') }}" @click="open = false" class="{{ $active === 'welcome' ? $mobileLinkActive : $mobileLink }}">{{ __('welcome.nav_home') }}</a>
                    <a href="{{ route('articles.index') }}" @click="open = false" class="{{ $active === 'articles' ? $mobileLinkActive : $mobileLink }}">{{ __('nav.articles') }}</a>
                    <a href="{{ $homeUrl }}#cara-kerja" @click="open = false" class="{{ $mobileLink }}">{{ __('welcome.nav_how') }}</a>
                    <a href="{{ $homeUrl }}#faq" @click="open = false" class="{{ $mobileLink }}">{{ __('welcome.nav_faq') }}</a>
                    <a href="{{ $homeUrl }}#tentang" @click="open = false" class="{{ $mobileLink }}">{{ __('welcome.nav_about') }}</a>
                </nav>

                <div class="mt-6 border-t border-slate-100 pt-5">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">{{ __('nav.language') }}</p>
                    <div class="mt-3">
                        <x-language-switcher variant="segment" />
                    </div>
                </div>
            </div>

            <div class="shrink-0 border-t border-slate-100 bg-white p-4 pb-[max(1rem,env(safe-area-inset-bottom))]">
                @auth
                    <a href="{{ route('dashboard') }}" @click="open = false" class="inline-flex w-full items-center justify-center rounded-xl bg-baytgo px-4 py-3 text-sm font-semibold text-white transition hover:bg-baytgo-800">{{ __('welcome.cta_dashboard') }}</a>
                @elseif (Route::has('login'))
                    <a href="{{ route('login') }}" @click="open = false" class="inline-flex w-full items-center justify-center rounded-xl bg-baytgo px-4 py-3 text-sm font-semibold text-white transition hover:bg-baytgo-800">{{ __('welcome.nav_login_register') }}</a>
                @endif
            </div>
        </div>
    </template>
</header>
