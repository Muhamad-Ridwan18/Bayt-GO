<x-app-layout>
    <div class="ui-page-y">
        <x-page-container class="ui-stack-compact max-w-2xl">
            <div class="rounded-3xl border border-slate-200 bg-white p-8 shadow-sm">
                <p class="text-[11px] font-semibold uppercase tracking-wider text-brand-700">Affiliate BaytGo</p>
                <h1 class="mt-2 text-2xl font-bold text-slate-900">Gabung jadi Affiliate</h1>
                <p class="mt-2 text-sm text-slate-600">Bagikan kode affiliate Anda dan dapatkan komisi dari setiap booking yang selesai. Komisi diambil dari platform fee BaytGo.</p>

                @php
                    $tiers = \App\Support\AffiliateSettings::getTiers();
                    $fmtTier = fn (float|int $n) => \App\Support\IndonesianNumber::formatThousands((string) (int) round((float) $n));
                @endphp
                <div class="mt-5 rounded-2xl border border-emerald-100 bg-emerald-50/50 p-4">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-emerald-700">Level & rate komisi</p>
                    <div class="mt-3 grid gap-2 sm:grid-cols-3">
                        @foreach ($tiers as $tier)
                            @php $ratePct = rtrim(rtrim(number_format($tier['rate'] * 100, 2, '.', ''), '0'), '.'); @endphp
                            <div class="rounded-xl bg-white px-3 py-2.5 ring-1 ring-emerald-100">
                                <p class="text-sm font-bold text-slate-900">Level {{ $tier['level'] }}</p>
                                <p class="text-lg font-bold tabular-nums text-emerald-700">{{ $ratePct }}%</p>
                                <p class="mt-0.5 text-[11px] text-slate-500">
                                    @if ($tier['min'] > 0)
                                        Omzet ≥ Rp {{ $fmtTier($tier['min']) }}
                                    @else
                                        Level awal
                                    @endif
                                </p>
                            </div>
                        @endforeach
                    </div>
                    <p class="mt-3 text-xs text-slate-500">Level naik otomatis berdasarkan total omzet booking beratribusi. Progress bar tampil di dashboard setelah aktif.</p>
                </div>

                <form method="POST" action="{{ route('affiliate.register') }}" class="mt-6 space-y-4">
                    @csrf
                    <div>
                        <x-input-label for="code" value="Kode Affiliate (opsional)" />
                        <x-text-input id="code" name="code" type="text" class="mt-1 block w-full font-mono uppercase"
                                      :value="old('code')" maxlength="32" autocomplete="off" placeholder="Contoh: RIDWAN" />
                        <p class="mt-1 text-xs text-slate-500">Kosongkan untuk generate otomatis.</p>
                        <x-input-error class="mt-2" :messages="$errors->get('code')" />
                        <x-input-error class="mt-2" :messages="$errors->get('affiliate')" />
                    </div>
                    <x-submit-button class="w-full rounded-xl bg-baytgo px-5 py-3 text-sm font-semibold text-white">
                        Aktifkan Affiliate
                    </x-submit-button>
                </form>
            </div>
        </x-page-container>
    </div>
</x-app-layout>
