<?php

namespace App\Support;

use App\Enums\BookingChangeRequestStatus;
use App\Enums\PaymentStatus;
use App\Models\BookingPayment;
use App\Models\BookingRefundRequest;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * Agregat ringkasan admin (kartu dashboard & halaman keuangan) — satu sumber kebenaran.
 */
final class AdminFinanceSummary
{
    public static function platformFeesFromPayments(): float
    {
        return (float) BookingPayment::query()
            ->join('muthowif_bookings as b', 'b.id', '=', 'booking_payments.muthowif_booking_id')
            ->whereIn('booking_payments.status', ['settlement', 'capture'])
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN b.payment_status = ? THEN booking_payments.platform_fee_amount * 0.5 ELSE booking_payments.platform_fee_amount END), 0) as platform_from_payments',
                [PaymentStatus::Refunded->value]
            )
            ->value('platform_from_payments');
    }

    public static function platformFeesFromRefunds(): float
    {
        return (float) BookingRefundRequest::query()
            ->where('status', BookingChangeRequestStatus::Approved)
            ->sum('refund_fee_platform');
    }

    public static function totalPlatformFees(): float
    {
        return self::platformFeesFromPayments() + self::platformFeesFromRefunds();
    }

    /**
     * Volume bruto pembayaran (gross): settlement/capture pada booking yang belum refunded.
     */
    public static function grossVolumeExcludingRefundedBookings(): int
    {
        return (int) BookingPayment::query()
            ->join('muthowif_bookings as b', 'b.id', '=', 'booking_payments.muthowif_booking_id')
            ->whereIn('booking_payments.status', ['settlement', 'capture'])
            ->whereRaw('b.payment_status != ?', [PaymentStatus::Refunded->value])
            ->sum('booking_payments.gross_amount');
    }

    /**
     * Volume bruto (gross settlement amounts) dalam rentang settled_at.
     */
    public static function grossVolumeBetween(CarbonInterface $start, CarbonInterface $end): int
    {
        return (int) BookingPayment::query()
            ->join('muthowif_bookings as b', 'b.id', '=', 'booking_payments.muthowif_booking_id')
            ->whereIn('booking_payments.status', ['settlement', 'capture'])
            ->whereBetween('booking_payments.settled_at', [
                Carbon::instance($start)->startOfDay(),
                Carbon::instance($end)->endOfDay(),
            ])
            ->whereRaw('b.payment_status != ?', [PaymentStatus::Refunded->value])
            ->sum('booking_payments.gross_amount');
    }

    /**
     * Aproksimasi fee platform dari pembayaran settlement di periode (untuk tren MoM).
     */
    public static function platformFeePaymentsSumBetween(CarbonInterface $start, CarbonInterface $end): float
    {
        return (float) BookingPayment::query()
            ->whereIn('status', ['settlement', 'capture'])
            ->whereBetween('settled_at', [
                Carbon::instance($start)->startOfDay(),
                Carbon::instance($end)->endOfDay(),
            ])
            ->sum('platform_fee_amount');
    }

    /**
     * Seri harian dalam satu bulan untuk grafik overview (gross vs refund keluar).
     *
     * @return array{
     *     month_label: string,
     *     days: list<string>,
     *     gross: list<int>,
     *     refunds: list<int>,
     *     total_gross: int,
     *     total_refunds: int
     * }
     */
    public static function chartDailySeriesForMonth(?CarbonInterface $month = null): array
    {
        $start = $month
            ? Carbon::instance($month)->startOfMonth()
            : now()->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $grossRows = BookingPayment::query()
            ->whereIn('status', ['settlement', 'capture'])
            ->whereNotNull('settled_at')
            ->whereBetween('settled_at', [$start, $end->copy()->endOfDay()])
            ->selectRaw('DATE(settled_at) as d, SUM(gross_amount) as t')
            ->groupBy('d')
            ->pluck('t', 'd');

        $refundRows = BookingRefundRequest::query()
            ->where('status', BookingChangeRequestStatus::Approved)
            ->whereNotNull('decided_at')
            ->whereBetween('decided_at', [$start, $end->copy()->endOfDay()])
            ->selectRaw('DATE(decided_at) as d, SUM(COALESCE(net_refund_customer, 0)) as t')
            ->groupBy('d')
            ->pluck('t', 'd');

        $days = [];
        $gross = [];
        $refunds = [];
        $totalGross = 0;
        $totalRefunds = 0;

        for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
            $key = $d->toDateString();
            $days[] = $key;
            $g = (int) ($grossRows[$key] ?? 0);
            $r = (int) round((float) ($refundRows[$key] ?? 0));
            $gross[] = $g;
            $refunds[] = $r;
            $totalGross += $g;
            $totalRefunds += $r;
        }

        return [
            'month_label' => $start->translatedFormat('F Y'),
            'days' => $days,
            'gross' => $gross,
            'refunds' => $refunds,
            'total_gross' => $totalGross,
            'total_refunds' => $totalRefunds,
        ];
    }
}
