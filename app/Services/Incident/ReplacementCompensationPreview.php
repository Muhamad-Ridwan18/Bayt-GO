<?php

namespace App\Services\Incident;

use App\Models\MuthowifBooking;
use App\Support\PlatformFee;

/**
 * Estimasi pendapatan muthowif pengganti (prorata hari sisa layanan).
 * Selaras dengan {@see BookingSettlementCalculator::incidentSplit()}.
 */
final class ReplacementCompensationPreview
{
    public function __construct(
        private readonly BookingSettlementCalculator $calculator,
    ) {}

    /**
     * @return array{
     *   total_days: int,
     *   elapsed_days: int,
     *   replacement_days: int,
     *   share: float,
     *   service_subtotal: float,
     *   addons_sum: float,
     *   same_hotel_line: float,
     *   transport_line: float,
     *   muthowif_fee: float,
     *   muthowif_net: float,
     * }
     */
    public function forBookingLines(
        MuthowifBooking $booking,
        float $serviceSubtotal,
        float $addonsSum,
        float $sameHotelLine,
        float $transportLine,
    ): array {
        $totalDays = max(1, $booking->billingNightsInclusive());
        $elapsed = min($totalDays, max(0, $this->calculator->elapsedServiceDays($booking)));
        $replacementDays = max(0, $totalDays - $elapsed);
        $share = $replacementDays / $totalDays;

        $serviceSubtotal = round($serviceSubtotal * $share, 2);
        $addonsSum = round($addonsSum * $share, 2);
        $sameHotelLine = round($sameHotelLine * $share, 2);
        $transportLine = round($transportLine * $share, 2);

        $totalGross = round($serviceSubtotal + $addonsSum + $sameHotelLine + $transportLine, 2);
        $payment = $booking->settledBookingPayment();

        if ($payment !== null) {
            $pool = $payment->muthowifWalletCreditAmount();
            $muthowifNet = round($pool * $share, 2);
            $split = PlatformFee::split($totalGross > 0 ? $totalGross : 1.0);
            $muthowifFee = (float) ($split['muthowif_fee'] ?? 0.0);
        } else {
            $split = PlatformFee::split($totalGross > 0 ? $totalGross : 1.0);
            $muthowifNet = (float) ($split['muthowif_net'] ?? 0.0);
            $muthowifFee = (float) ($split['muthowif_fee'] ?? 0.0);
        }

        return [
            'total_days' => $totalDays,
            'elapsed_days' => $elapsed,
            'replacement_days' => $replacementDays,
            'share' => $share,
            'service_subtotal' => $serviceSubtotal,
            'addons_sum' => $addonsSum,
            'same_hotel_line' => $sameHotelLine,
            'transport_line' => $transportLine,
            'muthowif_fee' => $muthowifFee,
            'muthowif_net' => $muthowifNet,
        ];
    }
}
