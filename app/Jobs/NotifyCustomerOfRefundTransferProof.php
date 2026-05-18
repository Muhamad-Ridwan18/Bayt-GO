<?php

namespace App\Jobs;

use App\Models\BookingRefundRequest;
use App\Services\MuthowifBookingWhatsAppNotifier;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class NotifyCustomerOfRefundTransferProof implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $refundRequestId
    ) {}

    public function handle(MuthowifBookingWhatsAppNotifier $notifier): void
    {
        $refund = BookingRefundRequest::query()->find($this->refundRequestId);
        if ($refund === null || $refund->transfer_proof_path === null || $refund->transfer_proof_path === '') {
            return;
        }

        $notifier->notifyCustomerRefundTransferCompleted($refund);
    }
}
