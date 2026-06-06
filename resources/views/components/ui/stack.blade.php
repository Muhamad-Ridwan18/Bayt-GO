@props([
    'compact' => false,
])

<div {{ $attributes->merge(['class' => $compact ? 'ui-stack-compact' : 'ui-stack']) }}>
    {{ $slot }}
</div>
