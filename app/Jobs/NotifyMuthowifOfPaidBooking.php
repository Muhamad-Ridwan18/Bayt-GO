<?php

namespace App\Jobs;

use App\Models\MuthowifBooking;
use App\Services\MuthowifBookingWhatsAppNotifier;
use App\Services\SupportBookingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class NotifyMuthowifOfPaidBooking implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $bookingId
    ) {}

    public function handle(MuthowifBookingWhatsAppNotifier $notifier, SupportBookingService $support): void
    {
        $booking = MuthowifBooking::query()->find($this->bookingId);
        if (! $booking) {
            return;
        }

        if ($booking->isSupport()) {
            $support->issueCompletionCodeAfterPayment($booking);
            $booking = $booking->fresh();
        }

        $notifier->notifyPaymentSettled($booking);
    }
}
