@props([
    'kicker' => null,
    'title',
    'titleId' => null,
    'subtitle' => null,
    'href' => null,
    'linkLabel' => null,
    'align' => 'start',
])

@php
    $wrap = $align === 'center'
        ? 'mb-8 text-center'
        : 'mb-5 flex flex-col gap-2 sm:mb-6 sm:flex-row sm:flex-wrap sm:items-end sm:justify-between sm:gap-3';
@endphp

<div {{ $attributes->class([$wrap]) }}>
    <div class="{{ $align === 'center' ? '' : 'min-w-0 flex-1 pe-2' }}">
        @if (filled($kicker))
            <p class="text-[10px] font-bold uppercase tracking-[0.16em] text-baytgo/70">{{ $kicker }}</p>
        @endif
        <h2
            @if ($titleId) id="{{ $titleId }}" @endif
            class="{{ filled($kicker) ? 'mt-1' : '' }} text-lg font-bold text-baytgo sm:text-2xl"
        >{{ $title }}</h2>
        @if (filled($subtitle))
            <p class="mt-1 line-clamp-2 text-xs text-slate-600 sm:text-sm">{{ $subtitle }}</p>
        @endif
    </div>
    @if ($href && $linkLabel)
        <a href="{{ $href }}" class="inline-flex shrink-0 items-center gap-1 self-start text-sm font-semibold text-gold-muted hover:text-baytgo">
            {{ $linkLabel }}
            <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg>
        </a>
    @endif
</div>
