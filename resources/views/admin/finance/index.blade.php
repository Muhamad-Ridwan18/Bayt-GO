@php
    use App\Support\IndonesianNumber;
    use App\Support\PlatformFee;
    use Illuminate\Support\Str;
    $fmt = fn (float|int $n) => IndonesianNumber::formatThousands((string) (int) round((float) $n));
    $tz = config('app.timezone');
    $withdrawStatusLabel = function (?string $s) {
        if ($s === null || $s === '') {
            return '—';
        }
        $key = 'admin.finance.withdraw_status.'.$s;
        $t = __($key);

        return $t === $key ? $s : $t;
    };
@endphp

<x-app-layout>

    <div class="py-8 sm:py-12">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-sm text-slate-600">
                    {{ __('admin.finance.intro', ['pct' => \App\Support\PlatformFee::TOTAL_RATE * 100]) }}
                </p>
                <a href="{{ route('admin.refunds.index') }}" class="inline-flex items-center justify-center rounded-xl border border-amber-200 bg-amber-50 px-4 py-2 text-sm font-semibold text-amber-900 hover:bg-amber-100">
                    {{ __('admin.finance.refund_cta') }}
                </a>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="rounded-2xl border border-brand-200 bg-gradient-to-br from-brand-50 to-white p-6 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-brand-800">{{ __('admin.finance.platform_total') }}</p>
                    <p class="mt-2 text-2xl font-bold text-brand-900">Rp {{ $fmt($totalPlatformFees) }}</p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('admin.finance.gross_volume') }}</p>
                    <p class="mt-2 text-2xl font-bold text-slate-900">Rp {{ $fmt($totalVolume) }}</p>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white overflow-hidden shadow-sm">
                <div class="px-5 py-4 border-b border-slate-100">
                    <h3 class="font-semibold text-slate-900">{{ __('admin.finance.history_title') }}</h3>
                    <p class="mt-1 text-xs text-slate-500">{{ __('admin.finance.history_hint', [
                        'rate' => PlatformFee::RATE * 100,
                        'total' => PlatformFee::TOTAL_RATE * 100,
                    ]) }}</p>
                </div>
                @if ($history->isEmpty())
                    <p class="p-8 text-center text-sm text-slate-500">{{ __('admin.finance.history_empty') }}</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-slate-50 text-left text-xs font-semibold uppercase text-slate-500">
                                <tr>
                                    <th class="px-4 py-3">{{ __('admin.finance.txn_type') }}</th>
                                    <th class="px-4 py-3">{{ __('admin.finance.time') }}</th>
                                    <th class="px-4 py-3">{{ __('admin.finance.reference') }}</th>
                                    <th class="px-4 py-3">{{ __('admin.finance.pilgrim') }}</th>
                                    <th class="px-4 py-3">{{ __('admin.finance.muthowif') }}</th>
                                    <th class="px-4 py-3 text-right">{{ __('admin.finance.gross') }}</th>
                                    <th class="px-4 py-3 text-right">{{ __('admin.finance.fee_customer_side') }}</th>
                                    <th class="px-4 py-3 text-right">{{ __('admin.finance.fee_muthowif_side') }}</th>
                                    <th class="px-4 py-3 text-right">{{ __('admin.finance.fee_total') }}</th>
                                    <th class="px-4 py-3 text-right">{{ __('admin.finance.col_net') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($history as $row)
                                    @if ($row['kind'] === 'order')
                                        @php
                                            /** @var \App\Models\BookingPayment $p */
                                            $p = $row['payment'];
                                            $b = $p->muthowifBooking;
                                            $baseDue = $b !== null ? (float) $b->resolvedAmountDue() : 0.0;
                                            $orderSplit = $baseDue > 0 ? PlatformFee::split($baseDue) : null;
                                            if ($orderSplit === null) {
                                                $pf = (float) $p->platform_fee_amount;
                                                $half = round($pf / 2, 2);
                                                $orderSplit = [
                                                    'customer_fee' => $half,
                                                    'muthowif_fee' => round($pf - $half, 2),
                                                    'platform_fee_total' => $pf,
                                                ];
                                            }
                                        @endphp
                                        <tr class="hover:bg-slate-50/80">
                                            <td class="px-4 py-3 align-top">
                                                <span class="inline-flex items-center rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-semibold text-emerald-900 ring-1 ring-emerald-200/80">{{ __('admin.finance.txn_types.order') }}</span>
                                            </td>
                                            <td class="px-4 py-3 align-top text-slate-600">
                                                <div class="whitespace-nowrap font-medium text-slate-800">{{ $row['at']->timezone($tz)->format('d/m/Y H:i') }}</div>
                                                <div class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">{{ __('admin.finance.time_label_settlement') }}</div>
                                                @if ($p->created_at)
                                                    <div class="mt-1 text-[10px] text-slate-400">
                                                        <span class="font-semibold text-slate-500">{{ __('admin.finance.time_label_payment_created') }}</span>
                                                        {{ $p->created_at->timezone($tz)->format('d/m/Y H:i') }}
                                                    </div>
                                                @endif
                                            </td>
                                            <td class="px-4 py-3 align-top font-mono text-xs text-slate-700 max-w-[12rem] truncate" title="{{ $p->order_id }}">
                                                {{ $p->order_id }}
                                            </td>
                                            <td class="px-4 py-3 align-top text-slate-800">{{ $b?->customer?->name ?? '—' }}</td>
                                            <td class="px-4 py-3 align-top text-slate-800">{{ $b?->muthowifProfile?->user?->name ?? '—' }}</td>
                                            <td class="px-4 py-3 align-top text-right font-medium text-slate-900">Rp {{ $fmt($p->gross_amount) }}</td>
                                            <td class="px-4 py-3 align-top text-right tabular-nums text-slate-800">Rp {{ $fmt((float) $orderSplit['customer_fee']) }}</td>
                                            <td class="px-4 py-3 align-top text-right tabular-nums text-slate-800">Rp {{ $fmt((float) $orderSplit['muthowif_fee']) }}</td>
                                            <td class="px-4 py-3 align-top text-right tabular-nums font-semibold text-brand-800">Rp {{ $fmt((float) $orderSplit['platform_fee_total']) }}</td>
                                            <td class="px-4 py-3 align-top text-right">
                                                <div class="font-medium text-slate-900">Rp {{ $fmt((float) $p->muthowif_net_amount) }}</div>
                                                <div class="text-[10px] text-slate-500">{{ __('admin.finance.to_muthowif') }}</div>
                                            </td>
                                        </tr>
                                    @elseif ($row['kind'] === 'refund')
                                        @php
                                            /** @var \App\Models\BookingRefundRequest $r */
                                            $r = $row['refund'];
                                            $b = $r->muthowifBooking;
                                            $pay = $b?->bookingPayments->first();
                                            $rfPlat = (float) $r->refund_fee_platform;
                                            $rfMu = (float) $r->refund_fee_muthowif;
                                            $rfTotal = $rfPlat + $rfMu;
                                        @endphp
                                        <tr class="hover:bg-slate-50/80">
                                            <td class="px-4 py-3 align-top">
                                                <span class="inline-flex items-center rounded-full bg-amber-50 px-2 py-0.5 text-xs font-semibold text-amber-950 ring-1 ring-amber-200/80">{{ __('admin.finance.txn_types.refund') }}</span>
                                            </td>
                                            <td class="px-4 py-3 align-top text-slate-600">
                                                <div class="whitespace-nowrap font-medium text-slate-800">{{ $row['at']->timezone($tz)->format('d/m/Y H:i') }}</div>
                                                <div class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">{{ __('admin.finance.time_label_refund') }}</div>
                                                @if ($r->created_at)
                                                    <div class="mt-1 text-[10px] text-slate-400">
                                                        <span class="font-semibold text-slate-500">{{ __('admin.finance.refund_requested_at') }}</span>
                                                        {{ $r->created_at->timezone($tz)->format('d/m/Y H:i') }}
                                                    </div>
                                                @endif
                                            </td>
                                            <td class="px-4 py-3 align-top">
                                                <div class="font-mono text-xs text-slate-700">{{ $b?->booking_code ?? '—' }}</div>
                                                @if ($pay?->order_id)
                                                    <div class="mt-0.5 font-mono text-[10px] text-slate-500 truncate max-w-[12rem]" title="{{ $pay->order_id }}">{{ $pay->order_id }}</div>
                                                @endif
                                            </td>
                                            <td class="px-4 py-3 align-top text-slate-800">{{ $b?->customer?->name ?? '—' }}</td>
                                            <td class="px-4 py-3 align-top text-slate-800">{{ $b?->muthowifProfile?->user?->name ?? '—' }}</td>
                                            <td class="px-4 py-3 align-top text-right font-medium text-slate-900">Rp {{ $fmt((float) $r->customer_paid_amount) }}</td>
                                            <td class="px-4 py-3 align-top text-right tabular-nums text-slate-800">Rp {{ $fmt($rfPlat) }}</td>
                                            <td class="px-4 py-3 align-top text-right tabular-nums text-slate-800">Rp {{ $fmt($rfMu) }}</td>
                                            <td class="px-4 py-3 align-top text-right tabular-nums font-semibold text-brand-800">Rp {{ $fmt($rfTotal) }}</td>
                                            <td class="px-4 py-3 align-top text-right">
                                                <div class="font-medium text-slate-900">Rp {{ $fmt((float) $r->net_refund_customer) }}</div>
                                                <div class="text-[10px] text-slate-500">{{ __('admin.finance.net_to_pilgrim') }}</div>
                                            </td>
                                        </tr>
                                    @else
                                        @php
                                            /** @var \App\Models\MuthowifWithdrawal $w */
                                            $w = $row['withdrawal'];
                                            $mu = $w->muthowifProfile;
                                        @endphp
                                        <tr class="hover:bg-slate-50/80">
                                            <td class="px-4 py-3 align-top">
                                                <span class="inline-flex items-center rounded-full bg-violet-50 px-2 py-0.5 text-xs font-semibold text-violet-900 ring-1 ring-violet-200/80">{{ __('admin.finance.txn_types.withdraw') }}</span>
                                            </td>
                                            <td class="px-4 py-3 align-top text-slate-600">
                                                <div class="whitespace-nowrap font-medium text-slate-800">{{ $row['at']->timezone($tz)->format('d/m/Y H:i') }}</div>
                                                <div class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">{{ __('admin.finance.time_label_withdraw') }}</div>
                                                @if ($w->requested_at)
                                                    <div class="mt-1 text-[10px] text-slate-400">
                                                        <span class="font-semibold text-slate-500">{{ __('admin.finance.withdraw_requested_at') }}</span>
                                                        {{ $w->requested_at->timezone($tz)->format('d/m/Y H:i') }}
                                                    </div>
                                                @endif
                                            </td>
                                            <td class="px-4 py-3 align-top text-xs text-slate-700">
                                                <div class="font-mono">{{ Str::limit((string) $w->getKey(), 13, '…') }}</div>
                                                <div class="mt-0.5 text-slate-600">{{ $w->beneficiary_bank }} · {{ $w->beneficiary_account }}</div>
                                            </td>
                                            <td class="px-4 py-3 align-top text-slate-400">—</td>
                                            <td class="px-4 py-3 align-top text-slate-800">{{ $mu?->user?->name ?? '—' }}</td>
                                            <td class="px-4 py-3 align-top text-right font-medium text-slate-900">Rp {{ $fmt((float) $w->amount) }}</td>
                                            <td class="px-4 py-3 align-top text-right text-slate-400">—</td>
                                            <td class="px-4 py-3 align-top text-right text-slate-400">—</td>
                                            <td class="px-4 py-3 align-top text-right text-slate-400">—</td>
                                            <td class="px-4 py-3 align-top text-right">
                                                <div class="text-xs font-medium text-slate-800">{{ $withdrawStatusLabel($w->status) }}</div>
                                                @if ($w->status === 'failed' && filled($w->failed_reason))
                                                    <div class="mt-0.5 text-xs text-red-600">{{ Str::limit($w->failed_reason, 80) }}</div>
                                                @endif
                                            </td>
                                        </tr>
                                    @endif
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="px-4 py-3 border-t border-slate-100">
                        {{ $history->withQueryString()->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
