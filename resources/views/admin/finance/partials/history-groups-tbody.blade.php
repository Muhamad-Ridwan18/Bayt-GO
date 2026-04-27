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
@foreach ($groups as $group)
    <tr class="bg-slate-100/95">
        <td colspan="10" class="px-4 py-2.5 text-xs font-bold tracking-wide text-slate-800">
            @if ($group['is_withdraw_group'])
                <span class="text-sm text-slate-900">{{ $group['display_label'] }}</span>
            @else
                <span class="uppercase text-[10px] text-slate-500">{{ __('admin.finance.booking_code') }}</span>
                <span class="ml-2 font-mono text-sm text-slate-900">{{ $group['display_label'] }}</span>
            @endif
            <span class="ml-2 font-normal text-slate-500">({{ __('admin.finance.history_group_rows', ['count' => $group['rows']->count()]) }})</span>
        </td>
    </tr>
    @foreach ($group['rows'] as $row)
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
                <td class="px-4 py-3 align-top font-mono text-xs text-slate-700 max-w-[14rem] truncate" title="{{ filled($p->order_id) ? __('admin.finance.reference_payment_order', ['order' => $p->order_id]) : '' }}">
                    {{ filled($b?->booking_code) ? $b->booking_code : ($p->order_id ?? '—') }}
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
                $pay = $b?->latestSettledBookingPayment;
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
                <td class="px-4 py-3 align-top font-mono text-xs text-slate-700 max-w-[14rem] truncate" title="{{ filled($pay?->order_id) ? __('admin.finance.reference_payment_order', ['order' => $pay->order_id]) : '' }}">
                    {{ filled($b?->booking_code) ? $b->booking_code : ($pay?->order_id ?? '—') }}
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
@endforeach
