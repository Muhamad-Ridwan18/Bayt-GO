@props([
    'processing' => null,
])

<button
    type="submit"
    data-submit-lock-label="{{ $processing ?? __('common.processing') }}"
    {{ $attributes->class([
        'inline-flex items-center justify-center gap-2',
        'disabled:cursor-wait disabled:opacity-70',
    ]) }}
>
    {{ $slot }}
</button>
