@props([
    'variant' => 'segment', // segment | compact
])

@php
    $current = app()->getLocale();
@endphp

<div {{ $attributes->merge(['class' => $variant === 'compact' ? 'inline-flex' : 'inline-flex items-stretch']) }} role="group" aria-label="{{ __('nav.language') }}">
    @if ($variant === 'segment')
        <div class="inline-flex rounded-xl border border-slate-200 bg-slate-100/90 p-1 shadow-inner">
            <a
                href="{{ route('locale.switch', ['locale' => 'id']) }}"
                @class([
                    'rounded-lg px-3 py-1.5 text-xs font-bold tracking-wide transition-all duration-200 min-w-[2.75rem] text-center',
                    'bg-brand-600 text-white shadow-md shadow-brand-600/30 ring-1 ring-brand-700/20' => $current === 'id',
                    'text-slate-600 hover:bg-white/80 hover:text-slate-900' => $current !== 'id',
                ])
                aria-current="{{ $current === 'id' ? 'true' : 'false' }}"
                title="{{ __('nav.lang_id') }}"
            >ID</a>
            <a
                href="{{ route('locale.switch', ['locale' => 'en']) }}"
                @class([
                    'rounded-lg px-3 py-1.5 text-xs font-bold tracking-wide transition-all duration-200 min-w-[2.75rem] text-center',
                    'bg-brand-600 text-white shadow-md shadow-brand-600/30 ring-1 ring-brand-700/20' => $current === 'en',
                    'text-slate-600 hover:bg-white/80 hover:text-slate-900' => $current !== 'en',
                ])
                aria-current="{{ $current === 'en' ? 'true' : 'false' }}"
                title="{{ __('nav.lang_en') }}"
            >EN</a>
        </div>
    @else
        <div class="flex gap-1.5">
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
        </div>
    @endif
</div>
