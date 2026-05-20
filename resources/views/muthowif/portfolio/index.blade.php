<x-app-layout>

    <div class="relative min-h-[calc(100vh-4rem)] overflow-hidden bg-gradient-to-b from-slate-100 via-slate-50 to-white py-6 sm:py-8">
        <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_80%_40%_at_50%_-10%,rgba(14,165,233,0.06),transparent)]"></div>
        <div class="relative mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">
            
            {{-- Hero Section --}}
            <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-slate-900 via-sky-950 to-blue-950 p-5 text-white shadow-lg shadow-sky-950/30 ring-1 ring-white/10 sm:rounded-3xl sm:p-6">
                <div class="pointer-events-none absolute inset-0 bg-[url('data:image/svg+xml,%3Csvg width=\'60\' height=\'60\' viewBox=\'0 0 60 60\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'none\' fill-rule=\'evenodd\'%3E%3Cg fill=\'%23ffffff\' fill-opacity=\'0.05\'%3E%3Cpath d=\'M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E')] opacity-40"></div>
                <div class="pointer-events-none absolute -right-12 top-0 h-40 w-40 rounded-full bg-sky-500/15 blur-3xl"></div>
                <div class="relative flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div class="flex items-start gap-3">
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-white/15 ring-1 ring-white/20" aria-hidden="true">
                            <svg class="h-6 w-6 text-sky-300" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                            </svg>
                        </span>
                        <div class="min-w-0">
                            <p class="text-[11px] font-semibold uppercase tracking-wider text-sky-100/90">Portfolio Galeri</p>
                            <h1 class="mt-1 text-xl font-bold tracking-tight text-white sm:text-2xl">Galeri Foto Bersama Jamaah</h1>
                            <p class="mt-2 max-w-xl text-sm leading-relaxed text-sky-50/90">
                                Unggah foto-foto terbaik Anda saat membimbing ibadah Umroh atau Haji bersama jamaah. Ini akan ditampilkan secara elegan di halaman profil Anda sebagai bukti pelayanan premium dan tepercaya.
                            </p>
                        </div>
                    </div>
                    <a href="{{ route('dashboard') }}" class="inline-flex shrink-0 items-center gap-2 self-start rounded-xl border border-white/20 bg-white/10 px-4 py-2.5 text-sm font-semibold text-white backdrop-blur-sm transition hover:bg-white/20">
                        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M9.293 2.293a1 1 0 011.414 0l7 7A1 1 0 0117 11h-1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-3a1 1 0 00-1-1H9a1 1 0 00-1 1v3a1 1 0 01-1 1H5a1 1 0 01-1-1v-6H3a1 1 0 01-.707-1.707l7-7z" clip-rule="evenodd" /></svg>
                        Kembali ke Dashboard
                    </a>
                </div>
            </div>

            {{-- Flash Alert --}}
            @if (session('status'))
                <div class="flex items-center gap-3 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-medium text-emerald-800 shadow-sm">
                    <svg class="h-5 w-5 shrink-0 text-emerald-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd"/>
                    </svg>
                    <span>{{ session('status') }}</span>
                </div>
            @endif

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                
                {{-- Upload Form --}}
                <div class="lg:col-span-1">
                    <div class="sticky top-6 overflow-hidden rounded-2xl border border-slate-200/90 bg-gradient-to-b from-white to-slate-50/80 shadow-sm ring-1 ring-slate-100/80">
                        <div class="flex min-w-0">
                            <div class="w-1 shrink-0 bg-sky-500" aria-hidden="true"></div>
                            <div class="min-w-0 flex-1 p-5 sm:p-6">
                                <h2 class="font-semibold text-slate-900">Tambah Foto Portofolio</h2>
                                <p class="mt-1 text-xs text-slate-500">Isi keterangan dan unggah foto dokumentasi kegiatan pelayanan Anda.</p>
                                
                                <form method="POST" action="{{ route('muthowif.portfolio.store') }}" enctype="multipart/form-data" class="mt-4 space-y-4">
                                    @csrf
                                    <div>
                                        <x-input-label for="title" value="Judul Kegiatan" />
                                        <x-text-input id="title" name="title" type="text" class="mt-1 block w-full border-slate-300" required
                                                      value="{{ old('title') }}" placeholder="Misal: Ziarah Jabal Rahmah Jamaah VIP" />
                                        <x-input-error class="mt-2" :messages="$errors->get('title')" />
                                    </div>
                                    <div>
                                        <x-input-label for="description" value="Keterangan / Deskripsi (Opsional)" />
                                        <textarea id="description" name="description" rows="3" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 text-sm" placeholder="Ceritakan singkat pelayanan Anda di foto ini...">{{ old('description') }}</textarea>
                                        <x-input-error class="mt-2" :messages="$errors->get('description')" />
                                    </div>
                                    <div x-data="{ imagePreview: null }">
                                        <x-input-label for="image" value="Unggah Foto" />
                                        <div class="mt-1 relative flex justify-center rounded-xl border border-dashed border-slate-300 bg-slate-50/50 px-4 py-5 transition hover:bg-slate-50 min-h-[140px] items-center">
                                            
                                            {{-- Upload placeholder (visible when no image is selected) --}}
                                            <div x-show="!imagePreview" class="space-y-1 text-center">
                                                <svg class="mx-auto h-10 w-10 text-slate-400" stroke="currentColor" fill="none" viewBox="0 0 48 48" aria-hidden="true">
                                                    <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                                </svg>
                                                <div class="flex text-xs text-slate-600 justify-center">
                                                    <label for="image" class="relative cursor-pointer rounded-md font-semibold text-brand-600 focus-within:outline-none focus-within:ring-2 focus-within:ring-brand-500 focus-within:ring-offset-2 hover:text-brand-500">
                                                        <span>Pilih berkas</span>
                                                        <input id="image" name="image" type="file" class="sr-only" accept="image/*,.heic,.heif" required
                                                               @change="
                                                                   const file = $event.target.files[0];
                                                                   if (file) {
                                                                       const reader = new FileReader();
                                                                       reader.onload = (e) => { imagePreview = e.target.result; };
                                                                       reader.readAsDataURL(file);
                                                                   } else {
                                                                       imagePreview = null;
                                                                   }
                                                               ">
                                                    </label>
                                                    <p class="pl-1">atau seret ke sini</p>
                                                </div>
                                                <p class="text-[10px] text-slate-400">PNG, JPG, JPEG, WEBP, HEIC, HEIF hingga 10MB</p>
                                            </div>

                                            {{-- Beautiful Image Preview (visible when an image is selected) --}}
                                            <div x-show="imagePreview" x-cloak class="w-full flex flex-col items-center">
                                                <div class="relative w-full aspect-video rounded-lg overflow-hidden border border-slate-200 shadow-sm bg-slate-100">
                                                    <img :src="imagePreview" class="h-full w-full object-cover">
                                                    <button 
                                                        type="button"
                                                        @click="imagePreview = null; document.getElementById('image').value = ''" 
                                                        class="absolute top-2 right-2 flex h-8 w-8 items-center justify-center rounded-full bg-slate-900/70 text-white hover:bg-slate-900/90 shadow transition"
                                                        title="Hapus pilihan foto"
                                                    >
                                                        <svg class="h-4.5 w-4.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                        </svg>
                                                    </button>
                                                </div>
                                                <p class="mt-2 text-xs text-slate-500">Foto terpilih. Klik silang di sudut kanan atas untuk membatalkan.</p>
                                            </div>
                                        </div>
                                        <x-input-error class="mt-2" :messages="$errors->get('image')" />
                                    </div>
                                    <div class="pt-2">
                                        <x-primary-button type="submit" class="w-full justify-center">
                                            Simpan Portofolio
                                        </x-primary-button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Portfolio Gallery List --}}
                <div class="lg:col-span-2 space-y-4">
                    <div class="rounded-2xl border border-slate-200/90 bg-white p-5 shadow-sm sm:p-6">
                        <h2 class="font-semibold text-slate-900 text-lg">Daftar Portofolio Anda</h2>
                        <p class="text-sm text-slate-500">Semua foto yang diunggah akan muncul sebagai album galeri di halaman profil publik Anda.</p>
                        
                        @if ($portfolios->isEmpty())
                            <div class="mt-6 border border-dashed border-slate-200 rounded-2xl px-5 py-12 text-center sm:px-6 sm:py-16 bg-slate-50/50">
                                <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-sky-50 text-sky-700 ring-1 ring-sky-200/80" aria-hidden="true">
                                    <svg class="h-7 w-7" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                                    </svg>
                                </span>
                                <p class="mt-4 text-base font-semibold text-slate-900">Belum Ada Foto Portofolio</p>
                                <p class="mx-auto mt-2 max-w-sm text-sm text-slate-500">Anda belum mengunggah foto portofolio. Silakan gunakan formulir di samping untuk menambahkan foto kegiatan perdana Anda.</p>
                            </div>
                        @else
                            <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2">
                                @foreach ($portfolios as $portfolio)
                                    <div class="group relative overflow-hidden rounded-xl border border-slate-200 bg-slate-50 shadow-sm transition hover:shadow-md hover:border-slate-300">
                                        
                                        {{-- Image Container --}}
                                        <div class="aspect-video w-full overflow-hidden bg-slate-200">
                                            <img src="{{ route('layanan.portfolio.photo', $portfolio) }}" alt="{{ $portfolio->title }}" class="h-full w-full object-cover transition duration-300 group-hover:scale-105" loading="lazy">
                                        </div>

                                        {{-- Edit Form --}}
                                        <form method="POST" action="{{ route('muthowif.portfolio.update', $portfolio) }}" enctype="multipart/form-data" class="border-t border-slate-200 bg-white p-4 space-y-3">
                                            @csrf
                                            @method('PATCH')

                                            <div>
                                                <x-input-label for="portfolio_title_{{ $portfolio->id }}" value="Judul Kegiatan" />
                                                <x-text-input
                                                    id="portfolio_title_{{ $portfolio->id }}"
                                                    name="title"
                                                    type="text"
                                                    class="mt-1 block w-full border-slate-300 text-sm"
                                                    required
                                                    value="{{ old('title', $portfolio->title) }}"
                                                />
                                            </div>

                                            <div>
                                                <x-input-label for="portfolio_description_{{ $portfolio->id }}" value="Keterangan / Deskripsi" />
                                                <textarea
                                                    id="portfolio_description_{{ $portfolio->id }}"
                                                    name="description"
                                                    rows="2"
                                                    class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500"
                                                >{{ old('description', $portfolio->description) }}</textarea>
                                            </div>

                                            <div>
                                                <x-input-label for="portfolio_image_{{ $portfolio->id }}" value="Ganti Foto (opsional)" />
                                                <x-input-file
                                                    id="portfolio_image_{{ $portfolio->id }}"
                                                    name="image"
                                                    accept="image/*,.heic,.heif"
                                                    class="mt-1"
                                                />
                                                <p class="mt-1 text-[11px] text-slate-500">Kosongkan jika ingin tetap memakai foto saat ini.</p>
                                            </div>

                                            <x-input-error class="mt-2" :messages="$errors->get('title')" />
                                            <x-input-error class="mt-2" :messages="$errors->get('description')" />
                                            <x-input-error class="mt-2" :messages="$errors->get('image')" />

                                            <div class="flex justify-end">
                                                <x-primary-button type="submit" class="justify-center text-xs">
                                                    Simpan Perubahan
                                                </x-primary-button>
                                            </div>
                                        </form>

                                        {{-- Delete Button Bottom Bar --}}
                                        <div class="border-t border-slate-200 bg-white/80 p-2.5 flex justify-end">
                                            <form action="{{ route('muthowif.portfolio.destroy', $portfolio) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus foto portofolio ini?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="inline-flex items-center gap-1.5 rounded-lg border border-red-200 bg-red-50/50 px-2.5 py-1.5 text-xs font-semibold text-red-700 transition hover:bg-red-100 hover:text-red-800">
                                                    <svg class="h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                        <path fill-rule="evenodd" d="M8.75 1A2.75 2.75 0 006 3.75V4H5a2 2 0 00-2 2v2c0 .414.336.75.75.75h12.5a.75.75 0 00.75-.75V6a2 2 0 00-2-2h-1V3.75A2.75 2.75 0 0011.25 1h-2.5zM6.25 4v-.25c0-.69.56-1.25 1.25-1.25h2.5c.69 0 1.25.56 1.25 1.25V4h-5z" clip-rule="evenodd" />
                                                        <path d="M4 9.75A.75.75 0 014.75 9h10.5a.75.75 0 01.75.75v6.75a2.25 2.25 0 01-2.25 2.25H6.75A2.25 2.25 0 014.5 16.5V9.75z" />
                                                    </svg>
                                                    Hapus Foto
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            @if ($portfolios->hasPages())
                                <div class="mt-6 flex justify-center border-t border-slate-100 pt-4">
                                    {{ $portfolios->links() }}
                                </div>
                            @endif
                        @endif
                    </div>
                </div>

            </div>

        </div>
    </div>
</x-app-layout>
