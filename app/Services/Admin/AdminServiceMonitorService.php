<?php

namespace App\Services\Admin;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Models\BookingPayment;
use App\Models\MuthowifBooking;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

final class AdminServiceMonitorService
{
    public const FILTER_ACTIVE = 'active';

    public const FILTER_IN_SERVICE = 'in_service';

    /**
     * @return array{
     *   counts: array{active: int, in_service: int},
     *   bookings: Collection<int, MuthowifBooking>,
     *   filter: string
     * }
     */
    public function feed(Request $request): array
    {
        $filter = $this->normalizeFilter($request->query('filter', self::FILTER_ACTIVE));

        $bookings = $this->filteredQuery($filter)
            ->limit(80)
            ->get();

        return [
            'counts' => $this->counts(),
            'stats' => $this->stats(),
            'bookings' => $bookings,
            'filter' => $filter,
        ];
    }

    /**
     * @return array{completed_today: int, completed_yesterday: int, escrow_held: float}
     */
    public function stats(): array
    {
        $completedToday = (int) MuthowifBooking::query()
            ->where('status', BookingStatus::Completed)
            ->whereDate('completed_at', now()->toDateString())
            ->count();

        $completedYesterday = (int) MuthowifBooking::query()
            ->where('status', BookingStatus::Completed)
            ->whereDate('completed_at', now()->subDay()->toDateString())
            ->count();

        $escrowHeld = (float) BookingPayment::query()
            ->join('muthowif_bookings as b', 'b.id', '=', 'booking_payments.muthowif_booking_id')
            ->whereIn('booking_payments.status', ['settlement', 'capture'])
            ->whereNull('booking_payments.wallet_credited_at')
            ->where('b.payment_status', PaymentStatus::Paid->value)
            ->sum('booking_payments.gross_amount');

        return [
            'completed_today' => $completedToday,
            'completed_yesterday' => $completedYesterday,
            'escrow_held' => $escrowHeld,
        ];
    }

    /**
     * @return array{active: int, in_service: int}
     */
    public function counts(): array
    {
        return [
            'active' => $this->filteredQuery(self::FILTER_ACTIVE)->count(),
            'in_service' => $this->filteredQuery(self::FILTER_IN_SERVICE)->count(),
        ];
    }

    public function normalizeFilter(?string $filter): string
    {
        return in_array($filter, [
            self::FILTER_ACTIVE,
            self::FILTER_IN_SERVICE,
        ], true) ? $filter : self::FILTER_ACTIVE;
    }

    /**
     * @return Builder<MuthowifBooking>
     */
    private function filteredQuery(string $filter): Builder
    {
        $query = $this->baseQuery();

        if ($filter === self::FILTER_IN_SERVICE) {
            $today = now()->toDateString();

            return $query
                ->whereDate('starts_on', '<=', $today)
                ->whereDate('ends_on', '>=', $today);
        }

        return $query->where('status', '!=', BookingStatus::Completed->value);
    }

    /**
     * @return Builder<MuthowifBooking>
     */
    private function baseQuery(): Builder
    {
        return MuthowifBooking::query()
            ->with([
                'customer:id,name,email',
                'muthowifProfile.user:id,name',
                'latestSettledBookingPayment' => fn ($q) => $q->select(
                    'booking_payments.id',
                    'booking_payments.muthowif_booking_id',
                    'booking_payments.settled_at',
                    'booking_payments.wallet_credited_at',
                ),
            ])
            ->where('status', BookingStatus::Confirmed)
            ->where('payment_status', PaymentStatus::Paid)
            ->orderBy('starts_on');
    }

    public function escrowLabel(MuthowifBooking $booking): string
    {
        $payment = $booking->latestSettledBookingPayment;
        if ($payment?->wallet_credited_at !== null) {
            return 'released';
        }

        return 'held';
    }

    public function servicePhaseKey(MuthowifBooking $booking): ?string
    {
        $start = $booking->starts_on?->copy()->startOfDay();
        $end = $booking->ends_on?->copy()->startOfDay();
        if ($start === null || $end === null) {
            return null;
        }

        $today = now()->startOfDay();
        if ($today->lt($start)) {
            return 'pre_service';
        }

        if ($today->gt($end)) {
            return 'post_service';
        }

        return 'in_service';
    }

    /**
     * @return array{current: int, total: int, pct: int}|null
     */
    public function serviceProgress(MuthowifBooking $booking): ?array
    {
        $start = $booking->starts_on?->copy()->startOfDay();
        $end = $booking->ends_on?->copy()->startOfDay();
        if ($start === null || $end === null) {
            return null;
        }

        $total = (int) $start->diffInDays($end) + 1;
        $today = now()->startOfDay();

        if ($today->lt($start)) {
            $current = 0;
        } elseif ($today->gt($end)) {
            $current = $total;
        } else {
            $current = (int) $start->diffInDays($today) + 1;
        }

        return [
            'current' => $current,
            'total' => $total,
            'pct' => (int) round(($current / max(1, $total)) * 100),
        ];
    }

    public function serviceDayLabel(MuthowifBooking $booking): ?string
    {
        $start = $booking->starts_on?->copy()->startOfDay();
        $end = $booking->ends_on?->copy()->startOfDay();
        if ($start === null || $end === null) {
            return null;
        }

        $today = now()->startOfDay();
        if ($today->lt($start)) {
            $days = (int) $today->diffInDays($start);

            return __('admin.service_monitor.starts_in_days', ['days' => $days]);
        }

        if ($today->gt($end)) {
            return __('admin.service_monitor.ended');
        }

        $current = (int) $start->diffInDays($today) + 1;
        $total = (int) $start->diffInDays($end) + 1;

        return __('admin.service_monitor.service_day', ['current' => $current, 'total' => $total]);
    }
}
