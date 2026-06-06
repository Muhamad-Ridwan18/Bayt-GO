@props([
    'market' => false,
    'pad' => 'none',
])

@php
    $padClass = match ($pad) {
        'md' => 'ui-card-pad',
        'lg' => 'ui-card-pad-lg',
        'compact' => 'ui-card-pad-compact',
        default => '',
    };
    $baseClass = $market ? 'ui-card-market' : 'ui-card';
@endphp

<div {{ $attributes->merge(['class' => trim("{$baseClass} {$padClass}")]) }}>
    {{ $slot }}
</div>
