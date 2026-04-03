<x-guest-layout>
    <div class="text-center space-y-4">
        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-amber-100 text-amber-700">
            <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </div>
        <h1 class="text-xl font-semibold text-slate-900">Pendaftaran muthowif terkirim</h1>
        <p class="text-sm text-slate-600 leading-relaxed">
            Dokumen dan foto Anda sedang ditinjau oleh admin. Anda <strong>belum dapat masuk</strong> ke akun sampai pendaftaran disetujui.
        </p>
        <p class="text-xs text-slate-500">
            Setelah disetujui, gunakan email dan password yang Anda daftarkan untuk masuk.
        </p>
        <div class="pt-2">
            <a href="{{ route('login') }}" class="inline-flex items-center justify-center rounded-xl bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-brand-700 transition">
                Ke halaman masuk
            </a>
        </div>
    </div>
</x-guest-layout>
