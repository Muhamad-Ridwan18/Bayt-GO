<button {{ $attributes->merge(['type' => 'button', 'class' => 'ui-btn-secondary disabled:opacity-50']) }}>
    {{ $slot }}
</button>
