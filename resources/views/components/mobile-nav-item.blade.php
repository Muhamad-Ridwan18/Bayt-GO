@props([
    'href',
    'active' => false,
    'danger' => false,
])

@php
    $base = 'flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-start text-[15px] font-medium transition duration-150 ease-in-out focus:outline-none';
    $classes = $danger
        ? $base.' text-red-600 hover:bg-red-50 focus:bg-red-50'
        : (($active ?? false)
            ? $base.' bg-slate-100 text-baytgo'
            : $base.' text-slate-700 hover:bg-slate-50 hover:text-baytgo focus:bg-slate-50');
@endphp

<a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
    @isset($icon)
        <span @class([
            'inline-flex h-5 w-5 shrink-0 items-center justify-center',
            'text-red-500' => $danger,
            'text-baytgo' => ! $danger && ($active ?? false),
            'text-slate-500' => ! $danger && ! ($active ?? false),
        ]) aria-hidden="true">
            {{ $icon }}
        </span>
    @endisset
    <span class="min-w-0 flex-1">{{ $slot }}</span>
    @isset($trailing)
        <span class="ms-auto shrink-0">{{ $trailing }}</span>
    @endisset
</a>
