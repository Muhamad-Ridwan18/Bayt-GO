<x-app-layout>
    <div class="ui-page-y">
        <x-page-container class="ui-stack-compact max-w-xl">
            @if (session('status'))
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{{ session('status') }}</div>
            @endif
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <a href="{{ route('admin.affiliates.index') }}" class="text-sm font-semibold text-brand-700">← Affiliate</a>
                <h1 class="mt-2 text-2xl font-bold text-slate-900">Pengaturan Affiliate</h1>
                <p class="mt-1 text-sm text-slate-600">Perubahan hanya berlaku untuk booking baru. Snapshot booking lama tetap.</p>
                <form method="POST" action="{{ route('admin.affiliates.settings.update') }}" class="mt-6 space-y-4">
                    @csrf
                    <div>
                        <x-input-label for="rate_percent" value="Komisi affiliate (%)" />
                        <x-text-input id="rate_percent" name="rate_percent" type="number" step="0.01" min="0.01" max="50"
                                      class="mt-1 block w-full" :value="old('rate_percent', number_format($rate * 100, 2, '.', ''))" required />
                        <p class="mt-1 text-xs text-slate-500">Total platform fee saat ini {{ number_format($platformFeeTotalRate * 100, 2) }}%.</p>
                        <x-input-error :messages="$errors->get('rate_percent')" />
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
