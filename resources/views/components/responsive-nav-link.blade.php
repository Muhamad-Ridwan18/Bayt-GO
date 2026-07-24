@props(['active'])

@php
$classes = ($active ?? false)
            ? 'block w-full rounded-xl px-3 py-2.5 text-start text-base font-medium text-baytgo bg-baytgo/8 focus:outline-none focus:bg-baytgo/12 transition duration-150 ease-in-out'
            : 'block w-full rounded-xl px-3 py-2.5 text-start text-base font-medium text-slate-600 hover:text-slate-800 hover:bg-slate-50 focus:outline-none focus:text-slate-800 focus:bg-slate-50 transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
