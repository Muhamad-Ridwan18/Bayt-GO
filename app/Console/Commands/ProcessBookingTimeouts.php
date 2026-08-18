<?php

namespace App\Console\Commands;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Models\MuthowifBooking;
use App\Services\CancelUnpaidBookingAfterPaymentDeadline;
use Illuminate\Console\Command;

class ProcessBookingTimeouts extends Command
{
    protected $signature = 'bookings:process-timeouts';

    protected $description = 'Auto-cancel booking confirmed+unpaid yang lewat payment_due_at';

    public function handle(CancelUnpaidBookingAfterPaymentDeadline $canceller): int
    {
        $ids = MuthowifBooking::query()
            ->where('status', BookingStatus::Confirmed)
            ->where('payment_status', PaymentStatus::Pending)
            ->whereNotNull('payment_due_at')
            ->where('payment_due_at', '<=', now())
            ->orderBy('payment_due_at')
            ->limit(200)
            ->pluck('id');

        $cancelled = 0;
        foreach ($ids as $id) {
            $booking = MuthowifBooking::query()->find($id);
            if ($booking === null) {
                continue;
            }

            if ($canceller->cancelIfOverdue($booking)) {
                $cancelled++;
            }
        }

        if ($cancelled > 0) {
            $this->info("Cancelled {$cancelled} unpaid booking(s) past payment deadline.");
        }

        return self::SUCCESS;
    }
}
