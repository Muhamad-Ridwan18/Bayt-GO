<?php

namespace App\Services\Emergency;

use App\Models\BookingPayment;
use App\Models\MuthowifBooking;

final class EmergencySettlementCalculator
{
    /**
     * @return array{replacement_amount: float, retained_by_platform: float, snapshot: array<string, mixed>}
     */
    public function replacementPayoutOnCompletion(MuthowifBooking $booking, BookingPayment $payment): array
    {
        $totalDays = max(1, $booking->billingNightsInclusive());
        $replacementDays = max(0, $this->remainingServiceDays($booking));
        $pool = round(
            (float) $payment->muthowif_net_amount - (float) ($payment->referral_reward_amount ?? 0),
            2
        );

        $replacementAmount = round($pool * ($replacementDays / $totalDays), 2);
        $retained = round($pool - $replacementAmount, 2);

        return [
            'replacement_amount' => $replacementAmount,
            'retained_by_platform' => $retained,
            'snapshot' => [
                'pool' => $pool,
                'total_days' => $totalDays,
                'replacement_days' => $replacementDays,
                'primary_amount' => 0.0,
                'payment_id' => $payment->getKey(),
            ],
        ];
    }

    public function remainingServiceDays(MuthowifBooking $booking): int
    {
        if ($booking->emergency_replacement_at === null) {
            return $booking->billingNightsInclusive();
        }

        $total = $booking->billingNightsInclusive();
        $from = $booking->emergency_replacement_at->copy()->startOfDay();
        $end = $booking->ends_on->copy()->startOfDay();

        if ($from->gt($end)) {
            return 0;
        }

        return (int) max(0, $from->diffInDays($end) + 1);
    }
}
