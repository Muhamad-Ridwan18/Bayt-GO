<?php

namespace App\Support;

use App\Enums\BookingChangeRequestStatus;
use App\Models\BookingPayment;
use App\Models\BookingRefundRequest;
use App\Models\MuthowifWithdrawal;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Riwayat transaksi admin (order settlement, refund selesai, withdraw) — sama dengan halaman Keuangan.
 */
final class AdminFinanceTimeline
{
    /**
     * Jumlah pembayaran Midtrans settlement/capture dalam jendela waktu (sama query filter dengan tabel keuangan).
     */
    public static function settlementPaymentCountSince(CarbonInterface $since): int
    {
        return (int) BookingPayment::query()
            ->whereIn('status', ['settlement', 'capture'])
            ->whereNotNull('settled_at')
            ->where('settled_at', '>=', $since)
            ->count();
    }

    /**
     * Grup riwayat terurut terbaru dulu, struktur sama persis yang dipaginasi di FinanceController.
     *
     * @return Collection<int, array{display_label: string, is_withdraw_group: bool, rows: Collection<int, array<string, mixed>>, sort_at: int}>
     */
    public static function groupsSince(CarbonInterface $since): Collection
    {
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
            ->where('settled_at', '>=', $since)
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
            ->where('decided_at', '>=', $since)
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
            ->where('updated_at', '>=', $since)
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

        return $groups->sortByDesc('sort_at')->values();
    }
}
