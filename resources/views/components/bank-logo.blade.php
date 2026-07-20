@props([
    'code' => null,
    'size' => 'md',
])

@php
    use App\Support\AffiliateBankOptions;

    $code = is_string($code) ? $code : '';
    $url = $code !== '' ? AffiliateBankOptions::logoUrl($code) : null;
    $label = $code !== '' ? AffiliateBankOptions::label($code) : 'Bank';
    $sizeClass = match ($size) {
        'xs' => 'h-6 w-6',
        'sm' => 'h-8 w-8',
        'lg' => 'h-12 w-12',
        default => 'h-10 w-10',
    };
    $initial = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $code) ?: 'B', 0, 2));
@endphp

@if ($url)
    <span {{ $attributes->class([$sizeClass, 'inline-flex shrink-0 items-center justify-center overflow-hidden rounded-xl bg-white ring-1 ring-slate-200/80']) }}>
        <img src="{{ $url }}" alt="{{ $label }}" class="h-full w-full object-contain p-1" loading="lazy" />
    </span>
@else
    <span {{ $attributes->class([$sizeClass, 'inline-flex shrink-0 items-center justify-center rounded-xl bg-slate-100 text-[10px] font-bold text-slate-600 ring-1 ring-slate-200']) }} title="{{ $label }}">
        {{ $initial }}
    </span>
@endif
