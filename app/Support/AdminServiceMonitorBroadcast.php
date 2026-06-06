<?php

namespace App\Support;

use App\Events\AdminServiceMonitorUpdated;
use App\Models\MuthowifBooking;
use Illuminate\Support\Facades\DB;

final class AdminServiceMonitorBroadcast
{
    public static function notify(?MuthowifBooking $booking = null, string $reason = 'updated'): void
    {
        $bookingId = $booking?->getKey() !== null ? (string) $booking->getKey() : null;

        DB::afterCommit(static function () use ($bookingId, $reason): void {
            dispatch(static function () use ($bookingId, $reason): void {
                broadcast(new AdminServiceMonitorUpdated($bookingId, $reason));
            })->afterResponse();
        });
    }
}
