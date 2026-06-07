<?php

namespace App\Support;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Enums\ReplacementOfferStatus;
use App\Models\MuthowifBooking;
use App\Services\MuthowifNetworkReferralService;

final class BookingWebLive
{
    public const TIER_DYNAMIC = 'dynamic';

    public const TIER_FULL = 'full';

    /**
     * @param  array{status?: string|null, payment_status?: string|null, emergency_event?: bool}|null  $client
     * @return array{
     *   tier: string,
     *   booking_id: string,
     *   status: string,
     *   payment_status: string,
     *   is_paid: bool,
     *   has_emergency: bool,
     *   has_referral_panel: bool,
     *   pending_change_requests: bool
     * }
     */
    public static function customerShowState(MuthowifBooking $booking, ?array $client = null): array
    {
        $booking->loadMissing(['muthowifProfile.user']);

        $networkReferral = app(MuthowifNetworkReferralService::class);
        $showReferralPanel = $networkReferral->shouldShowCustomerReferralPanel($booking);
        $emergency = EmergencyBookingViewData::for($booking);
        $hasEmergency = $emergency['activeEmergencyReport'] !== null;

        $pendingChange = $booking->refundRequests()->where('status', 'pending')->exists()
            || $booking->rescheduleRequests()->where('status', 'pending')->exists();

        return [
            'tier' => self::TIER_FULL,
            'booking_id' => (string) $booking->getKey(),
            'status' => $booking->status->value,
            'payment_status' => $booking->payment_status->value,
            'is_paid' => $booking->isPaid(),
            'has_emergency' => $hasEmergency,
            'has_referral_panel' => $showReferralPanel,
            'pending_change_requests' => $pendingChange,
        ];
    }

    /**
     * @param  array{status?: string|null, payment_status?: string|null, emergency_event?: bool}|null  $client
     * @return array{
     *   tier: string,
     *   booking_id: string,
     *   status: string,
     *   payment_status: string,
     *   is_paid: bool,
     *   has_pending_reschedule: bool
     * }
     */
    public static function muthowifShowState(MuthowifBooking $booking, ?array $client = null): array
    {
        $booking->loadMissing(['customer', 'muthowifProfile']);

        $hasPendingReschedule = $booking->rescheduleRequests()->where('status', 'pending')->exists();
        $hasPendingRefund = $booking->refundRequests()->where('status', 'pending')->exists();
        $isPending = $booking->status === BookingStatus::Pending;

        $prevStatus = $client['status'] ?? null;
        $prevPayment = $client['payment_status'] ?? null;
        $emergencyEvent = (bool) ($client['emergency_event'] ?? false);

        $tier = self::TIER_DYNAMIC;
        if (
            $emergencyEvent
            || $hasPendingReschedule
            || $hasPendingRefund
            || $isPending
            || self::statusTierChanged($prevStatus, $booking->status->value)
            || self::paymentTierChanged($prevPayment, $booking->payment_status->value, $booking->status)
        ) {
            $tier = self::TIER_FULL;
        }

        return [
            'tier' => $tier,
            'booking_id' => (string) $booking->getKey(),
            'status' => $booking->status->value,
            'payment_status' => $booking->payment_status->value,
            'is_paid' => $booking->isPaid(),
            'has_pending_reschedule' => $hasPendingReschedule,
        ];
    }

    private static function statusTierChanged(?string $previous, string $current): bool
    {
        if ($previous === null || $previous === '') {
            return false;
        }

        return $previous !== $current;
    }

    private static function paymentTierChanged(?string $previousPayment, string $currentPayment, BookingStatus $status): bool
    {
        if ($previousPayment === null || $previousPayment === '') {
            return false;
        }

        if ($previousPayment === $currentPayment) {
            return false;
        }

        if ($status === BookingStatus::Confirmed && $currentPayment === PaymentStatus::Paid->value) {
            return true;
        }

        return true;
    }

    public static function emergencyOffersSelectableCount(MuthowifBooking $booking): int
    {
        $report = $booking->activeEmergencyReport();
        if ($report === null) {
            return 0;
        }

        return $report->offers()
            ->where('status', ReplacementOfferStatus::Accepted->value)
            ->count();
    }
}
