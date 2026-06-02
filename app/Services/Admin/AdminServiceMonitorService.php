<?php

namespace App\Services\Admin;

use App\Enums\BookingIncidentOverlayStatus;
use App\Enums\BookingIncidentStatus;
use App\Enums\BookingServicePhase;
use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Models\BookingIncident;
use App\Models\MuthowifBooking;
use App\Services\Incident\EscrowFreezeGuard;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

final class AdminServiceMonitorService
{
    public const FILTER_ACTIVE = 'active';

    public const FILTER_IN_SERVICE = 'in_service';

    public const FILTER_INCIDENT = 'incident';

    /**
     * @return array{
     *   counts: array{active: int, in_service: int, incident: int},
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

        foreach ($bookings as $booking) {
            $booking->syncServicePhase();
        }

        return [
            'counts' => $this->counts(),
            'bookings' => $bookings,
            'filter' => $filter,
        ];
    }

    /**
     * @return array{active: int, in_service: int, incident: int}
     */
    public function counts(): array
    {
        return [
            'active' => $this->filteredQuery(self::FILTER_ACTIVE)->count(),
            'in_service' => $this->filteredQuery(self::FILTER_IN_SERVICE)->count(),
            'incident' => $this->filteredQuery(self::FILTER_INCIDENT)->count(),
        ];
    }

    public function normalizeFilter(?string $filter): string
    {
        return in_array($filter, [
            self::FILTER_ACTIVE,
            self::FILTER_IN_SERVICE,
            self::FILTER_INCIDENT,
        ], true) ? $filter : self::FILTER_ACTIVE;
    }

    /**
     * @return Builder<MuthowifBooking>
     */
    private function filteredQuery(string $filter): Builder
    {
        $query = $this->baseQuery();

        return match ($filter) {
            self::FILTER_IN_SERVICE => $query
                ->where('service_phase', BookingServicePhase::InService->value),
            self::FILTER_INCIDENT => $query->where(function (Builder $q): void {
                $q->where('incident_status', BookingIncidentOverlayStatus::Open->value)
                    ->orWhereHas('incidents', fn (Builder $iq) => $iq->whereIn('status', $this->openIncidentStatuses()));
            }),
            default => $query->where('status', '!=', BookingStatus::Completed->value),
        };
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
                'latestSettledBookingPayment:id,muthowif_booking_id,wallet_credited_at',
                'incidents' => fn ($q) => $q
                    ->whereIn('status', $this->openIncidentStatuses())
                    ->orderByDesc('opened_at')
                    ->limit(1),
            ])
            ->where('status', BookingStatus::Confirmed)
            ->where('payment_status', PaymentStatus::Paid)
            ->orderByRaw(
                "CASE WHEN incident_status = 'open' THEN 0 WHEN service_phase = 'in_service' THEN 1 WHEN service_phase = 'pre_service' THEN 2 ELSE 3 END"
            )
            ->orderBy('starts_on');
    }

    public function escrowLabel(MuthowifBooking $booking): string
    {
        $payment = $booking->latestSettledBookingPayment;
        if ($payment?->wallet_credited_at !== null) {
            return 'released';
        }

        return EscrowFreezeGuard::isFrozen($booking) ? 'frozen' : 'held';
    }

    public function openIncidentFor(MuthowifBooking $booking): ?BookingIncident
    {
        if ($booking->relationLoaded('incidents') && $booking->incidents->isNotEmpty()) {
            return $booking->incidents->first();
        }

        return $booking->openIncident();
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

    /**
     * @return list<string>
     */
    private function openIncidentStatuses(): array
    {
        return array_map(
            fn (BookingIncidentStatus $s) => $s->value,
            array_filter(
                BookingIncidentStatus::cases(),
                fn (BookingIncidentStatus $s) => $s->isOpen()
            )
        );
    }
}
