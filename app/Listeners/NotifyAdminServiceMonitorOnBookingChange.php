<?php

namespace App\Listeners;

use App\Events\CustomerBookingUpdated;
use App\Support\AdminServiceMonitorBroadcast;

final class NotifyAdminServiceMonitorOnBookingChange
{
    public function handle(CustomerBookingUpdated $event): void
    {
        AdminServiceMonitorBroadcast::notify($event->booking, 'booking_updated');
    }
}
