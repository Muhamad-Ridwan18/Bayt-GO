@php
    use App\Support\IndonesianNumber;
    $fmt = fn (float|int $n) => IndonesianNumber::formatThousands((string) (int) round((float) $n));
@endphp

<x-app-layout>
    <div class="py-8 sm:py-12">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">Refund menunggu transfer manual</h2>
                    <p class="mt-1 text-sm text-slate-600">Jamaah sudah mengajukan refund; transfer nominal bersih ke rekening jamaah lalu tandai selesai.</p>
                </div>
                <a href="{{ route('admin.finance.index') }}" class="text-sm font-semibold text-brand-700 hover:text-brand-800">← Keuangan</a>
            </div>

            @if (session('status'))
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{{ session('status') }}</div>
            @endif
            @if (session('error'))
                <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900">{{ session('error') }}</div>
            @endif

            @if ($pendingRefunds->isEmpty())
                <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50/80 p-10 text-center text-slate-600 text-sm">
                    Tidak ada refund yang menunggu transfer.
                </div>
            @else
                <div class="rounded-2xl border border-slate-200 bg-white overflow-hidden shadow-sm">
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-slate-50 text-left text-xs font-semibold uppercase text-slate-500">
                                <tr>
                                    <th class="px-4 py-3">Diajukan</th>
                                    <th class="px-4 py-3">Jamaah</th>
                                    <th class="px-4 py-3">Muthowif</th>
                                    <th class="px-4 py-3 text-right">Net refund</th>
                                    <th class="px-4 py-3">Catatan jamaah</th>
                                    <th class="px-4 py-3 w-48"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($pendingRefunds as $refund)
                                    @php
                                        $booking = $refund->muthowifBooking;
                                    @endphp
                                    <tr class="align-top hover:bg-slate-50/80">
                                        <td class="px-4 py-3 text-slate-700 whitespace-nowrap">
                                            {{ $refund->created_at?->timezone(config('app.timezone'))->format('d/m/Y H:i') }}
                                        </td>
                                        <td class="px-4 py-3">
                                            <p class="font-medium text-slate-900">{{ $refund->customer->name }}</p>
                                            <p class="text-xs text-slate-500">{{ $refund->customer->email }}</p>
                                        </td>
                                        <td class="px-4 py-3">
                                            {{ $booking?->muthowifProfile?->user?->name ?? '—' }}
                                        </td>
                                        <td class="px-4 py-3 text-right font-semibold text-slate-900">
                                            Rp {{ $fmt((float) $refund->net_refund_customer) }}
                                        </td>
                                        <td class="px-4 py-3 text-slate-600 text-xs max-w-xs">
                                            {{ $refund->customer_note ?: '—' }}
                                        </td>
                                        <td class="px-4 py-3">
                                            <form method="POST" action="{{ route('admin.refunds.complete', $refund) }}" class="space-y-2" onsubmit="return confirm('Tandai transfer refund ke jamaah sudah selesai?');">
                                                @csrf
                                                <input type="text" name="admin_note" class="w-full rounded-lg border-slate-300 text-xs" placeholder="Catatan internal (opsional)" value="{{ old('admin_note') }}">
                                                <button type="submit" class="w-full rounded-lg bg-emerald-600 px-3 py-2 text-xs font-semibold text-white hover:bg-emerald-700">
                                                    Tandai transfer selesai
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="mt-4">
                    {{ $pendingRefunds->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
