@php
    $balance = (float) ($profile->wallet_balance ?? 0);
@endphp
<div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
    <div class="flex items-start justify-between gap-4 flex-col sm:flex-row">
        <div>
            <p class="text-sm text-slate-500">Saldo dompet muthowif</p>
            <p class="mt-1 text-2xl font-bold text-slate-900 tabular-nums">
                Rp {{ \App\Support\IndonesianNumber::formatThousands((string) (int) round($balance)) }}
            </p>
        </div>
    </div>
</div>

<div class="mt-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
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
                        <td class="px-4 py-3 whitespace-nowrap text-slate-600">{{ $w->requested_at?->format('d/m/Y H:i') ?? '—' }}</td>
                        <td class="px-4 py-3 font-medium text-slate-900 whitespace-nowrap">
                            Rp {{ \App\Support\IndonesianNumber::formatThousands((string) (int) round((float) $w->amount)) }}
                        </td>
                        <td class="px-4 py-3 text-slate-800 whitespace-nowrap">{{ $w->beneficiary_bank }} • {{ $w->beneficiary_account }}</td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            @php
                                $tagClass = match ($w->status) {
                                    'pending_approval' => 'bg-orange-50 text-orange-900 ring-orange-200',
                                    'processing' => 'bg-amber-50 text-amber-900 ring-amber-200',
                                    'succeeded' => 'bg-emerald-50 text-emerald-900 ring-emerald-200',
                                    'failed' => 'bg-red-50 text-red-900 ring-red-200',
                                    default => 'bg-slate-50 text-slate-900 ring-slate-200',
                                };
                            @endphp
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 {{ $tagClass }}">{{ $w->status }}</span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-6 text-center text-sm text-slate-500">Belum ada riwayat withdraw.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $withdrawals->links() }}</div>
</div>
