<?php

namespace App\Support;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Models\MuthowifBooking;

final class BookingPostPayRules
{
    public const REFUND_MIN_DAYS_BEFORE_SERVICE = 60;

    public const RESCHEDULE_MIN_DAYS_BEFORE_SERVICE = 30;

    public static function canRequestRefund(MuthowifBooking $booking): ?string
    {
        if (! $booking->isPaid()) {
            return 'Refund hanya setelah pembayaran lunas.';
        }

        if ($booking->status !== BookingStatus::Confirmed) {
            return 'Refund hanya untuk booking terkonfirmasi yang belum selesai.';
        }

        if ($booking->payment_status === PaymentStatus::Refunded) {
            return 'Booking ini sudah direfund.';
        }

        if ($booking->payment_status === PaymentStatus::RefundPending) {
            return 'Refund sudah diajukan dan menunggu transfer dari admin.';
        }

        if ($booking->settledBookingPayment() === null) {
            return 'Data pembayaran tidak ditemukan.';
        }

        $today = now()->startOfDay();
        $serviceStart = $booking->starts_on->copy()->startOfDay();
        if ($serviceStart->lte($today)) {
            return 'Tanggal mulai layanan sudah lewat atau hari ini. Pengajuan refund tidak dapat diproses.';
        }

        $daysUntil = $today->diffInDays($serviceStart, false);
        if ($daysUntil < self::REFUND_MIN_DAYS_BEFORE_SERVICE) {
            return 'Pengajuan refund hanya sampai H-'.self::REFUND_MIN_DAYS_BEFORE_SERVICE.' sebelum tanggal mulai layanan.';
        }

        return null;
    }

    public static function canRequestReschedule(MuthowifBooking $booking): ?string
    {
        if (! $booking->isPaid()) {
            return 'Reschedule hanya setelah pembayaran lunas.';
        }

        if ($booking->status !== BookingStatus::Confirmed) {
            return 'Reschedule hanya untuk booking terkonfirmasi yang belum selesai.';
        }

        if ($booking->pendingRescheduleRequest() !== null) {
            return 'Anda sudah memiliki pengajuan reschedule yang menunggu keputusan muthowif.';
        }

        $today = now()->startOfDay();
        $serviceStart = $booking->starts_on->copy()->startOfDay();
        if ($serviceStart->lte($today)) {
            return 'Tanggal mulai layanan sudah lewat atau hari ini.';
        }

        $daysUntil = $today->diffInDays($serviceStart, false);
        if ($daysUntil < self::RESCHEDULE_MIN_DAYS_BEFORE_SERVICE) {
            return 'Reschedule hanya sampai H-'.self::RESCHEDULE_MIN_DAYS_BEFORE_SERVICE.' sebelum tanggal mulai layanan.';
        }

        return null;
    }
}
