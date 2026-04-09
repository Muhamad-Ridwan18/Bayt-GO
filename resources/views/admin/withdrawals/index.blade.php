<x-app-layout>
    <div class="py-8 sm:py-12">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
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

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="font-semibold text-slate-900">Ringkasan</h3>
                <div class="mt-3 grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div class="rounded-xl bg-slate-50 border border-slate-200 p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Pending count</p>
                        <p class="mt-2 text-2xl font-bold text-slate-900 tabular-nums">{{ $pendingCount }}</p>
                    </div>
                    <div class="rounded-xl bg-slate-50 border border-slate-200 p-4 sm:col-span-2">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Pending amount</p>
                        <p class="mt-2 text-2xl font-bold text-slate-900 tabular-nums">
                            Rp {{ \App\Support\IndonesianNumber::formatThousands((string) $pendingAmount) }}
                        </p>
                        <p class="mt-1 text-xs text-slate-600">Menunggu admin menyetujui withdraw; setelah itu saldo didebit dan admin menyelesaikan transfer manual ke bank, lalu menandai selesai.</p>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="font-semibold text-slate-900">Daftar withdraw</h3>
                <div class="mt-4 overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50 text-left text-xs font-semibold uppercase text-slate-500">
                            <tr>
                                <th class="px-4 py-3 whitespace-nowrap">Waktu</th>
                                <th class="px-4 py-3 whitespace-nowrap">Muthowif</th>
                                <th class="px-4 py-3 whitespace-nowrap">Nominal</th>
                                <th class="px-4 py-3 whitespace-nowrap">Tujuan</th>
                                <th class="px-4 py-3 whitespace-nowrap">Status</th>
                                <th class="px-4 py-3 text-right whitespace-nowrap">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($withdrawals as $w)
                                @php
                                    $profile = $w->muthowifProfile;
                                    $user = $profile?->user;
                                    $tagClass = match ($w->status) {
                                        'pending_approval' => 'bg-orange-50 text-orange-900 ring-orange-200',
                                        'processing' => 'bg-amber-50 text-amber-900 ring-amber-200',
                                        'succeeded' => 'bg-emerald-50 text-emerald-900 ring-emerald-200',
                                        'failed' => 'bg-red-50 text-red-900 ring-red-200',
                                        default => 'bg-slate-50 text-slate-900 ring-slate-200',
                                    };
                                @endphp
                                <tr class="hover:bg-slate-50/60">
                                    <td class="px-4 py-3 whitespace-nowrap text-slate-600">
                                        {{ $w->requested_at?->format('d/m/Y H:i') ?? '—' }}
                                    </td>
                                    <td class="px-4 py-3 text-slate-800 whitespace-nowrap">
                                        {{ $user?->name ?? '—' }}
                                    </td>
                                    <td class="px-4 py-3 font-medium text-slate-900 whitespace-nowrap">
                                        Rp {{ \App\Support\IndonesianNumber::formatThousands((string) $w->amount) }}
                                    </td>
                                    <td class="px-4 py-3 text-slate-800 whitespace-nowrap">
                                        {{ $w->beneficiary_bank }} • {{ $w->beneficiary_account }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 {{ $tagClass }}">
                                            {{ $w->status }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right whitespace-nowrap">
                                        @if ($w->status === 'pending_approval')
                                            <form method="POST" action="{{ route('admin.withdrawals.approve', $w) }}" onsubmit="return confirm('Setujui withdraw ini? Saldo muthowif akan didebit; Anda perlu mentransfer dana ke rekening tujuan lalu menandai selesai.');">
                                                @csrf
                                                <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-brand-600 px-3 py-2 text-sm font-semibold text-white hover:bg-brand-700">
                                                    Approve
                                                </button>
                                            </form>
                                        @elseif ($w->status === 'processing')
                                            <div class="flex flex-col items-end gap-2">
                                                <form method="POST" action="{{ route('admin.withdrawals.mark_transferred', $w) }}" onsubmit="return confirm('Tandai transfer ke rekening muthowif sudah selesai?');">
                                                    @csrf
                                                    <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-emerald-600 px-3 py-2 text-sm font-semibold text-white hover:bg-emerald-700">
                                                        Tandai transfer selesai
                                                    </button>
                                                </form>
                                                <form method="POST" action="{{ route('admin.withdrawals.mark_transfer_failed', $w) }}" onsubmit="return confirm('Transfer gagal? Saldo akan dikembalikan ke wallet muthowif.');">
                                                    @csrf
                                                    <button type="submit" class="text-xs font-semibold text-red-700 hover:text-red-900 underline">
                                                        Tandai gagal (kembalikan saldo)
                                                    </button>
                                                </form>
                                            </div>
                                        @else
                                            <span class="text-xs text-slate-500">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-6 text-center text-sm text-slate-500">
                                        Belum ada data withdraw.
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

