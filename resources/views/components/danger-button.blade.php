@props(['processing' => null])

<button {{ $attributes->merge([
    'type' => 'submit',
    'data-submit-lock-label' => $processing ?? __('common.processing'),
    'class' => 'ui-btn-danger',
]) }}>
    {{ $slot }}
</button>
