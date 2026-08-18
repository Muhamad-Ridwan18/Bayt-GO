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

<div
    class="mt-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"
    x-data="{
        proofOpen: false,
        proofUrl: '',
        proofKind: 'image',
        openProof(url, kind) {
            this.proofUrl = url;
            this.proofKind = kind;
            this.proofOpen = true;
        },
        closeProof() {
            this.proofOpen = false;
            this.proofUrl = '';
        },
    }"
    @keydown.escape.window="if (proofOpen) closeProof()"
>
    <h3 class="font-semibold text-slate-900">Riwayat withdraw</h3>
    <div class="mt-4 overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-left text-xs font-semibold uppercase text-slate-500">
                <tr>
                    <th class="px-4 py-3 whitespace-nowrap">Waktu</th>
                    <th class="px-4 py-3 whitespace-nowrap">Nominal</th>
                    <th class="px-4 py-3 whitespace-nowrap">Bank / Tujuan</th>
                    <th class="px-4 py-3 whitespace-nowrap">Status</th>
                    <th class="px-4 py-3 whitespace-nowrap text-right">Bukti Transfer</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($withdrawals as $w)
                    @php
                        $proofPath = filled($w->transfer_proof_path) ? (string) $w->transfer_proof_path : null;
                        $proofUrl = $proofPath ? asset('storage/'.$proofPath) : null;
                        $proofExt = $proofPath ? strtolower((string) pathinfo($proofPath, PATHINFO_EXTENSION)) : '';
                        $proofKind = $proofExt === 'pdf' ? 'pdf' : 'image';
                    @endphp
                    <tr class="hover:bg-slate-50/70">
                        <td class="px-4 py-3 whitespace-nowrap text-slate-600">{{ $w->requested_at?->format('d/m/Y H:i') ?? '—' }}</td>
                        <td class="px-4 py-3 font-medium text-slate-900 whitespace-nowrap">
                            Rp {{ \App\Support\IndonesianNumber::formatThousands((string) (int) round((float) $w->amount)) }}
                        </td>
                        <td class="px-4 py-3 text-slate-800 whitespace-nowrap">
                            <div class="inline-flex items-center gap-2">
                                <x-bank-logo :code="$w->beneficiary_bank" size="xs" />
                                <span>{{ $w->beneficiary_bank }} • {{ $w->beneficiary_account }}</span>
                            </div>
                        </td>
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
                        <td class="px-4 py-3 whitespace-nowrap text-right">
                            @if ($proofUrl)
                                <button
                                    type="button"
                                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-500 ring-1 ring-slate-200 transition hover:bg-slate-50 hover:text-brand-700"
                                    @click="openProof(@js($proofUrl), @js($proofKind))"
                                    title="Lihat bukti transfer"
                                    aria-label="Lihat bukti transfer"
                                >
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                    </svg>
                                </button>
                            @else
                                <span class="text-xs text-slate-300">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-6 text-center text-sm text-slate-500">Belum ada riwayat withdraw.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $withdrawals->links() }}</div>

    <div
        x-show="proofOpen"
        x-cloak
        class="fixed inset-0 z-[100] flex items-center justify-center p-4 sm:p-6"
        role="dialog"
        aria-modal="true"
        aria-label="Bukti transfer"
    >
        <div class="absolute inset-0 bg-slate-900/70 backdrop-blur-[2px]" @click="closeProof()"></div>
        <div class="relative z-10 flex max-h-[92vh] w-full max-w-3xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-slate-200/90" @click.stop>
            <div class="flex shrink-0 items-center justify-between gap-3 border-b border-slate-100 px-4 py-3 sm:px-5">
                <h4 class="min-w-0 truncate text-sm font-bold text-slate-900">Bukti transfer</h4>
                <button
                    type="button"
                    class="shrink-0 rounded-lg p-1.5 text-slate-500 transition hover:bg-slate-100 hover:text-slate-800"
                    @click="closeProof()"
                    aria-label="Tutup"
                >
                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z" /></svg>
                </button>
            </div>
            <div class="min-h-0 flex-1 overflow-auto bg-slate-50/80 p-4 sm:p-5">
                <img
                    x-show="proofKind === 'image'"
                    x-cloak
                    :src="proofUrl"
                    alt="Bukti transfer"
                    class="mx-auto block h-auto max-h-[min(75vh,36rem)] w-full object-contain"
                />
                <iframe
                    x-show="proofKind === 'pdf'"
                    x-cloak
                    :src="proofUrl"
                    title="Bukti transfer"
                    class="mx-auto block h-[min(75vh,36rem)] w-full rounded-xl border border-slate-200 bg-white shadow-sm"
                ></iframe>
            </div>
            <div class="flex shrink-0 justify-end gap-2 border-t border-slate-100 px-4 py-3 sm:px-5">
                <a
                    x-show="proofUrl"
                    :href="proofUrl"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50"
                >
                    Buka di tab baru
                </a>
            </div>
        </div>
    </div>
</div>
