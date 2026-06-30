@php
    $rows = old('packages');
    if (is_array($rows)) {
        $rows = collect($rows)->values()->all();
    } elseif ($packages->isEmpty()) {
        $rows = [['id' => '', 'name' => '', 'category' => 'other', 'description' => '', 'price' => '', 'min_pilgrims' => '1', 'max_pilgrims' => '10', 'is_active' => true]];
    } else {
        $rows = $packages->map(fn ($p) => [
            'id' => (string) $p->id,
            'name' => $p->name,
            'category' => $p->category?->value ?? 'other',
            'description' => $p->description ?? '',
            'price' => (string) (int) $p->price,
            'min_pilgrims' => (string) $p->min_pilgrims,
            'max_pilgrims' => (string) $p->max_pilgrims,
            'is_active' => $p->is_active,
        ])->values()->all();
    }
    $categoryOptions = collect($categories)->map(fn ($c) => ['value' => $c->value, 'label' => $c->label()])->all();
@endphp

<x-app-layout>
    <x-ui.app-page>
        <x-page-container class="ui-stack-compact">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                <h1 class="text-xl font-bold text-slate-900">{{ __('layanan_pendukung.manage_title') }}</h1>
                <p class="mt-1 text-sm text-slate-600">{{ __('layanan_pendukung.manage_lead') }}</p>

                <form method="POST" action="{{ route('muthowif.pelayanan-pendukung.update') }}" class="mt-6 space-y-4" x-data="{
                    rows: @js($rows),
                    categoryOptions: @js($categoryOptions),
                    addRow() { this.rows.push({ id: '', name: '', category: 'other', description: '', price: '', min_pilgrims: '1', max_pilgrims: '10', is_active: true }); },
                    removeRow(i) { this.rows.splice(i, 1); },
                }">
                    @csrf
                    @method('PUT')

                    <template x-for="(row, index) in rows" :key="index">
                        <div class="rounded-xl border border-slate-200 p-4 space-y-3">
                            <input type="hidden" :name="'packages[' + index + '][id]'" x-model="row.id">
                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <div>
                                    <x-input-label :value="__('layanan_pendukung.category')" />
                                    <select class="mt-1 block w-full rounded-xl border-slate-300 text-sm" x-model="row.category" :name="'packages[' + index + '][category]'" required>
                                        <template x-for="opt in categoryOptions" :key="opt.value">
                                            <option :value="opt.value" x-text="opt.label"></option>
                                        </template>
                                    </select>
                                </div>
                                <div class="sm:col-span-2">
                                    <x-input-label :value="__('layanan_pendukung.package_name')" />
                                    <input type="text" class="mt-1 block w-full rounded-xl border-slate-300 text-sm" x-model="row.name" :name="'packages[' + index + '][name]'" required>
                                </div>
                                <div>
                                    <x-input-label :value="__('layanan_pendukung.package_price')" />
                                    <input type="text" inputmode="numeric" class="mt-1 block w-full rounded-xl border-slate-300 text-sm" x-model="row.price" :name="'packages[' + index + '][price]'" required>
                                </div>
                                <div class="flex items-end gap-3">
                                    <div class="flex-1">
                                        <x-input-label :value="__('layanan_pendukung.min_pilgrims')" />
                                        <input type="number" min="1" class="mt-1 block w-full rounded-xl border-slate-300 text-sm" x-model="row.min_pilgrims" :name="'packages[' + index + '][min_pilgrims]'">
                                    </div>
                                    <div class="flex-1">
                                        <x-input-label :value="__('layanan_pendukung.max_pilgrims')" />
                                        <input type="number" min="1" class="mt-1 block w-full rounded-xl border-slate-300 text-sm" x-model="row.max_pilgrims" :name="'packages[' + index + '][max_pilgrims]'">
                                    </div>
                                </div>
                                <div class="sm:col-span-2">
                                    <x-input-label :value="__('layanan_pendukung.package_description')" />
                                    <textarea rows="2" class="mt-1 block w-full rounded-xl border-slate-300 text-sm" x-model="row.description" :name="'packages[' + index + '][description]'"></textarea>
                                </div>
                                <div class="flex items-center gap-2 sm:col-span-2">
                                    <input type="hidden" :name="'packages[' + index + '][is_active]'" value="0">
                                    <input type="checkbox" value="1" class="rounded border-slate-300 text-brand-600" x-model="row.is_active" :name="'packages[' + index + '][is_active]'">
                                    <span class="text-sm text-slate-700">{{ __('layanan_pendukung.is_active') }}</span>
                                </div>
                            </div>
                            <button type="button" @click="removeRow(index)" class="text-xs font-semibold text-red-700 hover:text-red-800">Hapus baris</button>
                        </div>
                    </template>

                    <x-input-error :messages="$errors->get('packages')" />

                    <div class="flex flex-wrap gap-3">
                        <button type="button" @click="addRow()" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                            {{ __('layanan_pendukung.add_row') }}
                        </button>
                        <x-submit-button class="rounded-xl bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-brand-700">
                            {{ __('layanan_pendukung.save_packages') }}
                        </x-submit-button>
                    </div>
                </form>
            </div>
        </x-page-container>
    </x-ui.app-page>
</x-app-layout>
