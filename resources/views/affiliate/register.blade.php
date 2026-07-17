<x-app-layout>
    <div class="ui-page-y">
        <x-page-container class="ui-stack-compact max-w-2xl">
            <div class="rounded-3xl border border-slate-200 bg-white p-8 shadow-sm">
                <p class="text-[11px] font-semibold uppercase tracking-wider text-brand-700">Affiliate BaytGo</p>
                <h1 class="mt-2 text-2xl font-bold text-slate-900">Gabung jadi Affiliate</h1>
                <p class="mt-2 text-sm text-slate-600">Bagikan kode affiliate Anda dan dapatkan komisi dari setiap booking yang selesai. Komisi diambil dari platform fee BaytGo.</p>

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
