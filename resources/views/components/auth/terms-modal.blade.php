@props([])

<div
    x-show="termsModalOpen"
    x-cloak
    class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4"
    role="dialog"
    aria-modal="true"
>
    <div class="w-full max-w-lg overflow-hidden rounded-3xl bg-white shadow-2xl ring-1 ring-slate-200">
        <div class="border-b border-slate-200 px-6 py-5">
            <h2 class="text-lg font-semibold text-slate-900">Syarat & Ketentuan</h2>
            <p class="mt-2 text-sm text-slate-600">Sebelum mendaftar, pastikan Anda sudah membaca dan menyetujui syarat & ketentuan.</p>
        </div>
        <div class="space-y-4 px-6 py-5 text-sm text-slate-700">
            <p>
                Dengan mengklik "Setuju dan Daftar", Anda menyetujui
                <a href="{{ route('terms') }}" target="_blank" rel="noopener noreferrer" class="font-semibold text-brand-700 hover:text-brand-800">Syarat & Ketentuan</a>
                kami.
            </p>
            <p class="text-slate-500">Jika ingin memeriksa kembali dokumen sebelum mendaftar, klik "Batal" dan baca syarat terlebih dahulu.</p>
        </div>
        <div class="flex flex-col gap-3 bg-slate-50 px-6 py-4 sm:flex-row sm:justify-end">
            <button
                type="button"
                @click="termsModalOpen = false"
                class="inline-flex justify-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100"
            >
                Batal
            </button>
            <button
                type="button"
                @click="agreeAndSubmit"
                class="inline-flex justify-center rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700"
            >
                Setuju dan Daftar
            </button>
        </div>
    </div>
</div>
