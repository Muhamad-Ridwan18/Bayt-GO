<?php

namespace App\Support;

use App\Events\AdminServiceMonitorUpdated;
use App\Models\MuthowifBooking;

final class AdminServiceMonitorBroadcast
{
    public static function notify(?MuthowifBooking $booking = null, string $reason = 'updated'): void
    {
        broadcast(new AdminServiceMonitorUpdated(
            $booking?->getKey() !== null ? (string) $booking->getKey() : null,
            $reason,
        ));
    }
}
