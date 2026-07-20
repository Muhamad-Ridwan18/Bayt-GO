@props([
    'label',
    'removePayload' => null,
])

<div {{ $attributes->class(['mb-2 flex items-center gap-2 rounded-lg border border-emerald-200 bg-emerald-50 p-2 text-xs text-emerald-800']) }}>
    <span class="min-w-0 flex-1 font-medium">✓ {{ $label }}</span>
    @if ($removePayload)
        <button
            type="button"
            @click="removeCachedFile(@js($removePayload))"
            class="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-lg border border-transparent text-slate-500 transition hover:border-red-200 hover:bg-red-50 hover:text-red-600"
            title="{{ __('guest.register.remove_file') }}"
            aria-label="{{ __('guest.register.remove_file') }}"
        >✕</button>
    @endif
</div>
