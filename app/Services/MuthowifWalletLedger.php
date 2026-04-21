<?php

namespace App\Services;

use App\Models\BookingPayment;
use App\Models\MuthowifProfile;
use App\Models\MuthowifWithdrawal;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

final class MuthowifWalletLedger
{
    /**
     * @return Collection<int, array{
     *     kind: 'booking_credit'|'withdraw_debit'|'withdraw_refund',
     *     signed_amount: float,
     *     at: CarbonInterface,
     *     tie: string,
     *     booking?: \App\Models\MuthowifBooking,
     *     withdrawal?: MuthowifWithdrawal
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
                ]);
            }
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
