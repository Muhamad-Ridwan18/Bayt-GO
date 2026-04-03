@props([
    'name' => 'supporting_documents',
    'label' => '',
    'itemLabel' => 'Dokumen',
    'accept' => 'application/pdf,image/jpeg,image/png,image/webp',
    'addLabel' => 'Tambah dokumen',
    'hint' => '',
])

<div
    class="space-y-3"
    x-data="{ fileSlots: [{}] }"
>
    @if ($label)
        <p class="text-sm font-semibold text-slate-900">{{ $label }}</p>
    @endif

    <template x-for="(slot, index) in fileSlots" :key="index">
        <div class="space-y-1">
            <label class="block text-sm font-medium text-slate-700">
                <span x-text="'{{ $itemLabel }} ' + (index + 1) + ' :'"></span>
            </label>
            <div class="flex gap-2 items-start">
                <input
                    type="file"
                    class="block w-full text-sm text-slate-600 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-brand-50 file:text-brand-700 hover:file:bg-brand-100"
                    name="{{ $name }}[]"
                    accept="{{ $accept }}"
                />
                <button
                    type="button"
                    class="shrink-0 mt-1 p-2 text-slate-400 hover:text-red-600 rounded-lg border border-transparent hover:border-red-200 hover:bg-red-50 text-sm"
                    title="Hapus"
                    x-show="fileSlots.length > 1"
                    x-on:click="fileSlots.splice(index, 1)"
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
            x-on:click="fileSlots.push({})"
        >
            <span class="text-base leading-none">+</span>
            {{ $addLabel }}
        </button>
    </div>

    @if ($hint)
        <p class="text-xs text-slate-500">{{ $hint }}</p>
    @endif

    <x-input-error :messages="$errors->get($name)" class="mt-1" />
    @foreach ($errors->messages() as $key => $messages)
        @if (str_starts_with($key, $name.'.'))
            @foreach ($messages as $message)
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @endforeach
        @endif
    @endforeach
</div>
