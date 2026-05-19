<x-marketplace-layout>
    @php
        $fallbackSvg = 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 100 100" fill="%23cbd5e1"><rect width="100%" height="100%"/><text x="50%" y="55%" font-size="24" font-family="system-ui,sans-serif" font-weight="bold" fill="%2364748b" text-anchor="middle">BG</text></svg>';
    @endphp

    <x-slot:title>
        Galeri Foto & Portfolio {{ $profile->user->name }} | BaytGo
    </x-slot:title>

    <div class="relative min-h-screen overflow-hidden bg-gradient-to-b from-slate-100 via-slate-50 to-white py-8 sm:py-12">
        {{-- Background decorative gradients --}}
        <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_80%_40%_at_50%_-10%,rgba(120,53,15,0.06),transparent)]"></div>
        <div class="pointer-events-none absolute -left-20 top-40 h-72 w-72 rounded-full bg-brand-400/5 blur-3xl"></div>
        <div class="pointer-events-none absolute -right-20 bottom-20 h-80 w-80 rounded-full bg-violet-400/5 blur-3xl"></div>

        <div class="relative mx-auto max-w-5xl px-4 sm:px-6 lg:px-8 space-y-8">
            {{-- Navigation back & breadcrumb --}}
            <div class="flex items-center justify-between">
                <a href="{{ route('layanan.show', $profile) }}" class="inline-flex items-center gap-2 rounded-xl border border-slate-200/90 bg-white px-4 py-2.5 text-xs font-bold text-slate-700 shadow-sm transition hover:border-brand-300 hover:text-brand-700 hover:shadow">
                    <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" /></svg>
                    <span>Kembali ke Profil</span>
                </a>
            </div>

            {{-- Profile Header card --}}
            <div class="relative overflow-hidden rounded-3xl border border-slate-200/80 bg-white p-6 shadow-sm ring-1 ring-slate-100/80">
                <div class="flex flex-col items-center gap-5 sm:flex-row sm:items-start text-center sm:text-left">
                    <div class="relative shrink-0">
                        <img 
                            src="{{ route('layanan.photo', $profile) }}" 
                            alt="{{ $profile->user->name }}" 
                            class="h-20 w-20 rounded-2xl object-cover shadow ring-2 ring-white"
                            onerror="this.onerror=null; this.src={!! json_encode($fallbackSvg) !!}"
                        >
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-[11px] font-bold uppercase tracking-wider text-brand-700">Portofolio & Dokumentasi Kegiatan</p>
                        <h1 class="mt-1 text-2xl font-extrabold tracking-tight text-slate-900">{{ $profile->user->name }}</h1>
                        <p class="mt-1 text-sm text-slate-600">Dokumentasi momen berharga bersama jamaah saat mendampingi ibadah di Tanah Suci.</p>
                    </div>
                </div>
            </div>

            {{-- Grid of Portfolios --}}
            <div class="space-y-6" x-data="{ lightboxOpen: false, activeImage: '', activeTitle: '', activeDesc: '' }">
                @if ($portfolios->isEmpty())
                    <div class="rounded-3xl border border-slate-200/80 bg-white py-16 px-4 text-center ring-1 ring-slate-100/80 shadow-sm">
                        <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-amber-50 text-amber-700 ring-1 ring-amber-200/80" aria-hidden="true">
                            <svg class="h-7 w-7" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" /></svg>
                        </span>
                        <h3 class="mt-4 text-base font-bold text-slate-900">Belum ada portofolio</h3>
                        <p class="mx-auto mt-2 max-w-sm text-sm text-slate-500">Muthowif ini belum mengunggah foto portofolio dokumentasi kegiatan.</p>
                    </div>
                @else
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 md:grid-cols-3">
                        @foreach ($portfolios as $portfolio)
                            <div 
                                @click="lightboxOpen = true; activeImage = '{{ route('layanan.portfolio.photo', $portfolio) }}'; activeTitle = '{{ e($portfolio->title) }}'; activeDesc = '{{ e($portfolio->description ?? '') }}'"
                                class="group flex flex-col overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm cursor-pointer hover:border-brand-300 hover:shadow-md transition duration-200"
                            >
                                <div class="relative aspect-[4/3] overflow-hidden bg-slate-100">
                                    <img src="{{ route('layanan.portfolio.photo', $portfolio) }}" alt="{{ $portfolio->title }}" class="h-full w-full object-cover transition duration-300 group-hover:scale-105" loading="lazy">
                                    <div class="absolute inset-0 bg-slate-950/20 opacity-0 group-hover:opacity-100 transition-opacity duration-200 flex items-center justify-center">
                                        <span class="flex h-10 w-10 items-center justify-center rounded-full bg-white/90 text-slate-800 shadow">
                                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607zM10.5 7.5v6m3-3h-6" /></svg>
                                        </span>
                                    </div>
                                </div>
                                <div class="flex flex-1 flex-col p-4">
                                    <h3 class="font-bold text-slate-900 group-hover:text-brand-700 transition line-clamp-1">{{ $portfolio->title }}</h3>
                                    @if ($portfolio->description)
                                        <p class="mt-1.5 text-xs text-slate-600 line-clamp-2 leading-relaxed">{{ $portfolio->description }}</p>
                                    @else
                                        <p class="mt-1.5 text-xs text-slate-400 italic">Tidak ada keterangan tambahan.</p>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- Pagination Links --}}
                    <div class="flex justify-center border-t border-slate-200/60 pt-6">
                        {{ $portfolios->links() }}
                    </div>

                    {{-- Lightbox Modal Container --}}
                    <div 
                        x-show="lightboxOpen" 
                        x-cloak
                        class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-950/90 p-4 backdrop-blur-sm"
                        @keydown.escape.window="lightboxOpen = false"
                    >
                        <div 
                            class="relative max-w-3xl w-full bg-white rounded-2xl overflow-hidden shadow-2xl flex flex-col"
                            @click.away="lightboxOpen = false"
                        >
                            {{-- Image --}}
                            <div class="relative bg-slate-950 max-h-[70vh] flex items-center justify-center">
                                <img :src="activeImage" :alt="activeTitle" class="max-h-[70vh] max-w-full object-contain">
                                <button 
                                    @click="lightboxOpen = false" 
                                    class="absolute top-4 right-4 flex h-10 w-10 items-center justify-center rounded-full bg-slate-950/60 text-white hover:bg-slate-950/80 transition"
                                >
                                    <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                            {{-- Info Panel --}}
                            <div class="p-5 border-t border-slate-100 bg-white">
                                <h3 class="text-lg font-bold text-slate-950" x-text="activeTitle"></h3>
                                <p class="mt-2 text-sm text-slate-600 leading-relaxed" x-text="activeDesc || 'Tidak ada keterangan tambahan.'"></p>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-marketplace-layout>
