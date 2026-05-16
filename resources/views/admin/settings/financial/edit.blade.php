<x-app-layout>
    <div class="py-8 sm:py-12">
        <div class="mx-auto max-w-2xl space-y-6 px-4 sm:px-6 lg:px-8">
            <div class="relative overflow-hidden rounded-3xl border border-slate-200 bg-gradient-to-br from-slate-900 via-brand-900 to-amber-950 p-8 text-white shadow-xl ring-1 ring-white/10">
                <div class="relative">
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-brand-200/90">{{ __('admin.settings_hub.badge') ?? 'PENGATURAN' }}</p>
                    <h1 class="mt-2 text-2xl font-bold tracking-tight">Finansial & Kurs Dolar</h1>
                    <p class="mt-2 max-w-xl text-sm leading-relaxed text-white/80">Atur nilai konversi manual jika seandainya API nilai tukar otomatis (Frankfurter) mengalami kendala atau gagal diakses.</p>
                    <div class="mt-6 flex flex-wrap gap-3">
                        <a href="{{ route('admin.settings.index') }}" class="inline-flex items-center rounded-xl bg-white px-5 py-2.5 text-sm font-semibold text-slate-900 shadow-sm hover:bg-brand-50 transition">
                            Kembali ke Menu
                        </a>
                    </div>
                </div>
            </div>

            @if (session('status'))
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">
                    {{ session('status') }}
                </div>
            @endif
            @if (session('error'))
                <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900">
                    {{ session('error') }}
                </div>
            @endif

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-sm font-semibold text-slate-900">Fallback Nilai Tukar USD ke IDR</h2>
                <p class="mt-1 text-xs leading-relaxed text-slate-500">Nilai ini hanya akan digunakan sistem ketika API kurs pihak ketiga sedang <span class="font-semibold italic">down</span> atau gagal memberikan respon. Masukkan nilai untuk 1 USD (Satu Dollar) ke dalam Rupiah.</p>

                <form method="post" action="{{ route('admin.financial-settings.update') }}" class="mt-6 space-y-5">
                    @csrf
                    @method('PUT')

                    <div>
                        <x-input-label for="fallback_usd_rate" value="1 USD = (Rupiah)" />
                        <div class="relative mt-2">
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                <span class="text-slate-500 sm:text-sm font-medium">Rp</span>
                            </div>
                            <input
                                id="fallback_usd_rate"
                                name="fallback_usd_rate"
                                type="number"
                                min="1000"
                                step="1"
                                value="{{ old('fallback_usd_rate', $fallbackUsdRate) }}"
                                class="block w-full rounded-lg border border-slate-300 bg-slate-50 pl-10 px-3 py-2 text-sm text-slate-900 shadow-sm transition placeholder:text-slate-400 focus:border-brand-500 focus:bg-white focus:ring-brand-500"
                                placeholder="16000"
                                required
                            >
                        </div>
                        <p class="mt-1.5 text-[11px] text-slate-500">Misal: 16000. Jangan gunakan titik atau koma pemisah ribuan.</p>
                        <x-input-error :messages="$errors->get('fallback_usd_rate')" class="mt-2" />
                    </div>

                    <hr class="border-slate-100">

                    <div class="flex flex-wrap gap-3 pt-2">
                        <x-primary-button type="submit">Simpan Kurs</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
