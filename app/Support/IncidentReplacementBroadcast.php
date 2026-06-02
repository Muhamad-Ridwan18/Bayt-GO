<?php

namespace App\Support;

use App\Events\BookingReplacementPoolUpdated;
use App\Models\BookingIncident;

final class IncidentReplacementBroadcast
{
    public static function poolUpdated(BookingIncident $incident, string $reason = 'pool_changed'): void
    {
        $fresh = $incident->fresh()->loadMissing('muthowifBooking');
        broadcast(new BookingReplacementPoolUpdated($fresh, $reason));
        AdminServiceMonitorBroadcast::notify($fresh->muthowifBooking, 'replacement_pool');
    }
}
