@props([
    'variant' => 'segment', // segment | segment-dark | compact | welcome-menu
])

@php
    $current = app()->getLocale();
@endphp

<div {{ $attributes->merge(['class' => $variant === 'compact' ? 'inline-flex' : 'inline-flex items-stretch']) }} role="group" aria-label="{{ __('nav.language') }}">
    @if ($variant === 'segment-dark')
        <div class="inline-flex flex-wrap justify-end gap-1 rounded-xl border border-white/20 bg-white/10 p-1 shadow-inner backdrop-blur-sm">
            <a
                href="{{ route('locale.switch', ['locale' => 'id']) }}"
                @class([
                    'rounded-lg px-2.5 py-1.5 text-xs font-bold tracking-wide transition-all duration-200 min-w-[2.5rem] text-center',
                    'bg-white text-brand-900 shadow-md ring-1 ring-white/30' => $current === 'id',
                    'text-white/85 hover:bg-white/15 hover:text-white' => $current !== 'id',
                ])
                aria-current="{{ $current === 'id' ? 'true' : 'false' }}"
                title="{{ __('nav.lang_id') }}"
            >ID</a>
            <a
                href="{{ route('locale.switch', ['locale' => 'en']) }}"
                @class([
                    'rounded-lg px-2.5 py-1.5 text-xs font-bold tracking-wide transition-all duration-200 min-w-[2.5rem] text-center',
                    'bg-white text-brand-900 shadow-md ring-1 ring-white/30' => $current === 'en',
                    'text-white/85 hover:bg-white/15 hover:text-white' => $current !== 'en',
                ])
                aria-current="{{ $current === 'en' ? 'true' : 'false' }}"
                title="{{ __('nav.lang_en') }}"
            >EN</a>
            <a
                href="{{ route('locale.switch', ['locale' => 'ar']) }}"
                @class([
                    'rounded-lg px-2.5 py-1.5 text-xs font-bold tracking-wide transition-all duration-200 min-w-[2.5rem] text-center',
                    'bg-white text-brand-900 shadow-md ring-1 ring-white/30' => $current === 'ar',
                    'text-white/85 hover:bg-white/15 hover:text-white' => $current !== 'ar',
                ])
                aria-current="{{ $current === 'ar' ? 'true' : 'false' }}"
                title="{{ __('nav.lang_ar') }}"
            >AR</a>
        </div>
    @elseif ($variant === 'welcome-menu')
        <details class="relative">
            <summary class="flex cursor-pointer list-none items-center gap-2 rounded-xl border border-slate-200/90 bg-white px-3 py-2 text-sm font-semibold text-slate-800 shadow-sm transition hover:border-slate-300 [&::-webkit-details-marker]:hidden">
                <span>{{ strtoupper($current) }}</span>
                <svg class="h-4 w-4 shrink-0 text-slate-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                </svg>
            </summary>
            <div class="absolute right-0 z-50 mt-1.5 min-w-[11rem] overflow-hidden rounded-xl border border-slate-100 bg-white py-1 shadow-lg shadow-slate-900/10">
                <a
                    href="{{ route('locale.switch', ['locale' => 'id']) }}"
                    @class([
                        'block px-4 py-2 text-sm font-medium transition-colors',
                        'bg-baytgo/8 text-baytgo' => $current === 'id',
                        'text-slate-700 hover:bg-slate-50' => $current !== 'id',
                    ]) aria-current="{{ $current === 'id' ? 'true' : 'false' }}" title="{{ __('nav.lang_id') }}">{{ __('nav.lang_id') }}</a>
                <a
                    href="{{ route('locale.switch', ['locale' => 'en']) }}"
                    @class([
                        'block px-4 py-2 text-sm font-medium transition-colors',
                        'bg-baytgo/8 text-baytgo' => $current === 'en',
                        'text-slate-700 hover:bg-slate-50' => $current !== 'en',
                    ]) aria-current="{{ $current === 'en' ? 'true' : 'false' }}" title="{{ __('nav.lang_en') }}">{{ __('nav.lang_en') }}</a>
                <a
                    href="{{ route('locale.switch', ['locale' => 'ar']) }}"
                    @class([
                        'block px-4 py-2 text-sm font-medium transition-colors',
                        'bg-baytgo/8 text-baytgo' => $current === 'ar',
                        'text-slate-700 hover:bg-slate-50' => $current !== 'ar',
                    ]) aria-current="{{ $current === 'ar' ? 'true' : 'false' }}" title="{{ __('nav.lang_ar') }}">{{ __('nav.lang_ar') }}</a>
            </div>
        </details>
    @elseif ($variant === 'segment')
        <div class="inline-flex flex-wrap gap-1 rounded-xl border border-slate-200 bg-slate-100/90 p-1 shadow-inner">
            <a
                href="{{ route('locale.switch', ['locale' => 'id']) }}"
                @class([
                    'rounded-lg px-2.5 py-1.5 text-xs font-bold tracking-wide transition-all duration-200 min-w-[2.5rem] text-center',
                    'bg-brand-600 text-white shadow-md shadow-brand-600/30 ring-1 ring-brand-700/20' => $current === 'id',
                    'text-slate-600 hover:bg-white/80 hover:text-slate-900' => $current !== 'id',
                ])
                aria-current="{{ $current === 'id' ? 'true' : 'false' }}"
                title="{{ __('nav.lang_id') }}"
            >ID</a>
            <a
                href="{{ route('locale.switch', ['locale' => 'en']) }}"
                @class([
                    'rounded-lg px-2.5 py-1.5 text-xs font-bold tracking-wide transition-all duration-200 min-w-[2.5rem] text-center',
                    'bg-brand-600 text-white shadow-md shadow-brand-600/30 ring-1 ring-brand-700/20' => $current === 'en',
                    'text-slate-600 hover:bg-white/80 hover:text-slate-900' => $current !== 'en',
                ])
                aria-current="{{ $current === 'en' ? 'true' : 'false' }}"
                title="{{ __('nav.lang_en') }}"
            >EN</a>
            <a
                href="{{ route('locale.switch', ['locale' => 'ar']) }}"
                @class([
                    'rounded-lg px-2.5 py-1.5 text-xs font-bold tracking-wide transition-all duration-200 min-w-[2.5rem] text-center',
                    'bg-brand-600 text-white shadow-md shadow-brand-600/30 ring-1 ring-brand-700/20' => $current === 'ar',
                    'text-slate-600 hover:bg-white/80 hover:text-slate-900' => $current !== 'ar',
                ])
                aria-current="{{ $current === 'ar' ? 'true' : 'false' }}"
                title="{{ __('nav.lang_ar') }}"
            >AR</a>
        </div>
    @else
        <div class="flex flex-wrap gap-1.5">
            <a
                href="{{ route('locale.switch', ['locale' => 'id']) }}"
                @class([
                    'rounded-lg px-2.5 py-1 text-[11px] font-bold transition',
                    'bg-brand-600 text-white shadow' => $current === 'id',
                    'bg-slate-200/80 text-slate-700' => $current !== 'id',
                ])
            >ID</a>
            <a
                href="{{ route('locale.switch', ['locale' => 'en']) }}"
                @class([
                    'rounded-lg px-2.5 py-1 text-[11px] font-bold transition',
                    'bg-brand-600 text-white shadow' => $current === 'en',
                    'bg-slate-200/80 text-slate-700' => $current !== 'en',
                ])
            >EN</a>
            <a
                href="{{ route('locale.switch', ['locale' => 'ar']) }}"
                @class([
                    'rounded-lg px-2.5 py-1 text-[11px] font-bold transition',
                    'bg-brand-600 text-white shadow' => $current === 'ar',
                    'bg-slate-200/80 text-slate-700' => $current !== 'ar',
                ])
            >AR</a>
        </div>
    @endif
</div>
