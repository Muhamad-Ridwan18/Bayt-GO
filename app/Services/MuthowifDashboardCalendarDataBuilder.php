<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\MuthowifServiceType;
use App\Models\MuthowifProfile;
use Carbon\Carbon;

class MuthowifDashboardCalendarDataBuilder
{
    /**
     * @return array{
     *     calendarMonth: Carbon,
     *     calendarStart: Carbon,
     *     calendarEnd: Carbon,
     *     monthStartStr: string,
     *     monthEndStr: string,
     *     calendarBookings: Collection,
     *     blockedDates: Collection,
     *     blockedDatesThisMonth: Collection,
     *     blockedSet: Collection,
     *     bookingSet: Collection,
     *     calendarDetails: array<string, array<string, mixed>>
     * }
     */
    public static function build(MuthowifProfile $mp, ?string $monthParam): array
    {
        try {
            if (is_string($monthParam) && preg_match('/^\d{4}-\d{2}$/', $monthParam)) {
                $calendarMonth = Carbon::createFromFormat('Y-m', $monthParam)->startOfMonth();
            } else {
                $calendarMonth = now()->startOfMonth();
            }
        } catch (\Throwable) {
            $calendarMonth = now()->startOfMonth();
        }

        $calendarMonthMin = now()->copy()->subYears(2)->startOfMonth();
        $calendarMonthMax = now()->copy()->addYears(2)->startOfMonth();
        if ($calendarMonth->lt($calendarMonthMin)) {
            $calendarMonth = $calendarMonthMin->copy();
        }
        if ($calendarMonth->gt($calendarMonthMax)) {
            $calendarMonth = $calendarMonthMax->copy();
        }

        $calendarStart = $calendarMonth->copy()->startOfWeek(Carbon::MONDAY);
        $calendarEnd = $calendarMonth->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY);

        $monthStartStr = $calendarMonth->copy()->startOfMonth()->toDateString();
        $monthEndStr = $calendarMonth->copy()->endOfMonth()->toDateString();

        $calendarBookings = $mp->bookings()
            ->whereIn('status', [BookingStatus::Pending, BookingStatus::Confirmed, BookingStatus::Completed])
            ->whereDate('starts_on', '<=', $monthEndStr)
            ->whereDate('ends_on', '>=', $monthStartStr)
            ->orderBy('starts_on')
            ->get(['id', 'starts_on', 'ends_on', 'status', 'customer_id', 'service_type']);
        $calendarBookings->load('customer:id,name');

        $blockedDates = $mp->blockedDates()
            ->whereBetween('blocked_on', [$calendarStart->toDateString(), $calendarEnd->toDateString()])
            ->orderBy('blocked_on')
            ->get(['id', 'blocked_on', 'note']);

        $blockedDatesThisMonth = $mp->blockedDates()
            ->whereBetween('blocked_on', [$monthStartStr, $monthEndStr])
            ->orderBy('blocked_on')
            ->get(['id', 'blocked_on', 'note']);

        $blockedSet = $blockedDates
            ->pluck('blocked_on')
            ->map(fn ($date) => Carbon::parse($date)->toDateString())
            ->flip();

        $bookingSet = collect();
        $calendarDetails = [];
        foreach ($calendarBookings as $bookingRow) {
            $cursor = Carbon::parse($bookingRow->starts_on)->startOfDay();
            $end = Carbon::parse($bookingRow->ends_on)->startOfDay();
            while ($cursor->lte($end)) {
                $dateKey = $cursor->toDateString();
                $bookingSet->put($dateKey, true);
                $calendarDetails[$dateKey]['bookings'] ??= [];
                $calendarDetails[$dateKey]['bookings'][] = [
                    'name' => $bookingRow->customer?->name ?? __('dashboard_muthowif.guest'),
                    'service' => $bookingRow->service_type?->label() ?? __('dashboard_muthowif.service'),
                    'service_short' => match ($bookingRow->service_type) {
                        MuthowifServiceType::Group => __('dashboard_muthowif.service_group'),
                        MuthowifServiceType::PrivateJamaah => __('dashboard_muthowif.service_private'),
                        default => __('dashboard_muthowif.service'),
                    },
                ];
                $cursor->addDay();
            }
        }

        foreach ($blockedDates as $blockedRow) {
            $dateKey = Carbon::parse($blockedRow->blocked_on)->toDateString();
            $calendarDetails[$dateKey]['blocked'] ??= [];
            $calendarDetails[$dateKey]['blocked'][] = $blockedRow->note ?: __('dashboard_muthowif.default_off_note');
        }

        return [
            'calendarMonth' => $calendarMonth,
            'calendarStart' => $calendarStart,
            'calendarEnd' => $calendarEnd,
            'monthStartStr' => $monthStartStr,
            'monthEndStr' => $monthEndStr,
            'calendarBookings' => $calendarBookings,
            'blockedDates' => $blockedDates,
            'blockedDatesThisMonth' => $blockedDatesThisMonth,
            'blockedSet' => $blockedSet,
            'bookingSet' => $bookingSet,
            'calendarDetails' => $calendarDetails,
        ];
    }
}
