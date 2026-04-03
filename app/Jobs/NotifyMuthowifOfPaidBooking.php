<?php

namespace App\Jobs;

use App\Models\MuthowifBooking;
use App\Services\MuthowifBookingWhatsAppNotifier;
use Illuminate\Foundation\Bus\Dispatchable;

class NotifyMuthowifOfPaidBooking
{
    use Dispatchable;

    public function __construct(
        public string $bookingId
    ) {}

    public function handle(MuthowifBookingWhatsAppNotifier $notifier): void
    {
        $booking = MuthowifBooking::query()->find($this->bookingId);
        if ($booking) {
            $notifier->notifyPaymentSettled($booking);
        }
    }
}
