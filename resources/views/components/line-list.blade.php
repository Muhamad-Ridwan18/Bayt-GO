@props([
    'label' => null,
    'items' => [],
])

@php
    $list = is_array($items) ? array_values(array_filter($items, fn ($v) => is_string($v) && trim($v) !== '')) : [];
@endphp

<div {{ $attributes->merge(['class' => '']) }}>
    @if ($label)
        <p class="text-sm font-medium text-slate-900">{{ $label }}</p>
    @endif
    @if (count($list) === 0)
        <p class="text-sm text-slate-500">—</p>
    @else
        <ul class="mt-1 list-disc list-inside space-y-0.5 text-sm text-slate-700">
            @foreach ($list as $line)
                <li>{{ $line }}</li>
            @endforeach
        </ul>
    @endif
</div>
