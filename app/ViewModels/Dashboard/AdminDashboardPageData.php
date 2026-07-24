<?php

namespace App\ViewModels\Dashboard;

use App\Enums\BookingChangeRequestStatus;
use App\Enums\MuthowifVerificationStatus;
use App\Models\BookingPayment;
use App\Models\BookingRefundRequest;
use App\Models\MuthowifBooking;
use App\Models\MuthowifProfile;
use App\Models\MuthowifWithdrawal;
use App\Support\AdminFinanceSummary;
use App\Support\IndonesianNumber;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

final class AdminDashboardPageData
{
    /**
     * @param  array{month_label: string, days: list<string>, gross: list<int>, refunds: list<int>, total_gross: int, total_refunds: int}  $chart
     * @param  Collection<int, BookingPayment>  $recentPayments
     * @param  list<string>  $xTicks
     */
    public function __construct(
        public readonly Carbon $now,
        public readonly float $platformFeeThis,
        public readonly ?int $pctPlatform,
        public readonly int $grossThisMonth,
        public readonly ?int $pctGross,
        public readonly int $settledThisMonth,
        public readonly ?int $pctSettled,
        public readonly int $totalBookings,
        public readonly ?int $pctBookings,
        public readonly int $activeMuthowif,
        public readonly array $chart,
        public readonly int $maxY,
        public readonly int $chartW,
        public readonly int $chartH,
        public readonly string $grossPoly,
        public readonly string $refundPoly,
        public readonly array $xTicks,
        public readonly int $netChart,
        public readonly Collection $recentPayments,
        public readonly int $pendingRefundCount,
        public readonly int $pendingWithdrawCount,
        public readonly int $pendingMuthowifCount,
        public readonly int $pendingTotal,
        public readonly int $newBookingsToday,
        public readonly int $settlementsToday,
        public readonly int $refundsToday,
    ) {}

    public static function make(): self
    {
        $now = Carbon::now();
        $thisMonthStart = $now->copy()->startOfMonth();
        $lastMonthStart = $now->copy()->subMonth()->startOfMonth();
        $lastMonthEnd = $now->copy()->subMonth()->endOfMonth();

        $platformFeePrev = AdminFinanceSummary::platformFeePaymentsSumBetween($lastMonthStart, $lastMonthEnd);
        $platformFeeThis = AdminFinanceSummary::platformFeePaymentsSumBetween($thisMonthStart, $now);
        $grossThisMonth = AdminFinanceSummary::grossVolumeBetween($thisMonthStart, $now);
        $grossPrevMonth = AdminFinanceSummary::grossVolumeBetween($lastMonthStart, $lastMonthEnd);

        $settledThisMonth = BookingPayment::query()
            ->whereIn('status', ['settlement', 'capture'])
            ->whereNotNull('settled_at')
            ->whereBetween('settled_at', [$thisMonthStart, $now->copy()->endOfDay()])
            ->count();
        $settledLastMonth = BookingPayment::query()
            ->whereIn('status', ['settlement', 'capture'])
            ->whereNotNull('settled_at')
            ->whereBetween('settled_at', [$lastMonthStart, $lastMonthEnd->copy()->endOfDay()])
            ->count();

        $totalBookings = (int) MuthowifBooking::query()->count();
        $bookingsThisMonth = (int) MuthowifBooking::query()->where('created_at', '>=', $thisMonthStart)->count();
        $bookingsLastMonth = (int) MuthowifBooking::query()
            ->whereBetween('created_at', [$lastMonthStart, $lastMonthEnd->copy()->endOfDay()])
            ->count();

        $activeMuthowif = (int) MuthowifProfile::query()
            ->where('verification_status', MuthowifVerificationStatus::Approved)
            ->count();

        $pendingWithdrawCount = (int) MuthowifWithdrawal::query()
            ->where('status', 'pending_approval')
            ->count();
        $pendingRefundCount = (int) BookingRefundRequest::query()
            ->where('status', BookingChangeRequestStatus::Pending)
            ->count();
        $pendingMuthowifCount = (int) MuthowifProfile::query()
            ->where('verification_status', MuthowifVerificationStatus::Pending)
            ->count();
        $pendingTotal = $pendingMuthowifCount + $pendingWithdrawCount + $pendingRefundCount;

        $todayStart = $now->copy()->startOfDay();
        $newBookingsToday = (int) MuthowifBooking::query()->where('created_at', '>=', $todayStart)->count();
        $settlementsToday = (int) BookingPayment::query()
            ->whereIn('status', ['settlement', 'capture'])
            ->whereNotNull('settled_at')
            ->where('settled_at', '>=', $todayStart)
            ->count();
        $refundsToday = (int) BookingRefundRequest::query()
            ->where('status', BookingChangeRequestStatus::Approved)
            ->whereNotNull('decided_at')
            ->where('decided_at', '>=', $todayStart)
            ->count();

        $chart = AdminFinanceSummary::chartDailySeriesForMonth($now);

        $recentPayments = BookingPayment::query()
            ->whereIn('status', ['settlement', 'capture'])
            ->orderByDesc('settled_at')
            ->limit(8)
            ->with(['muthowifBooking:id,booking_code'])
            ->get(['id', 'order_id', 'gross_amount', 'status', 'settled_at', 'muthowif_booking_id']);

        $pctPlatform = self::momPct($platformFeeThis, $platformFeePrev);
        $pctGross = self::momPct((float) $grossThisMonth, (float) $grossPrevMonth);
        $pctSettled = self::momPct((float) $settledThisMonth, (float) $settledLastMonth);
        $pctBookings = self::momPct((float) $bookingsThisMonth, (float) $bookingsLastMonth);

        $netChart = max(0, $chart['total_gross'] - $chart['total_refunds']);

        $maxY = max(1, ...$chart['gross'], ...$chart['refunds']);
        $chartW = 100;
        $chartH = 44;
        $n = max(1, count($chart['gross']));

        $grossPoly = self::linePoints($chart['gross'], $chartW, $chartH, $maxY);
        $refundPoly = self::linePoints($chart['refunds'], $chartW, $chartH, $maxY);

        // Label sumbu X: maksimal ~7 tick
        $xTicks = [];
        $step = max(1, (int) ceil($n / 7));
        foreach ($chart['days'] as $i => $day) {
            if ($i % $step === 0) {
                $xTicks[] = Carbon::parse($day)->translatedFormat('j M');
            }
        }

        return new self(
            now: $now,
            platformFeeThis: $platformFeeThis,
            pctPlatform: $pctPlatform,
            grossThisMonth: $grossThisMonth,
            pctGross: $pctGross,
            settledThisMonth: $settledThisMonth,
            pctSettled: $pctSettled,
            totalBookings: $totalBookings,
            pctBookings: $pctBookings,
            activeMuthowif: $activeMuthowif,
            chart: $chart,
            maxY: $maxY,
            chartW: $chartW,
            chartH: $chartH,
            grossPoly: $grossPoly,
            refundPoly: $refundPoly,
            xTicks: $xTicks,
            netChart: $netChart,
            recentPayments: $recentPayments,
            pendingRefundCount: $pendingRefundCount,
            pendingWithdrawCount: $pendingWithdrawCount,
            pendingMuthowifCount: $pendingMuthowifCount,
            pendingTotal: $pendingTotal,
            newBookingsToday: $newBookingsToday,
            settlementsToday: $settlementsToday,
            refundsToday: $refundsToday,
        );
    }

    public function formatMoney(float|int $n): string
    {
        return IndonesianNumber::formatThousands((string) (int) round((float) $n));
    }

    public function formatShort(float $v): string
    {
        if ($v >= 1000000) {
            return rtrim(rtrim(number_format($v / 1000000, 1, ',', '.'), '0'), ',').'jt';
        }
        if ($v >= 1000) {
            return rtrim(rtrim(number_format($v / 1000, 1, ',', '.'), '0'), ',').'rb';
        }

        return (string) (int) $v;
    }

    private static function momPct(float $cur, float $prev): ?int
    {
        if ($prev <= 0 && $cur <= 0) {
            return null;
        }
        if ($prev <= 0) {
            return $cur > 0 ? 100 : 0;
        }

        return (int) round(100 * ($cur - $prev) / $prev);
    }

    /**
     * @param  list<int>  $series
     */
    private static function linePoints(array $series, int $chartW, int $chartH, int $maxY): string
    {
        $pts = [];
        $count = count($series);
        if ($count === 0) {
            return '0,'.$chartH;
        }
        if ($count === 1) {
            $y = $chartH - ((int) $series[0] / $maxY) * $chartH;

            return '0,'.round($y, 2).' '.$chartW.','.round($y, 2);
        }
        foreach ($series as $i => $v) {
            $x = ($i / ($count - 1)) * $chartW;
            $y = $chartH - (((int) $v) / $maxY) * ($chartH - 2);
            $pts[] = round($x, 2).','.round($y, 2);
        }

        return implode(' ', $pts);
    }
}
