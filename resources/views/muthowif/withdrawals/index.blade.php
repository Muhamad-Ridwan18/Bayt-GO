<x-app-layout>

    <div class="py-8 sm:py-12">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
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

            @php
                $profile = Auth::user()->muthowifProfile;
                $balance = (float) ($profile->wallet_balance ?? 0);
            @endphp

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex items-start justify-between gap-4 flex-col sm:flex-row">
                    <div>
                        <p class="text-sm text-slate-500">Saldo dompet muthowif</p>
                        <p class="mt-1 text-2xl font-bold text-slate-900 tabular-nums">
                            Rp {{ \App\Support\IndonesianNumber::formatThousands((string) (int) round($balance)) }}
                        </p>
                        <p class="mt-2 text-sm text-slate-600 leading-relaxed">
                            Withdraw akan diproses setelah admin menyetujui dan menyelesaikan transfer manual ke rekening Anda.
                        </p>
                    </div>
                    <div class="w-full sm:w-72 rounded-xl bg-slate-50 border border-slate-200 p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Catatan</p>
                        <p class="mt-2 text-sm text-slate-600 leading-relaxed">
                            Isi `beneficiary_bank` dan `beneficiary_account` sesuai tujuan payout.
                        </p>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="font-semibold text-slate-900">Ajukan withdraw</h3>
                <form method="POST" action="{{ route('muthowif.withdrawals.store') }}" class="mt-4 space-y-4">
                    @csrf

                    <div>
                        <x-input-label for="amount" value="Nominal (Rp)" />
                        <x-indonesian-number-input
                            name="amount"
                            id="amount"
                            required
                            :value="old('amount')"
                            placeholder="Contoh: 1.000.000"
                            :prefix="true"
                        />
                        @error('amount')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <x-input-label for="beneficiary_name" value="Nama penerima" />
                        <input id="beneficiary_name" name="beneficiary_name" type="text" required maxlength="100"
                               class="mt-1 block w-full rounded-xl border border-slate-200 px-4 py-3 text-sm"
                               value="{{ old('beneficiary_name') }}" />
                        @error('beneficiary_name')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="beneficiary_bank" value="Channel code (mis. ID_BCA)" />
                            <input id="beneficiary_bank" name="beneficiary_bank" type="text" required maxlength="64"
                                   class="mt-1 block w-full rounded-xl border border-slate-200 px-4 py-3 text-sm"
                                   value="{{ old('beneficiary_bank') }}" />
                            @error('beneficiary_bank')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <x-input-label for="beneficiary_account" value="Nomor rekening / nomor tujuan" />
                            <input id="beneficiary_account" name="beneficiary_account" type="text" required maxlength="64"
                                   class="mt-1 block w-full rounded-xl border border-slate-200 px-4 py-3 text-sm"
                                   value="{{ old('beneficiary_account') }}" />
                            @error('beneficiary_account')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div>
                        <x-input-label for="notes" value="Catatan (opsional)" />
                        <input id="notes" name="notes" type="text" maxlength="255"
                               class="mt-1 block w-full rounded-xl border border-slate-200 px-4 py-3 text-sm"
                               value="{{ old('notes') }}" />
                        @error('notes')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <button type="submit" class="w-full rounded-xl bg-brand-600 px-6 py-3 text-sm font-semibold text-white hover:bg-brand-700">
                        Kirim permintaan withdraw
                    </button>
                </form>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="font-semibold text-slate-900">Riwayat withdraw</h3>
                <div class="mt-4 overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50 text-left text-xs font-semibold uppercase text-slate-500">
                            <tr>
                                <th class="px-4 py-3 whitespace-nowrap">Waktu</th>
                                <th class="px-4 py-3 whitespace-nowrap">Nominal</th>
                                <th class="px-4 py-3 whitespace-nowrap">Bank / Tujuan</th>
                                <th class="px-4 py-3 whitespace-nowrap">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($withdrawals as $w)
                                <tr class="hover:bg-slate-50/70">
                                    <td class="px-4 py-3 whitespace-nowrap text-slate-600">
                                        {{ $w->requested_at?->format('d/m/Y H:i') ?? '—' }}
                                    </td>
                                    <td class="px-4 py-3 font-medium text-slate-900 whitespace-nowrap">
                                        Rp {{ \App\Support\IndonesianNumber::formatThousands((string) (int) round((float) $w->amount)) }}
                                    </td>
                                    <td class="px-4 py-3 text-slate-800 whitespace-nowrap">
                                        {{ $w->beneficiary_bank }} • {{ $w->beneficiary_account }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        @php
                                            $status = $w->status;
                                            $tagClass = match ($status) {
                                                'pending_approval' => 'bg-orange-50 text-orange-900 ring-orange-200',
                                                'processing' => 'bg-amber-50 text-amber-900 ring-amber-200',
                                                'succeeded' => 'bg-emerald-50 text-emerald-900 ring-emerald-200',
                                                'failed' => 'bg-red-50 text-red-900 ring-red-200',
                                                default => 'bg-slate-50 text-slate-900 ring-slate-200',
                                            };
                                        @endphp
                                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 {{ $tagClass }}">
                                            {{ $w->status }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-6 text-center text-sm text-slate-500">
                                        Belum ada riwayat withdraw.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-4">
                    {{ $withdrawals->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

