@props(['page'])

<div
    class="scroll-smooth"
    x-data="{ showServicePrompt: {{ $page->hasServices ? 'false' : 'true' }} }"
    x-init="if (showServicePrompt) { $nextTick(() => { $refs.serviceBtn?.focus(); }); }"
>
    @if ($page->missingWorkLocation)
        <x-ui.alert type="warning" class="mb-6">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="font-semibold">{{ __('dashboard_muthowif.work_location_alert_title') }}</p>
                    <p class="mt-1 text-sm opacity-90">{{ __('dashboard_muthowif.work_location_alert_body') }}</p>
                </div>
                <a href="{{ route('profile.edit') }}#public_work_location" class="inline-flex shrink-0 items-center justify-center rounded-xl bg-amber-900/90 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-amber-950">
                    {{ __('dashboard_muthowif.work_location_alert_cta') }}
                </a>
            </div>
        </x-ui.alert>
    @endif

    <div
        x-show="showServicePrompt"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 px-4 py-6 sm:px-6"
        role="dialog"
        aria-modal="true"
    >
        <div class="w-full max-w-xl overflow-hidden rounded-[2rem] border border-slate-200 bg-white p-6 shadow-2xl shadow-slate-900/10">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-[0.2em] text-baytgo">Aksi penting</p>
                    <h2 class="mt-3 text-2xl font-bold text-slate-900">Lengkapi layanan muthowif Anda</h2>
                    <p class="mt-3 text-sm leading-relaxed text-slate-600">Karena Anda belum menambahkan layanan, kami sarankan untuk langsung atur layanan agar profil Anda siap menerima permintaan booking.</p>
                </div>
                <button
                    type="button"
                    class="rounded-full border border-slate-200 bg-slate-100 p-2 text-slate-600 transition hover:bg-slate-200"
                    @click="showServicePrompt = false"
                    aria-label="Tutup pemberitahuan"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M4.28 4.28a.75.75 0 011.06 0L10 8.94l4.66-4.66a.75.75 0 111.06 1.06L11.06 10l4.66 4.66a.75.75 0 11-1.06 1.06L10 11.06l-4.66 4.66a.75.75 0 01-1.06-1.06L8.94 10 4.28 5.34a.75.75 0 010-1.06z" clip-rule="evenodd" />
                    </svg>
                </button>
            </div>
            <div class="mt-6 grid gap-4 sm:grid-cols-[1fr_auto] sm:items-center">
                <div class="space-y-2">
                    <p class="text-sm text-slate-700">Tambahkan layanan harian Anda sekarang sehingga daftar layanan bisa tampil ke jamaah dan muthowif bisa mulai menerima booking.</p>
                    <p class="text-xs text-slate-500">Anda bisa mengatur layanan group dan private secara terpisah di halaman layanan.</p>
                </div>
                <a
                    href="{{ route('muthowif.kelola-layanan') }}"
                    x-ref="serviceBtn"
                    class="inline-flex items-center justify-center rounded-2xl bg-baytgo px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-baytgo/15 transition hover:bg-baytgo-800"
                >
                    Atur layanan sekarang
                </a>
            </div>
        </div>
    </div>

    @include('partials.dashboard-muthowif-layout', ['page' => $page])
</div>
