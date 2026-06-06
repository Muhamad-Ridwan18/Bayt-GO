@props([
    'tag' => 'div',
    'wide' => false,
])

<{{ $tag }} {{ $attributes->merge(['class' => 'mx-auto w-full px-4 sm:px-6 lg:px-8 '.($wide ? 'max-w-[88rem]' : 'max-w-7xl')]) }}>
    {{ $slot }}
</{{ $tag }}>
