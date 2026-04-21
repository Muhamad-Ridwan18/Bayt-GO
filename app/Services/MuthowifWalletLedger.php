<?php

namespace App\Services;

use App\Enums\BookingChangeRequestStatus;
use App\Models\BookingPayment;
use App\Models\BookingRefundRequest;
use App\Models\MuthowifProfile;
use App\Models\MuthowifWithdrawal;
use Illuminate\Support\Collection;

final class MuthowifWalletLedger
{
    /**
     * @return Collection<int, array{
     *     kind: 'booking_credit'|'withdraw_debit'|'withdraw_refund'|'refund_completed',
     *     signed_amount: float,
     *     at: \Carbon\CarbonInterface,
     *     tie: string,
     *     booking?: \App\Models\MuthowifBooking,
     *     withdrawal?: MuthowifWithdrawal,
     *     refund?: BookingRefundRequest
     * }>
     */
    public static function entriesForProfile(MuthowifProfile $profile): Collection
    {
        $out = Collection::make();

        $payments = BookingPayment::query()
            ->whereNotNull('wallet_credited_at')
            ->whereHas('muthowifBooking', static function ($q) use ($profile): void {
                $q->where('muthowif_profile_id', $profile->getKey());
            })
            ->with(['muthowifBooking:id,booking_code,muthowif_profile_id'])
            ->get();

        foreach ($payments as $payment) {
            $at = $payment->wallet_credited_at;
            if ($at === null) {
                continue;
            }
            $out->push([
                'kind' => 'booking_credit',
                'signed_amount' => round((float) $payment->muthowif_net_amount, 2),
                'at' => $at,
                'tie' => 'p:'.$payment->getKey(),
                'booking' => $payment->muthowifBooking,
                'withdrawal' => null,
                'refund' => null,
            ]);
        }

        $withdrawals = MuthowifWithdrawal::query()
            ->where('muthowif_profile_id', $profile->getKey())
            ->get();

        foreach ($withdrawals as $w) {
            if ($w->approved_at !== null) {
                $out->push([
                    'kind' => 'withdraw_debit',
                    'signed_amount' => -1 * round((float) $w->amount, 2),
                    'at' => $w->approved_at,
                    'tie' => 'w:'.$w->getKey().':d',
                    'booking' => null,
                    'withdrawal' => $w,
                    'refund' => null,
                ]);
            }

            if ($w->status === 'failed' && $w->approved_at !== null && $w->failed_at !== null) {
                $out->push([
                    'kind' => 'withdraw_refund',
                    'signed_amount' => round((float) $w->amount, 2),
                    'at' => $w->failed_at,
                    'tie' => 'w:'.$w->getKey().':r',
                    'booking' => null,
                    'withdrawal' => $w,
                    'refund' => null,
                ]);
            }
        }

        $refunds = BookingRefundRequest::query()
            ->where('status', BookingChangeRequestStatus::Approved)
            ->whereNotNull('decided_at')
            ->whereHas('muthowifBooking', static function ($q) use ($profile): void {
                $q->where('muthowif_profile_id', $profile->getKey());
            })
            ->with([
                'muthowifBooking' => static function ($q): void {
                    $q->select(['id', 'booking_code', 'muthowif_profile_id']);
                },
            ])
            ->get();

        foreach ($refunds as $refund) {
            $booking = $refund->muthowifBooking;
            $at = $refund->decided_at;
            if ($booking === null || $at === null) {
                continue;
            }

            $feeMu = (int) $refund->refund_fee_muthowif;
            $signed = $feeMu > 0 ? -1 * (float) $feeMu : 0.0;

            $out->push([
                'kind' => 'refund_completed',
                'signed_amount' => $signed,
                'at' => $at,
                'tie' => 'r:'.$refund->getKey(),
                'booking' => $booking,
                'withdrawal' => null,
                'refund' => $refund,
            ]);
        }

        return $out
            ->sort(static function (array $a, array $b): int {
                $cmp = $b['at']->getTimestamp() <=> $a['at']->getTimestamp();
                if ($cmp !== 0) {
                    return $cmp;
                }

                return strcmp((string) $a['tie'], (string) $b['tie']);
            })
            ->values();
    }
}
