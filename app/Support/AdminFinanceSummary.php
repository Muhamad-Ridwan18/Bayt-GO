<?php

namespace App\Support;

use App\Enums\AffiliateCommissionStatus;
use App\Enums\BookingChangeRequestStatus;
use App\Enums\PaymentStatus;
use App\Models\AffiliateCommission;
use App\Models\BookingPayment;
use App\Models\BookingRefundRequest;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * Agregat ringkasan admin (kartu dashboard & halaman keuangan) — satu sumber kebenaran.
 */
final class AdminFinanceSummary
{
    public static function clearCache(): void
    {
        \Illuminate\Support\Facades\Cache::forget('admin_platform_fees_payments');
        \Illuminate\Support\Facades\Cache::forget('admin_platform_fees_refunds');
        \Illuminate\Support\Facades\Cache::forget('admin_total_platform_fees');
        \Illuminate\Support\Facades\Cache::forget('admin_affiliate_commissions');
        \Illuminate\Support\Facades\Cache::forget('admin_net_platform_fees');
        \Illuminate\Support\Facades\Cache::forget('admin_gross_volume_excluding_refunded');
        \Illuminate\Support\Facades\Cache::forget('admin_finance_timeline_groups');
    }

    public static function platformFeesFromPayments(): float
    {
        return (float) \Illuminate\Support\Facades\Cache::remember('admin_platform_fees_payments', 86400, function () {
            $platformFeesRefunded = (float) BookingPayment::query()
                ->join('muthowif_bookings as b', 'b.id', '=', 'booking_payments.muthowif_booking_id')
                ->whereIn('booking_payments.status', ['settlement', 'capture'])
                ->where('b.payment_status', PaymentStatus::Refunded->value)
                ->sum('booking_payments.platform_fee_amount') * 0.5;

            $platformFeesNotRefunded = (float) BookingPayment::query()
                ->join('muthowif_bookings as b', 'b.id', '=', 'booking_payments.muthowif_booking_id')
                ->whereIn('booking_payments.status', ['settlement', 'capture'])
                ->where('b.payment_status', '!=', PaymentStatus::Refunded->value)
                ->sum('booking_payments.platform_fee_amount');

            return $platformFeesRefunded + $platformFeesNotRefunded;
        });
    }

    public static function platformFeesFromRefunds(): float
    {
        return (float) \Illuminate\Support\Facades\Cache::remember('admin_platform_fees_refunds', 86400, function () {
            return (float) BookingRefundRequest::query()
                ->where('status', BookingChangeRequestStatus::Approved)
                ->sum('refund_fee_platform');
        });
    }

    public static function totalPlatformFees(): float
    {
        return (float) \Illuminate\Support\Facades\Cache::remember('admin_total_platform_fees', 86400, function () {
            return self::platformFeesFromPayments() + self::platformFeesFromRefunds();
        });
    }

    public static function affiliateCommissionsPaidOrPending(): float
    {
        return (float) \Illuminate\Support\Facades\Cache::remember('admin_affiliate_commissions', 86400, function () {
            return (float) AffiliateCommission::query()
                ->whereIn('status', [
                    AffiliateCommissionStatus::Pending->value,
                    AffiliateCommissionStatus::Available->value,
                ])
                ->sum('commission_amount');
        });
    }

    public static function netPlatformFees(): float
    {
        return (float) \Illuminate\Support\Facades\Cache::remember('admin_net_platform_fees', 86400, function () {
            return max(0, self::totalPlatformFees() - self::affiliateCommissionsPaidOrPending());
        });
    }

    /**
     * Volume bruto pembayaran (gross): settlement/capture pada booking yang belum refunded.
     */
    public static function grossVolumeExcludingRefundedBookings(): int
    {
        return (int) \Illuminate\Support\Facades\Cache::remember('admin_gross_volume_excluding_refunded', 86400, function () {
            return (int) BookingPayment::query()
                ->join('muthowif_bookings as b', 'b.id', '=', 'booking_payments.muthowif_booking_id')
                ->whereIn('booking_payments.status', ['settlement', 'capture'])
                ->where('b.payment_status', '!=', PaymentStatus::Refunded->value)
                ->sum('booking_payments.gross_amount');
        });
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
            ->where('b.payment_status', '!=', PaymentStatus::Refunded->value)
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

        $grossPayments = BookingPayment::query()
            ->whereIn('status', ['settlement', 'capture'])
            ->whereNotNull('settled_at')
            ->whereBetween('settled_at', [$start, $end->copy()->endOfDay()])
            ->toBase()
            ->get(['settled_at', 'gross_amount']);

        $grossRows = $grossPayments->groupBy(function ($payment) {
            return \Illuminate\Support\Carbon::parse($payment->settled_at)->toDateString();
        })->map(function ($group) {
            return $group->sum('gross_amount');
        });

        $refundRequests = BookingRefundRequest::query()
            ->where('status', BookingChangeRequestStatus::Approved)
            ->whereNotNull('decided_at')
            ->whereBetween('decided_at', [$start, $end->copy()->endOfDay()])
            ->toBase()
            ->get(['decided_at', 'net_refund_customer']);

        $refundRows = $refundRequests->groupBy(function ($refund) {
            return \Illuminate\Support\Carbon::parse($refund->decided_at)->toDateString();
        })->map(function ($group) {
            return $group->sum(fn ($r) => (float) ($r->net_refund_customer ?? 0));
        });

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
