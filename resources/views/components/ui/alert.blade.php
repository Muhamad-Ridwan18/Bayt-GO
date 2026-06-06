@props([
    'type' => 'info',
])

@php
    $class = match ($type) {
        'success' => 'ui-alert-success',
        'error' => 'ui-alert-error',
        'warning' => 'ui-alert-warning',
        default => 'ui-alert-info',
    };
@endphp

<div {{ $attributes->merge(['class' => $class, 'role' => 'alert']) }}>
    {{ $slot }}
</div>
