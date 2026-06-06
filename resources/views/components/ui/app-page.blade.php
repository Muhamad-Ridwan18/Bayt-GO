@props([
    'compact' => false,
])

<div {{ $attributes->merge(['class' => 'ui-app-page '.($compact ? 'ui-page-y-compact' : 'ui-page-y')]) }}>
    {{ $slot }}
</div>
