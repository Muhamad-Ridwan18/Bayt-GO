<?php

namespace App\Services;

use App\Jobs\NotifyCustomerOfBookingSubmitted;
use App\Jobs\NotifyMuthowifOfNewBooking;
use App\Models\MuthowifBooking;
use Illuminate\Support\Facades\Log;

final class BookingNotificationDispatcher
{
    public function dispatchCreated(MuthowifBooking|string $booking): void
    {
        $bookingId = (string) ($booking instanceof MuthowifBooking ? $booking->getKey() : $booking);
        $connection = (string) config('queue.default', 'redis');

        NotifyMuthowifOfNewBooking::dispatch($bookingId)->onConnection($connection);
        NotifyCustomerOfBookingSubmitted::dispatch($bookingId)->onConnection($connection);

        Log::info('booking.notifications.queued', [
            'booking_id' => $bookingId,
            'connection' => $connection,
        ]);
    }
}
