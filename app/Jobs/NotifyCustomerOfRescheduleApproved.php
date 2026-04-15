<?php

namespace App\Jobs;

use App\Models\BookingRescheduleRequest;
use App\Models\MuthowifBooking;
use App\Services\MuthowifBookingWhatsAppNotifier;
use Illuminate\Foundation\Bus\Dispatchable;

class NotifyCustomerOfRescheduleApproved
{
    use Dispatchable;

    public function __construct(
        public string $bookingId,
        public string $rescheduleRequestId
    ) {}

    public function handle(MuthowifBookingWhatsAppNotifier $notifier): void
    {
        $booking = MuthowifBooking::query()->find($this->bookingId);
        $request = BookingRescheduleRequest::query()->find($this->rescheduleRequestId);
        if ($booking && $request && (string) $request->muthowif_booking_id === (string) $booking->getKey()) {
            $booking->refresh();
            $notifier->notifyCustomerRescheduleApproved($booking, $request);
        }
    }
}
