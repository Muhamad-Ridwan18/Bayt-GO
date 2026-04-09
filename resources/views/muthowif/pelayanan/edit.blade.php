@php
    $addonRows = old('add_ons');
    if (is_array($addonRows)) {
        $addonRows = collect($addonRows)->map(function ($row) {
            if (! is_array($row)) {
                return ['name' => '', 'price' => ''];
            }

            return [
                'name' => $row['name'] ?? '',
                'price' => isset($row['price']) ? preg_replace('/\D+/', '', (string) $row['price']) : '',
            ];
        })->values()->all();
    } elseif ($privateService->addOns->isEmpty()) {
        $addonRows = [['name' => '', 'price' => '']];
    } else {
        $addonRows = $privateService->addOns->map(function ($a) {
            return [
                'name' => $a->name,
                'price' => (string) (int) $a->price,
            ];
        })->values()->all();
    }
@endphp

<x-app-layout>

    <div class="py-8 sm:py-12">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <div x-data="{ activeTab: 'group' }" class="space-y-5">
                <div class="rounded-2xl border border-slate-200 bg-white p-3 shadow-sm">
                    <div class="grid grid-cols-2 gap-2">
                        <button
                            type="button"
                            @click="activeTab = 'group'"
                            class="inline-flex items-center justify-center rounded-xl px-3 py-2.5 text-sm font-semibold transition"
                            :class="activeTab === 'group' ? 'bg-brand-600 text-white shadow-sm' : 'bg-slate-50 text-slate-700 hover:bg-slate-100'"
                        >
                            Layanan Group
                        </button>
                        <button
                            type="button"
                            @click="activeTab = 'private'"
                            class="inline-flex items-center justify-center rounded-xl px-3 py-2.5 text-sm font-semibold transition"
                            :class="activeTab === 'private' ? 'bg-brand-600 text-white shadow-sm' : 'bg-slate-50 text-slate-700 hover:bg-slate-100'"
                        >
                            Layanan Private
                        </button>
                    </div>
                </div>

                {{-- Group --}}
                <div
                    x-show="activeTab === 'group'"
                    x-transition.opacity.duration.150ms
                    class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden flex flex-col"
                >
                    <div class="border-b border-slate-100 bg-slate-50/80 px-5 py-4">
                        <h3 class="text-base font-semibold text-slate-900">Form Tambah Layanan – Group</h3>
                        <p class="mt-1 text-xs text-slate-600">Layanan untuk jemaah bertipe rombongan (group)</p>
                    </div>
                    <form method="POST" action="{{ route('muthowif.pelayanan.group') }}" class="flex flex-col flex-1 p-5 sm:p-6 space-y-5">
                        @csrf
                        @method('PUT')

                        <div>
                            <x-input-label for="group_name" value="Nama Layanan" />
                            <x-text-input id="group_name" name="group_name" type="text" class="mt-1 block w-full"
                                          :value="old('group_name', $groupService->name)" required
                                          placeholder="Contoh: Layanan Umrah Eksekutif 9 Hari" />
                            <x-input-error class="mt-2" :messages="$errors->get('group_name')" />
                        </div>

                        <div>
                            <x-input-label for="group_daily_price" value="Harga Harian" />
                            <x-indonesian-number-input
                                name="group_daily_price"
                                id="group_daily_price"
                                :value="old('group_daily_price', $groupService->daily_price !== null ? (string) (int) $groupService->daily_price : '')"
                                required
                                placeholder="Contoh: 250.000"
                                :prefix="true"
                            />
                            <x-input-error class="mt-2" :messages="$errors->get('group_daily_price')" />
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="group_min_pilgrims" value="Minimal Jemaah" />
                                <x-indonesian-number-input
                                    name="group_min_pilgrims"
                                    id="group_min_pilgrims"
                                    :value="old('group_min_pilgrims', $groupService->min_pilgrims)"
                                    required
                                    placeholder="Contoh: 10"
                                />
                                <x-input-error class="mt-2" :messages="$errors->get('group_min_pilgrims')" />
                            </div>
                            <div>
                                <x-input-label for="group_max_pilgrims" value="Maksimal Jemaah" />
                                <x-indonesian-number-input
                                    name="group_max_pilgrims"
                                    id="group_max_pilgrims"
                                    :value="old('group_max_pilgrims', $groupService->max_pilgrims)"
                                    required
                                    placeholder="Contoh: 20"
                                />
                                <x-input-error class="mt-2" :messages="$errors->get('group_max_pilgrims')" />
                            </div>
                        </div>

                        <div>
                            <x-input-label for="group_description" value="Deskripsi" />
                            <textarea id="group_description" name="group_description" rows="5"
                                      class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 text-sm"
                                      placeholder="Jelaskan layanan, fasilitas, pendampingan, dll.">{{ old('group_description', $groupService->description) }}</textarea>
                            <x-input-error class="mt-2" :messages="$errors->get('group_description')" />
                        </div>

                        <div class="space-y-3">
                            <label class="flex items-start gap-3 cursor-pointer">
                                <input type="checkbox" name="group_stays_at_same_hotel" value="1"
                                       class="mt-1 rounded border-slate-300 text-brand-600 shadow-sm focus:ring-brand-500"
                                       @checked(old('group_stays_at_same_hotel', $groupService->stays_at_same_hotel)) />
                                <span class="text-sm text-slate-700">Muthowif tinggal di hotel yang sama dengan jemaah</span>
                            </label>
                            <label class="flex items-start gap-3 cursor-pointer">
                                <input type="checkbox" name="group_includes_transport" value="1"
                                       class="mt-1 rounded border-slate-300 text-brand-600 shadow-sm focus:ring-brand-500"
                                       @checked(old('group_includes_transport', $groupService->includes_transport)) />
                                <span class="text-sm text-slate-700">Termasuk transportasi selama di Tanah Suci</span>
                            </label>
                        </div>

                        <div class="pt-2 mt-auto">
                            <button type="submit"
                                    class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-brand-700 px-4 py-3 text-sm font-semibold text-white shadow-sm hover:bg-brand-800 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2">
                                <svg class="h-5 w-5 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25l-7.5 3.75V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0111.186 0z" />
                                </svg>
                                Simpan Layanan Group
                            </button>
                        </div>
                    </form>
                </div>

                {{-- Private --}}
                <div
                    x-show="activeTab === 'private'"
                    x-transition.opacity.duration.150ms
                    class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden flex flex-col"
                >
                    <div class="border-b border-slate-100 bg-slate-50/80 px-5 py-4">
                        <h3 class="text-base font-semibold text-slate-900">Form Tambah Layanan – Private</h3>
                        <p class="mt-1 text-xs text-slate-600">Layanan untuk jemaah bertipe privat / keluarga</p>
                    </div>
                    <form method="POST" action="{{ route('muthowif.pelayanan.private') }}" class="flex flex-col flex-1 p-5 sm:p-6 space-y-5" x-data="muthowifPrivatePelayananForm(@js($addonRows))">
                        @csrf
                        @method('PUT')

                        <div>
                            <x-input-label for="private_name" value="Nama Layanan" />
                            <x-text-input id="private_name" name="private_name" type="text" class="mt-1 block w-full"
                                          :value="old('private_name', $privateService->name)" required
                                          placeholder="Contoh: Private Umrah VIP 12 Hari" />
                            <x-input-error class="mt-2" :messages="$errors->get('private_name')" />
                        </div>

                        <div>
                            <x-input-label for="private_daily_price" value="Harga Harian" />
                            <x-indonesian-number-input
                                name="private_daily_price"
                                id="private_daily_price"
                                :value="old('private_daily_price', $privateService->daily_price !== null ? (string) (int) $privateService->daily_price : '')"
                                required
                                placeholder="Contoh: 350.000"
                                :prefix="true"
                            />
                            <x-input-error class="mt-2" :messages="$errors->get('private_daily_price')" />
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="private_min_pilgrims" value="Minimal Jemaah" />
                                <x-indonesian-number-input
                                    name="private_min_pilgrims"
                                    id="private_min_pilgrims"
                                    :value="old('private_min_pilgrims', $privateService->min_pilgrims)"
                                    required
                                    placeholder="Contoh: 1"
                                />
                                <x-input-error class="mt-2" :messages="$errors->get('private_min_pilgrims')" />
                            </div>
                            <div>
                                <x-input-label for="private_max_pilgrims" value="Maksimal Jemaah" />
                                <x-indonesian-number-input
                                    name="private_max_pilgrims"
                                    id="private_max_pilgrims"
                                    :value="old('private_max_pilgrims', $privateService->max_pilgrims)"
                                    required
                                    placeholder="Contoh: 8"
                                />
                                <x-input-error class="mt-2" :messages="$errors->get('private_max_pilgrims')" />
                            </div>
                        </div>

                        <div>
                            <x-input-label for="private_description" value="Deskripsi" />
                            <textarea id="private_description" name="private_description" rows="5"
                                      class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 text-sm"
                                      placeholder="Jelaskan layanan, fasilitas, pendampingan, dll.">{{ old('private_description', $privateService->description) }}</textarea>
                            <x-input-error class="mt-2" :messages="$errors->get('private_description')" />
                        </div>

                        <div class="space-y-3">
                            <label class="flex items-start gap-3 cursor-pointer">
                                <input type="checkbox" name="private_stays_at_same_hotel" value="1"
                                       class="mt-1 rounded border-slate-300 text-brand-600 shadow-sm focus:ring-brand-500"
                                       @checked(old('private_stays_at_same_hotel', $privateService->stays_at_same_hotel)) />
                                <span class="text-sm text-slate-700">Muthowif tinggal di hotel yang sama dengan jemaah</span>
                            </label>
                            <label class="flex items-start gap-3 cursor-pointer">
                                <input type="checkbox" name="private_includes_transport" value="1"
                                       class="mt-1 rounded border-slate-300 text-brand-600 shadow-sm focus:ring-brand-500"
                                       @checked(old('private_includes_transport', $privateService->includes_transport)) />
                                <span class="text-sm text-slate-700">Termasuk transportasi selama di Tanah Suci</span>
                            </label>
                        </div>

                        <div class="rounded-xl border-2 border-dashed border-slate-200 bg-slate-50/50 p-4 space-y-4">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <p class="text-sm font-semibold text-slate-900 flex items-center gap-1">
                                        <span class="text-brand-600 text-lg leading-none">+</span> Add Ons
                                    </p>
                                    <p class="text-xs text-slate-500 mt-0.5">Tambahkan opsi add ons tambahan untuk layanan ini.</p>
                                </div>
                                <button type="button" @click="rows.push({ name: '', price: '' })"
                                        class="shrink-0 text-xs font-semibold text-brand-700 hover:text-brand-800">+ Baris</button>
                            </div>
                            <x-input-error class="mt-0" :messages="$errors->get('add_ons')" />
                            <template x-for="(row, index) in rows" :key="index">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pb-4 border-b border-slate-200 last:border-0 last:pb-0">
                                    <div>
                                        <label class="block text-xs font-medium text-slate-600 mb-1">Nama Add Ons</label>
                                        <input type="text" :name="'add_ons[' + index + '][name]'" x-model="row.name"
                                               class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500"
                                               placeholder="Contoh: City Tour Thaif" />
                                    </div>
                                    <div class="flex gap-2 items-end">
                                        <div class="flex-1">
                                            <label class="block text-xs font-medium text-slate-600 mb-1">Harga</label>
                                            <div class="flex rounded-lg border border-slate-300 shadow-sm overflow-hidden">
                                                <span class="inline-flex items-center px-2 bg-white text-slate-500 text-xs border-e border-slate-200 shrink-0">Rp</span>
                                                <input type="text" inputmode="numeric" autocomplete="off"
                                                       x-init="$el.value = formatDigits(String(row.price || ''))"
                                                       @input="onAddonPriceInput($event, row)"
                                                       class="block w-full min-w-0 flex-1 border-0 py-2 px-2 text-sm focus:ring-0"
                                                       placeholder="500.000" />
                                                <input type="hidden" :name="'add_ons[' + index + '][price]'" :value="row.price" />
                                            </div>
                                        </div>
                                        <button type="button" @click="rows.length > 1 ? rows.splice(index, 1) : (rows[index] = { name: '', price: '' })"
                                                class="mb-0.5 text-xs font-semibold text-red-600 hover:text-red-700 px-1">Hapus</button>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <div class="pt-2 mt-auto">
                            <button type="submit"
                                    class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-brand-700 px-4 py-3 text-sm font-semibold text-white shadow-sm hover:bg-brand-800 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2">
                                <svg class="h-5 w-5 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25l-7.5 3.75V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0111.186 0z" />
                                </svg>
                                Simpan Layanan Private
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
