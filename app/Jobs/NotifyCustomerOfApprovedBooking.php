<?php

namespace App\Jobs;

use App\Models\MuthowifBooking;
use App\Services\MuthowifBookingWhatsAppNotifier;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NotifyCustomerOfApprovedBooking
{
    use Dispatchable, Queueable, SerializesModels;

    public function __construct(
        public string $bookingId
    ) {}

    public function handle(MuthowifBookingWhatsAppNotifier $notifier): void
    {
        $booking = MuthowifBooking::query()->find($this->bookingId);
        if ($booking) {
            $notifier->notifyCustomerApproved($booking);
        }
    }
}
