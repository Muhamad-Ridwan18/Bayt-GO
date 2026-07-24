@props([
    'variant' => 'primary',
    'href' => null,
    'type' => 'submit',
])

@php
    $classes = match ($variant) {
        'secondary' => 'inline-flex w-full items-center justify-center rounded-xl bg-slate-100 px-4 py-3.5 text-sm font-medium text-slate-700 transition hover:bg-slate-200/80 focus:outline-none focus:ring-2 focus:ring-slate-300 focus:ring-offset-2',
        'ghost' => 'inline-flex items-center justify-center rounded-xl px-3 py-2 text-sm font-semibold text-baytgo transition hover:bg-emerald-50 focus:outline-none focus:ring-2 focus:ring-baytgo/20',
        'outline' => 'inline-flex w-full items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-3.5 text-sm font-semibold text-slate-800 transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-baytgo/20',
        'gold' => 'inline-flex shrink-0 items-center justify-center gap-2 rounded-xl bg-gold px-7 py-3.5 text-sm font-bold text-baytgo-950 shadow-lg transition hover:bg-gold-muted focus:outline-none focus:ring-2 focus:ring-gold/40',
        default => 'ui-btn-primary w-full justify-center rounded-xl bg-baytgo py-3.5 text-base font-semibold shadow-md shadow-baytgo/15 hover:bg-baytgo-800',
    };
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->class([$classes]) }}>
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->class([$classes]) }}>
        {{ $slot }}
    </button>
@endif
