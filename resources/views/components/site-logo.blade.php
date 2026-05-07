@props([
    'variant' => 'nav',
])

@php
    use App\Support\SiteBrand;

    $logoUrl = SiteBrand::logoPublicUrl();

    /** @var array<string, array{outer: string, img: string, fallback: string}> $styles */
    $styles = [
        'welcome' => [
            'outer' => 'flex h-11 w-11 shrink-0 items-center justify-center overflow-hidden rounded-2xl bg-transparent p-0',
            'img' => 'h-full w-full object-contain',
            'fallback' => 'flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-brand-400 to-brand-700 text-sm font-bold text-white shadow-lg shadow-brand-900/40',
        ],
        'nav' => [
            'outer' => 'flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-transparent p-0',
            'img' => 'h-full w-full object-contain',
            'fallback' => 'flex h-9 w-9 items-center justify-center rounded-lg bg-brand-600 text-xs font-bold text-white',
        ],
        'guest' => [
            'outer' => 'flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-transparent p-0',
            'img' => 'h-full w-full object-contain',
            'fallback' => 'flex h-9 w-9 items-center justify-center rounded-xl bg-brand-600 text-sm font-bold text-white',
        ],
        'marketplace' => [
            'outer' => 'flex h-11 w-11 shrink-0 items-center justify-center overflow-hidden rounded-2xl bg-transparent p-0 transition',
            'img' => 'h-full w-full object-contain',
            'fallback' => 'flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-brand-500 to-brand-700 text-sm font-bold text-white shadow-lg shadow-brand-600/25 transition group-hover:shadow-brand-600/40',
        ],
        'docs' => [
            'outer' => 'flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-2xl bg-transparent p-0',
            'img' => 'h-full w-full object-contain',
            'fallback' => 'flex h-10 w-10 items-center justify-center rounded-2xl bg-gradient-to-br from-brand-400 to-brand-700 text-xs font-bold text-white shadow-lg shadow-brand-900/40',
        ],
    ];

    $s = $styles[$variant] ?? $styles['nav'];
@endphp

@if ($logoUrl !== null)
    <span {{ $attributes->merge(['class' => $s['outer']]) }}>
        <img
            src="{{ $logoUrl }}"
            alt="{{ config('app.name') }}"
            class="{{ $s['img'] }}"
            decoding="async"
        >
    </span>
@else
    <span {{ $attributes->merge(['class' => $s['fallback']]) }}>BG</span>
@endif
