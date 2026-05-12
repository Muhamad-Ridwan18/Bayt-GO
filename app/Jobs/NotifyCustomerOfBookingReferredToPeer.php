<?php

namespace App\Jobs;

use App\Models\MuthowifBooking;
use App\Services\MuthowifBookingWhatsAppNotifier;
use Illuminate\Foundation\Bus\Dispatchable;

class NotifyCustomerOfBookingReferredToPeer
{
    use Dispatchable;

    public function __construct(
        public string $bookingId,
        public string $previousMuthowifName,
    ) {}

    public function handle(MuthowifBookingWhatsAppNotifier $notifier): void
    {
        $booking = MuthowifBooking::query()->find($this->bookingId);
        if ($booking !== null) {
            $notifier->notifyCustomerReferredToPeer($booking, $this->previousMuthowifName);
        }
    }
}
