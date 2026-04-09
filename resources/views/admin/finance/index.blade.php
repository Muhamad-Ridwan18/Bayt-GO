@php
    use App\Support\IndonesianNumber;
    $fmt = fn (float|int $n) => IndonesianNumber::formatThousands((string) (int) round((float) $n));
@endphp

<x-app-layout>

    <div class="py-8 sm:py-12">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
            <p class="text-sm text-slate-600">
                Ringkasan biaya platform ({{ \App\Support\PlatformFee::TOTAL_RATE * 100 }}% total) dan riwayat transaksi Midtrans untuk pembayaran yang sudah settlement.
            </p>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="rounded-2xl border border-brand-200 bg-gradient-to-br from-brand-50 to-white p-6 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-brand-800">Total biaya platform terkumpul</p>
                    <p class="mt-2 text-2xl font-bold text-brand-900">Rp {{ $fmt($totalPlatformFees) }}</p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Volume bruto (jamaah)</p>
                    <p class="mt-2 text-2xl font-bold text-slate-900">Rp {{ $fmt($totalVolume) }}</p>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white overflow-hidden shadow-sm">
                <div class="px-5 py-4 border-b border-slate-100">
                    <h3 class="font-semibold text-slate-900">Transaksi</h3>
                </div>
                @if ($payments->isEmpty())
                    <p class="p-8 text-center text-sm text-slate-500">Belum ada pembayaran yang settlement.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-slate-50 text-left text-xs font-semibold uppercase text-slate-500">
                                <tr>
                                    <th class="px-4 py-3">Waktu</th>
                                    <th class="px-4 py-3">Order ID</th>
                                    <th class="px-4 py-3">Jamaah</th>
                                    <th class="px-4 py-3">Muthowif</th>
                                    <th class="px-4 py-3 text-right">Bruto</th>
                                    <th class="px-4 py-3 text-right">Fee {{ \App\Support\PlatformFee::TOTAL_RATE * 100 }}%</th>
                                    <th class="px-4 py-3 text-right">Ke muthowif</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($payments as $p)
                                    @php
                                        $b = $p->muthowifBooking;
                                    @endphp
                                    <tr class="hover:bg-slate-50/80">
                                        <td class="px-4 py-3 whitespace-nowrap text-slate-600">
                                            {{ $p->settled_at?->format('d/m/Y H:i') ?? '—' }}
                                        </td>
                                        <td class="px-4 py-3 font-mono text-xs text-slate-700 max-w-[10rem] truncate" title="{{ $p->order_id }}">
                                            {{ $p->order_id }}
                                        </td>
                                        <td class="px-4 py-3 text-slate-800">{{ $b?->customer?->name ?? '—' }}</td>
                                        <td class="px-4 py-3 text-slate-800">{{ $b?->muthowifProfile?->user?->name ?? '—' }}</td>
                                        <td class="px-4 py-3 text-right font-medium">Rp {{ $fmt($p->gross_amount) }}</td>
                                        <td class="px-4 py-3 text-right text-brand-800 font-medium">Rp {{ $fmt((float) $p->platform_fee_amount) }}</td>
                                        <td class="px-4 py-3 text-right text-slate-600">Rp {{ $fmt((float) $p->muthowif_net_amount) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="px-4 py-3 border-t border-slate-100">
                        {{ $payments->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
