@props([
    'label' => null,
])

<div {{ $attributes->class(['relative my-8']) }}>
    <div class="absolute inset-0 flex items-center" aria-hidden="true">
        <div class="w-full border-t border-slate-200"></div>
    </div>
    @if (filled($label))
        <div class="relative flex justify-center text-sm">
            <span class="bg-white px-3 text-slate-400">{{ $label }}</span>
        </div>
    @endif
</div>
