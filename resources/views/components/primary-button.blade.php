@props(['processing' => null])

<button {{ $attributes->merge([
    'type' => 'submit',
    'data-submit-lock-label' => $processing ?? __('common.processing'),
    'class' => 'inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-brand-600 border border-transparent rounded-xl text-sm font-semibold text-white shadow-sm hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 transition ease-in-out duration-150 disabled:cursor-wait disabled:opacity-70',
]) }}>
    {{ $slot }}
</button>
