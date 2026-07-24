@props([
    'tone' => 'success',
])

@php
    $wrap = match ($tone) {
        'info' => 'border-sky-100 bg-sky-50 text-sky-900',
        'warning' => 'border-amber-200 bg-amber-50 text-amber-900',
        'error' => 'border-red-200 bg-red-50 text-red-800',
        default => 'border-emerald-200 bg-emerald-50 text-emerald-800',
    };
    $icon = match ($tone) {
        'info' => 'M11.25 11.25l.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z',
        'warning' => 'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z',
        'error' => 'M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z',
        default => 'M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
    };
    $iconColor = match ($tone) {
        'info' => 'text-sky-600',
        'warning' => 'text-amber-600',
        'error' => 'text-red-600',
        default => 'text-emerald-600',
    };
@endphp

<div {{ $attributes->class(['mb-5 flex gap-3 rounded-2xl border px-4 py-3 text-sm', $wrap]) }}>
    <svg class="mt-0.5 h-5 w-5 shrink-0 {{ $iconColor }}" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}" />
    </svg>
    <div class="min-w-0">{{ $slot }}</div>
</div>
