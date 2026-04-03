@props([
    'name',
    'value' => null,
    'id' => null,
    'required' => false,
    'placeholder' => '',
    'prefix' => false,
])

@php
    $fieldId = $id ?? $name;
    $initial = old($name, $value);
    $digits = ($initial !== null && $initial !== '')
        ? preg_replace('/\D+/', '', (string) $initial)
        : '';
@endphp

<div
    x-data="indonesianDigitsInput(@js($digits))"
    {{ $attributes->class([
        'mt-1 flex w-full rounded-xl border border-slate-300 shadow-sm overflow-hidden focus-within:ring-2 focus-within:ring-brand-500 focus-within:border-brand-500' => $prefix,
        'mt-1 w-full' => ! $prefix,
    ]) }}
>
    @if ($prefix)
        <span class="inline-flex items-center px-3 bg-slate-50 text-slate-600 text-sm border-e border-slate-200 shrink-0">Rp</span>
    @endif
    <input
        type="text"
        id="{{ $fieldId }}"
        x-ref="visible"
        @input="onInput"
        x-init="$el.value = formatDigits(raw)"
        inputmode="numeric"
        autocomplete="off"
        placeholder="{{ $placeholder }}"
        @if ($required) required @endif
        @class([
            'block w-full min-w-0 flex-1 border-0 py-2.5 px-3 text-sm text-slate-900 placeholder:text-slate-400 focus:ring-0' => $prefix,
            'block w-full rounded-lg border border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 text-sm' => ! $prefix,
        ])
    />
    <input type="hidden" name="{{ $name }}" :value="raw" />
</div>
