<?php

namespace App\Support;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Models\MuthowifBooking;
use Carbon\CarbonInterface;

final class BookingPostPayRules
{
    public static function refundMinDaysBeforeService(): int
    {
        return max(0, (int) config('booking.refund_min_days_before_service', 60));
    }

    public static function rescheduleMinDaysBeforeService(): int
    {
        return max(0, (int) config('booking.reschedule_min_days_before_service', 30));
    }

    public static function canRequestRefund(MuthowifBooking $booking): ?string
    {
        if (! $booking->isPaid()) {
            return __('bookings.refund_eligibility.not_paid');
        }

        if ($booking->status !== BookingStatus::Confirmed) {
            return __('bookings.refund_eligibility.only_confirmed');
        }

        if ($booking->payment_status === PaymentStatus::Refunded) {
            return __('bookings.refund_eligibility.already_refunded');
        }

        if ($booking->payment_status === PaymentStatus::RefundPending) {
            return __('bookings.refund_eligibility.refund_pending');
        }

        if ($booking->settledBookingPayment() === null) {
            return __('bookings.refund_eligibility.payment_missing');
        }

        $today = now()->startOfDay();
        $serviceStart = $booking->starts_on->copy()->startOfDay();
        if ($serviceStart->lte($today)) {
            return __('bookings.refund_eligibility.service_started');
        }

        $daysUntil = $today->diffInDays($serviceStart, false);
        $minDays = self::refundMinDaysBeforeService();
        if ($daysUntil < $minDays) {
            return __('bookings.refund_eligibility.too_late', ['days' => $minDays]);
        }

        return null;
    }

    public static function canRequestReschedule(MuthowifBooking $booking): ?string
    {
        if (! $booking->isPaid()) {
            return __('bookings.reschedule_eligibility.not_paid');
        }

        if ($booking->status !== BookingStatus::Confirmed) {
            return __('bookings.reschedule_eligibility.only_confirmed');
        }

        if ($booking->pendingRescheduleRequest() !== null) {
            return __('bookings.reschedule_eligibility.pending_exists');
        }

        $today = now()->startOfDay();
        $serviceStart = $booking->starts_on->copy()->startOfDay();
        if ($serviceStart->lte($today)) {
            return __('bookings.reschedule_eligibility.service_started');
        }

        return null;
    }

    /**
     * Tanggal mulai baru harus minimal H-X hari dari hari ini (zona aplikasi).
     */
    public static function newStartMeetsRescheduleMinDays(CarbonInterface $newStart): bool
    {
        $today = now()->startOfDay();
        $start = $newStart->copy()->startOfDay();
        $daysUntil = $today->diffInDays($start, false);
        $minDays = self::rescheduleMinDaysBeforeService();

        return $daysUntil >= $minDays;
    }

    /**
     * Tanggal mulai baru harus berjarak minimal H-X hari kalender dari tanggal mulai booking saat ini
     * (mis. mulai 30 Jul tidak boleh digeser hanya ke 28 Jul).
     */
    public static function newStartMeetsMinShiftFromOriginal(MuthowifBooking $booking, CarbonInterface $newStart): bool
    {
        if ($booking->starts_on === null) {
            return false;
        }

        $orig = $booking->starts_on->copy()->startOfDay();
        $new = $newStart->copy()->startOfDay();
        $minDays = self::rescheduleMinDaysBeforeService();
        $gap = (int) $orig->diffInDays($new, true);

        return $gap >= $minDays;
    }
}
