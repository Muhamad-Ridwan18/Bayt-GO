@props([
    'type' => 'neutral',
])

@php
    $class = match ($type) {
        'ready' => 'bg-emerald-50 text-emerald-800 ring-emerald-200/80',
        'guest' => 'bg-amber-50 text-amber-900 ring-amber-200/80',
        'blocked' => 'bg-red-50 text-red-800 ring-red-200/80',
        default => 'bg-slate-100 text-slate-700 ring-slate-200/80',
    };
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 {$class}"]) }}>
    {{ $slot }}
</span>
