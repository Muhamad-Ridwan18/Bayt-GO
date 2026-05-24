@props(['messages', 'field' => null])

@if ($messages)
    <ul {{ $attributes->merge(['class' => 'text-sm text-red-600 space-y-1']) }}>
        @foreach ((array) $messages as $message)
            <li>{{ $message }}</li>
        @endforeach
    </ul>
@endif

@if ($field)
    <ul {{ $attributes->merge(['class' => 'text-sm text-red-600 space-y-1']) }} x-show="errors && errors.{{ $field }}" x-cloak>
        <template x-for="err in (errors.{{ $field }} || [])">
            <li x-text="err"></li>
        </template>
    </ul>
@endif

