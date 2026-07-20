@props([
    'label' => null,
    'name' => null,
    'error' => null,
    'hint' => null,
    'for' => null,
])

@php
    $inputId = $for ?? $name;
    $messages = $error ?? ($name ? $errors->get($name) : null);
@endphp

<div {{ $attributes->class(['space-y-2']) }}>
    @if (filled($label))
        <x-input-label :for="$inputId" :value="$label" />
    @endif

    {{ $slot }}

    @if (filled($hint))
        <p class="text-xs text-slate-500">{{ $hint }}</p>
    @endif

    <x-input-error :messages="$messages" />
</div>
