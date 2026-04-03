@props([
    'name' => 'items',
    'label' => '',
    'itemLabel' => 'Item',
    'placeholder' => '',
    'addLabel' => 'Tambah',
    'items' => [''],
])

@php
    $initial = old($name);
    if (! is_array($initial) || count($initial) === 0) {
        $initial = $items;
    }
@endphp

{{-- Atribut pakai kutip tunggal agar JSON @json (berisi ") tidak memutus x-data --}}
<div
    class="space-y-3"
    x-data='{
        rows: @json(array_values($initial)),
        add() { this.rows.push(""); },
        remove(i) { if (this.rows.length > 1) this.rows.splice(i, 1); }
    }'
>
    @if ($label)
        <p class="text-sm font-semibold text-slate-900">{{ $label }}</p>
    @endif

    <template x-for="(val, index) in rows" :key="index">
        <div class="space-y-1">
            <label class="block text-sm font-medium text-slate-700">
                <span x-text="'{{ $itemLabel }} ' + (index + 1) + ' :'"></span>
            </label>
            <div class="flex gap-2 items-start">
                <input
                    type="text"
                    class="block w-full rounded-lg border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 text-sm"
                    x-bind:name="'{{ $name }}[' + index + ']'"
                    x-model="rows[index]"
                    placeholder="{{ $placeholder }}"
                />
                <button
                    type="button"
                    class="shrink-0 mt-1 p-2 text-slate-400 hover:text-red-600 rounded-lg border border-transparent hover:border-red-200 hover:bg-red-50 text-sm"
                    title="Hapus baris"
                    x-show="rows.length > 1"
                    x-on:click="remove(index)"
                >
                    ✕
                </button>
            </div>
        </div>
    </template>

    <div class="flex justify-end pt-1">
        <button
            type="button"
            class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100 transition"
            x-on:click="add()"
        >
            <span class="text-base leading-none">+</span>
            {{ $addLabel }}
        </button>
    </div>

    <x-input-error :messages="$errors->get($name)" class="mt-1" />
    @foreach ($errors->messages() as $key => $messages)
        @if (str_starts_with($key, $name.'.'))
            @foreach ($messages as $message)
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @endforeach
        @endif
    @endforeach
</div>
