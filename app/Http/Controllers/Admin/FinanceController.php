<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BookingChangeRequestStatus;
use App\Http\Controllers\Controller;
use App\Models\BookingPayment;
use App\Models\BookingRefundRequest;
use App\Models\MuthowifWithdrawal;
use Carbon\CarbonInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class FinanceController extends Controller
{
    public function index(): View
    {
        $paidQuery = BookingPayment::query()->whereIn('status', ['settlement', 'capture']);

        $platformFeesFromPayments = (float) (clone $paidQuery)->sum('platform_fee_amount');
        $platformFeesFromRefunds = (float) BookingRefundRequest::query()
            ->where('status', BookingChangeRequestStatus::Approved)
            ->sum('refund_fee_platform');
        // Jangan jumlahkan refund ke angka utama: potongan admin refund juga :pct dari harga dasar
        // seperti total fee platform pada order — menjumlahkan keduanya = double count per booking.
        $totalPlatformFees = $platformFeesFromPayments;
        $totalVolume = (int) (clone $paidQuery)->sum('gross_amount');

        $payments = BookingPayment::query()
            ->whereIn('status', ['settlement', 'capture'])
            ->whereNotNull('settled_at')
            ->with(['muthowifBooking.customer', 'muthowifBooking.muthowifProfile.user'])
            ->get();

        $completedRefunds = BookingRefundRequest::query()
            ->where('status', BookingChangeRequestStatus::Approved)
            ->whereNotNull('decided_at')
            ->with([
                'muthowifBooking.customer',
                'muthowifBooking.muthowifProfile.user',
                'muthowifBooking.bookingPayments' => static function ($q): void {
                    $q->whereIn('status', ['settlement', 'capture'])->orderByDesc('settled_at');
                },
            ])
            ->get();

        $withdrawals = MuthowifWithdrawal::query()
            ->with(['muthowifProfile.user'])
            ->get();

        /** @var Collection<int, array{kind: string, at: CarbonInterface, payment?: BookingPayment, refund?: BookingRefundRequest, withdrawal?: MuthowifWithdrawal}> $timeline */
        $timeline = Collection::make();

        foreach ($payments as $payment) {
            $at = $payment->settled_at ?? $payment->created_at;
            if ($at === null) {
                continue;
            }
            $timeline->push([
                'kind' => 'order',
                'at' => $at,
                'payment' => $payment,
            ]);
        }

        foreach ($completedRefunds as $refund) {
            $at = $refund->decided_at ?? $refund->created_at;
            if ($at === null) {
                continue;
            }
            $timeline->push([
                'kind' => 'refund',
                'at' => $at,
                'refund' => $refund,
            ]);
        }

        foreach ($withdrawals as $withdrawal) {
            $at = $withdrawal->completed_at
                ?? $withdrawal->failed_at
                ?? $withdrawal->processing_at
                ?? $withdrawal->approved_at
                ?? $withdrawal->requested_at
                ?? $withdrawal->created_at;
            if ($at === null) {
                continue;
            }
            $timeline->push([
                'kind' => 'withdraw',
                'at' => $at,
                'withdrawal' => $withdrawal,
            ]);
        }

        $timeline = $timeline
            ->sortByDesc(static fn (array $row): int => $row['at']->getTimestamp())
            ->values();

        $perPage = 25;
        $page = max(1, (int) request()->input('history_page', 1));
        $total = $timeline->count();
        $slice = $timeline->slice(($page - 1) * $perPage, $perPage)->values();

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
            'platformFeesFromPayments' => $platformFeesFromPayments,
            'platformFeesFromRefunds' => $platformFeesFromRefunds,
            'totalVolume' => $totalVolume,
        ]);
    }
}
