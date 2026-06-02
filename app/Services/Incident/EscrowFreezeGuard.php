<?php

namespace App\Services\Incident;

use App\Enums\BookingIncidentOverlayStatus;
use App\Enums\BookingIncidentStatus;
use App\Models\MuthowifBooking;

final class EscrowFreezeGuard
{
    public static function isFrozen(MuthowifBooking $booking): bool
    {
        if ($booking->incident_status === BookingIncidentOverlayStatus::Open) {
            return true;
        }

        return $booking->incidents()
            ->whereIn('status', array_map(
                fn (BookingIncidentStatus $s) => $s->value,
                array_filter(
                    BookingIncidentStatus::cases(),
                    fn (BookingIncidentStatus $s) => $s->isOpen()
                )
            ))
            ->exists();
    }
}
