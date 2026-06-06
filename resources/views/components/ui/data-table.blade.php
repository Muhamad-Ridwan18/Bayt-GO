@props([
    'empty' => null,
])

<div {{ $attributes->merge(['class' => 'overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-sm ring-1 ring-slate-100/80']) }}>
    @if (isset($toolbar))
        <div class="border-b border-slate-100 bg-slate-50/80">
            {{ $toolbar }}
        </div>
    @endif

    @if ($empty)
        <p class="p-10 text-center text-sm text-slate-500">{{ $empty }}</p>
    @else
        <div class="overflow-x-auto">
            {{ $slot }}
        </div>
    @endif
</div>
