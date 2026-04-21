<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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
        $totalPlatformFees = AdminFinanceSummary::totalPlatformFees();
        $totalVolume = AdminFinanceSummary::grossVolumeExcludingRefundedBookings();

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
            'totalVolume' => $totalVolume,
        ]);
    }
}
