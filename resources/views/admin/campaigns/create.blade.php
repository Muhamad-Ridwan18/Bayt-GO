<x-app-layout>
    <x-ui.app-page>
        <x-page-container class="ui-stack relative">
            
            <div class="flex items-center gap-4">
                <a href="{{ route('admin.campaign.index') }}" class="rounded-full bg-white p-2 text-slate-400 shadow-sm transition hover:bg-slate-50 hover:text-slate-600">
                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M17 10a.75.75 0 01-.75.75H5.612l4.158 3.96a.75.75 0 11-1.04 1.08l-5.5-5.25a.75.75 0 010-1.08l5.5-5.25a.75.75 0 111.04 1.08L5.612 9.25H16.25A.75.75 0 0117 10z" clip-rule="evenodd" />
                    </svg>
                </a>
                <div>
                    <h1 class="text-2xl font-bold text-slate-900">Buat Campaign Baru</h1>
                    <p class="mt-1 text-sm text-slate-600">Tambahkan promosi atau event baru.</p>
                </div>
            </div>

            <form action="{{ route('admin.campaign.store') }}" method="POST" enctype="multipart/form-data" class="ui-stack-compact">
                @csrf
                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="ui-card-pad-lg ui-stack-compact">
                        
                        <div>
                            <x-input-label for="title" value="Judul Campaign *" />
                            <x-text-input id="title" name="title" type="text" class="mt-1 block w-full" :value="old('title')" required />
                            <x-input-error :messages="$errors->get('title')" class="mt-2" />
                        </div>

                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div>
                                <x-input-label for="start_date" value="Tanggal Mulai *" />
                                <x-text-input id="start_date" name="start_date" type="datetime-local" class="mt-1 block w-full" :value="old('start_date')" required />
                                <x-input-error :messages="$errors->get('start_date')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="end_date" value="Tanggal Selesai *" />
                                <x-text-input id="end_date" name="end_date" type="datetime-local" class="mt-1 block w-full" :value="old('end_date')" required />
                                <x-input-error :messages="$errors->get('end_date')" class="mt-2" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div>
                                <x-input-label for="desktop_banner" value="Banner Desktop (Opsional)" />
                                <input id="desktop_banner" name="desktop_banner" type="file" class="mt-1 block w-full text-sm text-slate-500 file:mr-4 file:rounded-full file:border-0 file:bg-brand-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-brand-700 hover:file:bg-brand-100" accept="image/*" />
                                <x-input-error :messages="$errors->get('desktop_banner')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="mobile_banner" value="Banner Mobile (Opsional)" />
                                <input id="mobile_banner" name="mobile_banner" type="file" class="mt-1 block w-full text-sm text-slate-500 file:mr-4 file:rounded-full file:border-0 file:bg-brand-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-brand-700 hover:file:bg-brand-100" accept="image/*" />
                                <x-input-error :messages="$errors->get('mobile_banner')" class="mt-2" />
                            </div>
                        </div>

                        <div>
                            <x-input-label for="body" value="Deskripsi / Syarat & Ketentuan" />
                            <textarea id="body" name="body" rows="4" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">{{ old('body') }}</textarea>
                            <x-input-error :messages="$errors->get('body')" class="mt-2" />
                        </div>

                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
                            <div>
                                <x-input-label for="cta_text" value="Teks Tombol CTA" />
                                <x-text-input id="cta_text" name="cta_text" type="text" class="mt-1 block w-full" :value="old('cta_text', 'Pesan Sekarang')" />
                            </div>
                            <div>
                                <x-input-label for="cta_url" value="URL Tombol CTA" />
                                <x-text-input id="cta_url" name="cta_url" type="text" class="mt-1 block w-full" :value="old('cta_url', '#')" />
                            </div>
                            <div>
                                <x-input-label for="theme_color" value="Warna Tema (Hex)" />
                                <x-text-input id="theme_color" name="theme_color" type="text" class="mt-1 block w-full" :value="old('theme_color', '#10b981')" />
                            </div>
                        </div>

                        <div class="flex items-center gap-6">
                            <div>
                                <x-input-label for="sort_order" value="Urutan (Sort)" />
                                <x-text-input id="sort_order" name="sort_order" type="number" class="mt-1 block w-full" :value="old('sort_order', 0)" />
                            </div>
                            <div class="mt-6 flex items-center">
                                <input id="is_active" name="is_active" type="checkbox" value="1" class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-600" {{ old('is_active', true) ? 'checked' : '' }}>
                                <label for="is_active" class="ml-2 block text-sm text-slate-900">Aktif (Publish)</label>
                            </div>
                        </div>
                    </div>
                    <div class="bg-slate-50 px-6 py-4 sm:flex sm:flex-row-reverse sm:px-8">
                        <x-submit-button class="w-full rounded-xl bg-baytgo px-5 py-2.5 text-sm font-semibold text-white shadow-md shadow-baytgo/20 transition hover:bg-baytgo-800 sm:ml-3 sm:w-auto">
                            Simpan Campaign
                        </x-submit-button>
                    </div>
                </div>
            </form>
        </x-page-container>
</x-ui.app-page>
</x-app-layout>
