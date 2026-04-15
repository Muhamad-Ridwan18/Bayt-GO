<?php

namespace App\Support;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Models\MuthowifBooking;

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

        $daysUntil = $today->diffInDays($serviceStart, false);
        $minDays = self::rescheduleMinDaysBeforeService();
        if ($daysUntil < $minDays) {
            return __('bookings.reschedule_eligibility.too_late', ['days' => $minDays]);
        }

        return null;
    }
}
