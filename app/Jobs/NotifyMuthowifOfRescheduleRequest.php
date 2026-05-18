<?php

namespace App\Jobs;

use App\Models\BookingRescheduleRequest;
use App\Models\MuthowifBooking;
use App\Services\MuthowifBookingWhatsAppNotifier;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class NotifyMuthowifOfRescheduleRequest implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $bookingId,
        public string $rescheduleRequestId
    ) {}

    public function handle(MuthowifBookingWhatsAppNotifier $notifier): void
    {
        $booking = MuthowifBooking::query()->find($this->bookingId);
        $request = BookingRescheduleRequest::query()->find($this->rescheduleRequestId);
        if ($booking && $request && (string) $request->muthowif_booking_id === (string) $booking->getKey()) {
            $notifier->notifyMuthowifRescheduleRequested($booking, $request);
        }
    }
}
