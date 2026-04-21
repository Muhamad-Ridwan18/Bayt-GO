<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BookingChangeRequestStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\BookingPayment;
use App\Models\BookingRefundRequest;
use App\Models\MuthowifWithdrawal;
use Carbon\CarbonInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
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
        $platformFeesFromPayments = (float) BookingPayment::query()
            ->join('muthowif_bookings as b', 'b.id', '=', 'booking_payments.muthowif_booking_id')
            ->whereIn('booking_payments.status', ['settlement', 'capture'])
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN b.payment_status = ? THEN booking_payments.platform_fee_amount * 0.5 ELSE booking_payments.platform_fee_amount END), 0) as platform_from_payments',
                [PaymentStatus::Refunded->value]
            )
            ->value('platform_from_payments');

        /*
         * Potongan admin refund selesai (approved).
         *   SELECT COALESCE(SUM(refund_fee_platform), 0)
         *   FROM booking_refund_requests WHERE status = 'approved';
         */
        $platformFeesFromRefunds = (float) BookingRefundRequest::query()
            ->where('status', BookingChangeRequestStatus::Approved)
            ->sum('refund_fee_platform');

        $totalPlatformFees = $platformFeesFromPayments + $platformFeesFromRefunds;

        /*
         * Bruto jamaah: hanya pembayaran settlement pada booking yang belum refunded
         * (uang yang masih dianggap "volume" operasional; refunded = dikembalikan ke jamaah).
         */
        $totalVolume = (int) BookingPayment::query()
            ->join('muthowif_bookings as b', 'b.id', '=', 'booking_payments.muthowif_booking_id')
            ->whereIn('booking_payments.status', ['settlement', 'capture'])
            ->where('b.payment_status', '!=', PaymentStatus::Refunded->value)
            ->sum('booking_payments.gross_amount');

        $historySince = now()->subMonths((int) config('admin.finance.history_months', 24))->startOfDay();

        $payments = BookingPayment::query()
            ->select([
                'id',
                'muthowif_booking_id',
                'order_id',
                'gross_amount',
                'platform_fee_amount',
                'muthowif_net_amount',
                'status',
                'settled_at',
                'created_at',
            ])
            ->whereIn('status', ['settlement', 'capture'])
            ->whereNotNull('settled_at')
            ->where('settled_at', '>=', $historySince)
            ->with([
                'muthowifBooking' => static function ($q): void {
                    $q->select([
                        'id',
                        'booking_code',
                        'muthowif_profile_id',
                        'customer_id',
                        'total_amount',
                        'service_type',
                        'starts_on',
                        'ends_on',
                        'pilgrim_count',
                        'selected_add_on_ids',
                        'with_same_hotel',
                        'with_transport',
                    ])->with([
                        'customer:id,name',
                        'muthowifProfile' => static function ($q2): void {
                            $q2->select(['id', 'user_id'])->with('user:id,name');
                        },
                    ]);
                },
            ])
            ->get();

        $completedRefunds = BookingRefundRequest::query()
            ->where('status', BookingChangeRequestStatus::Approved)
            ->whereNotNull('decided_at')
            ->where('decided_at', '>=', $historySince)
            ->with([
                'muthowifBooking' => static function ($q): void {
                    $q->select([
                        'id',
                        'booking_code',
                        'muthowif_profile_id',
                        'customer_id',
                    ])->with([
                        'customer:id,name',
                        'muthowifProfile' => static function ($q2): void {
                            $q2->select(['id', 'user_id'])->with('user:id,name');
                        },
                        // Jangan batasi select() di sini: latestOfMany memakai join subquery; kolom tanpa prefix jadi ambiguous di MySQL.
                        'latestSettledBookingPayment',
                    ]);
                },
            ])
            ->get();

        $withdrawals = MuthowifWithdrawal::query()
            ->with([
                'muthowifProfile' => static function ($q): void {
                    $q->select(['id', 'user_id'])->with('user:id,name');
                },
            ])
            ->where('updated_at', '>=', $historySince)
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

        $grouped = $timeline->groupBy(static function (array $row): string {
            return match ($row['kind']) {
                'order' => filled($row['payment']->muthowifBooking?->booking_code)
                    ? (string) $row['payment']->muthowifBooking->booking_code
                    : 'id:'.$row['payment']->muthowif_booking_id,
                'refund' => filled($row['refund']->muthowifBooking?->booking_code)
                    ? (string) $row['refund']->muthowifBooking->booking_code
                    : 'id:'.$row['refund']->muthowif_booking_id,
                'withdraw' => '__w:'.$row['withdrawal']->getKey(),
                default => 'unknown',
            };
        });

        /** @var Collection<int, array{display_label: string, is_withdraw_group: bool, rows: Collection<int, array<string, mixed>>, sort_at: int}> $groups */
        $groups = $grouped->map(static function (Collection $rows, string $key): array {
            $sorted = $rows->sortBy([
                static fn (array $r): int => match ($r['kind']) {
                    'order' => 0,
                    'refund' => 1,
                    default => 2,
                },
                static fn (array $r): int => $r['at']->getTimestamp(),
            ])->values();

            $sortAt = (int) $sorted->max(static fn (array $r): int => $r['at']->getTimestamp());

            $isWithdrawGroup = str_starts_with($key, '__w:');
            $displayLabel = match (true) {
                $isWithdrawGroup => __('admin.finance.history_group_withdraw'),
                str_starts_with($key, 'id:') => __('admin.finance.history_group_booking_nocode', [
                    'id' => Str::limit(substr($key, 3), 13),
                ]),
                default => $key,
            };

            return [
                'display_label' => $displayLabel,
                'is_withdraw_group' => $isWithdrawGroup,
                'rows' => $sorted,
                'sort_at' => $sortAt,
            ];
        })->values();

        $groups = $groups->sortByDesc('sort_at')->values();

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
