@props([
    'tag' => 'div',
    'wide' => false,
])

<{{ $tag }} {{ $attributes->merge(['class' => 'mx-auto w-full px-4 sm:px-6 lg:px-8 xl:px-10']) }}>
    {{ $slot }}
</{{ $tag }}>
