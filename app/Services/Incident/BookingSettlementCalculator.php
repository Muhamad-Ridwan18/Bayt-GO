<?php

namespace App\Services\Incident;

use App\Enums\PayoutAllocationRole;
use App\Models\BookingPayment;
use App\Models\MuthowifBooking;

final class BookingSettlementCalculator
{
    /**
     * @return array{
     *   allocations: list<array{profile_id: string, role: PayoutAllocationRole, days: int, amount: float}>,
     *   snapshot: array<string, mixed>
     * }
     */
    public function incidentSplit(
        BookingPayment $payment,
        MuthowifBooking $booking,
        string $primaryProfileId,
        ?string $replacementProfileId,
        int $daysPrimary,
        int $daysReplacement,
        int $totalDays,
        bool $primaryGetsZero = false,
    ): array {
        $totalDays = max(1, $totalDays);
        $pool = round((float) $payment->muthowif_net_amount - (float) ($payment->referral_reward_amount ?? 0), 2);

        $primaryAmount = $primaryGetsZero
            ? 0.0
            : round($pool * ($daysPrimary / $totalDays), 2);

        $replacementAmount = $replacementProfileId
            ? round($pool * ($daysReplacement / $totalDays), 2)
            : 0.0;

        $rounding = round($pool - $primaryAmount - $replacementAmount, 2);
        if ($rounding !== 0.0 && $replacementProfileId) {
            $replacementAmount = round($replacementAmount + $rounding, 2);
        } elseif ($rounding !== 0.0) {
            $primaryAmount = round($primaryAmount + $rounding, 2);
        }

        $allocations = [
            [
                'profile_id' => $primaryProfileId,
                'role' => PayoutAllocationRole::Primary,
                'days' => $daysPrimary,
                'amount' => $primaryAmount,
            ],
        ];

        if ($replacementProfileId && $replacementAmount > 0) {
            $allocations[] = [
                'profile_id' => $replacementProfileId,
                'role' => PayoutAllocationRole::Replacement,
                'days' => $daysReplacement,
                'amount' => $replacementAmount,
            ];
        }

        return [
            'allocations' => $allocations,
            'snapshot' => [
                'pool' => $pool,
                'total_days' => $totalDays,
                'days_primary' => $daysPrimary,
                'days_replacement' => $daysReplacement,
                'primary_gets_zero' => $primaryGetsZero,
                'payment_id' => $payment->getKey(),
            ],
        ];
    }

    public function elapsedServiceDays(MuthowifBooking $booking): int
    {
        $total = MuthowifBooking::inclusiveSpanDays($booking->starts_on, $booking->ends_on);
        $today = now()->startOfDay();
        $start = $booking->starts_on->copy()->startOfDay();

        if ($today->lt($start)) {
            return 0;
        }

        $elapsed = (int) $start->diffInDays($today) + 1;

        return min($total, max(0, $elapsed));
    }
}
