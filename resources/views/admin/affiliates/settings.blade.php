<x-app-layout>
    <div class="ui-page-y">
        <x-page-container class="ui-stack-compact max-w-xl">
            @if (session('status'))
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{{ session('status') }}</div>
            @endif
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <a href="{{ route('admin.affiliates.index') }}" class="text-sm font-semibold text-brand-700">← Affiliate</a>
                <h1 class="mt-2 text-2xl font-bold text-slate-900">Pengaturan Affiliate</h1>
                <p class="mt-1 text-sm text-slate-600">Level berdasarkan total base transaksi booking (pending + available). Perubahan hanya berlaku untuk booking baru.</p>
                <form method="POST" action="{{ route('admin.affiliates.settings.update') }}" class="mt-6 space-y-5">
                    @csrf

                    <div class="space-y-4">
                        <p class="text-sm font-semibold text-slate-800">Level komisi</p>
                        @foreach ($tiers as $i => $tier)
                            <div class="rounded-xl border border-slate-200 bg-slate-50/80 p-4">
                                <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Level {{ $i + 1 }}</p>
                                <div class="mt-3 grid gap-3 sm:grid-cols-2">
                                    <div>
                                        <x-input-label :for="'tier_min_'.$i" value="Omzet minimal (Rp)" />
                                        <x-text-input
                                            :id="'tier_min_'.$i"
                                            :name="'tiers['.$i.'][min]'"
                                            type="number"
                                            step="1000"
                                            min="0"
                                            class="mt-1 block w-full"
                                            :value="old('tiers.'.$i.'.min', (int) $tier['min'])"
                                            :required="$i === 0 ? false : true"
                                            :readonly="$i === 0"
                                        />
                                        @if ($i === 0)
                                            <p class="mt-1 text-xs text-slate-500">Level 1 selalu mulai dari 0.</p>
                                        @endif
                                        <x-input-error :messages="$errors->get('tiers.'.$i.'.min')" />
                                    </div>
                                    <div>
                                        <x-input-label :for="'tier_rate_'.$i" value="Komisi (%)" />
                                        <x-text-input
                                            :id="'tier_rate_'.$i"
                                            :name="'tiers['.$i.'][rate_percent]'"
                                            type="number"
                                            step="0.01"
                                            min="0.01"
                                            max="50"
                                            class="mt-1 block w-full"
                                            :value="old('tiers.'.$i.'.rate_percent', number_format($tier['rate'] * 100, 2, '.', ''))"
                                            required
                                        />
                                        <x-input-error :messages="$errors->get('tiers.'.$i.'.rate_percent')" />
                                    </div>
                                </div>
                            </div>
                        @endforeach
                        <p class="text-xs text-slate-500">Total platform fee saat ini {{ number_format($platformFeeTotalRate * 100, 2) }}%. Rate tiap level tidak boleh melebihi itu.</p>
                        <x-input-error :messages="$errors->get('tiers')" />
                    </div>

                    <div>
                        <x-input-label for="min_withdraw" value="Minimal withdraw (Rp)" />
                        <x-text-input id="min_withdraw" name="min_withdraw" type="number" min="1000" step="1000"
                                      class="mt-1 block w-full" :value="old('min_withdraw', (int) $minWithdraw)" required />
                        <x-input-error :messages="$errors->get('min_withdraw')" />
                    </div>
                    <x-submit-button>Simpan</x-submit-button>
                </form>
            </div>
        </x-page-container>
    </div>
</x-app-layout>
