<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\MuthowifBooking;
use App\Support\AdminFinanceSummary;
use App\Support\AdminFinanceTimeline;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;

class FinanceController extends Controller
{
    public function index(): View
    {
        /*
         * Fee platform dari pembayaran (settlement):
         * - Booking BUKAN refunded: jumlahkan penuh platform_fee_amount (15% dasar = 7,5% jamaah + 7,5% muthowif).
         * - Booking refunded: hanya setengah (7,5% dasar) — bagian yang di-retain sebagai revenue platform dari order;
         *   ditambah refund_fee_platform di bawah (potongan admin refund), tanpa double count penuh 15%+15%.
         *
         * SQL setara:
         *   SELECT COALESCE(SUM(
         *     CASE WHEN b.payment_status = 'refunded' THEN bp.platform_fee_amount * 0.5 ELSE bp.platform_fee_amount END
         *   ), 0)
         *   FROM booking_payments bp
         *   INNER JOIN muthowif_bookings b ON b.id = bp.muthowif_booking_id
         *   WHERE bp.status IN ('settlement','capture');
         */
        $totalPlatformFees = AdminFinanceSummary::netPlatformFees();
        $affiliateCommissions = AdminFinanceSummary::affiliateCommissionsPaidOrPending();
        $totalVolume = AdminFinanceSummary::grossVolumeExcludingRefundedBookings();
        $totalOrders = AdminFinanceSummary::settlementOrderCountTotal();

        $today = now();
        $yesterday = now()->subDay();

        $pct = static function (float $current, float $previous): ?float {
            if ($previous <= 0) {
                return $current > 0 ? 100.0 : null;
            }

            return round((($current - $previous) / $previous) * 100, 1);
        };

        $feeToday = AdminFinanceSummary::platformFeePaymentsSumBetween($today, $today);
        $feeYesterday = AdminFinanceSummary::platformFeePaymentsSumBetween($yesterday, $yesterday);
        $affToday = AdminFinanceSummary::affiliateCommissionSumBetween($today, $today);
        $affYesterday = AdminFinanceSummary::affiliateCommissionSumBetween($yesterday, $yesterday);
        $grossToday = AdminFinanceSummary::grossVolumeBetween($today, $today);
        $grossYesterday = AdminFinanceSummary::grossVolumeBetween($yesterday, $yesterday);
        $ordersToday = AdminFinanceSummary::settlementOrderCountBetween($today, $today);
        $ordersYesterday = AdminFinanceSummary::settlementOrderCountBetween($yesterday, $yesterday);

        $trends = [
            'fee' => $pct($feeToday, $feeYesterday),
            'affiliate' => $pct($affToday, $affYesterday),
            'gross' => $pct((float) $grossToday, (float) $grossYesterday),
            'orders' => $pct((float) $ordersToday, (float) $ordersYesterday),
        ];

        $chart = AdminFinanceSummary::chartDailyFinanceSeriesLastDays(7);

        $todaySummary = [
            'gross' => (float) $grossToday,
            'fee' => $feeToday,
            'affiliate' => $affToday,
            'net_muthowif' => max(0, (float) $grossToday - $feeToday),
        ];

        $pendingRefundCount = MuthowifBooking::query()
            ->where('payment_status', PaymentStatus::RefundPending)
            ->count();

        $historySince = now()->subMonths((int) config('admin.finance.history_months', 24))->startOfDay();
        $groups = AdminFinanceTimeline::groupsSince($historySince);

        $perPage = 12;
        $page = max(1, (int) request()->input('history_page', 1));
        $total = $groups->count();
        $slice = $groups->slice(($page - 1) * $perPage, $perPage)->values();

        $history = new LengthAwarePaginator(
            $slice,
            $total,
            $perPage,
            $page,
            [
                'path' => request()->url(),
                'pageName' => 'history_page',
            ]
        );
        $history->withQueryString();

        return view('admin.finance.index', [
            'history' => $history,
            'totalPlatformFees' => $totalPlatformFees,
            'affiliateCommissions' => $affiliateCommissions,
            'totalVolume' => $totalVolume,
            'totalOrders' => $totalOrders,
            'trends' => $trends,
            'chart' => $chart,
            'todaySummary' => $todaySummary,
            'pendingRefundCount' => $pendingRefundCount,
        ]);
    }
}
