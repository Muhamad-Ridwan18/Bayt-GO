<?php

namespace App\Jobs;

use App\Models\BookingRefundRequest;
use App\Services\MuthowifBookingWhatsAppNotifier;
use Illuminate\Foundation\Bus\Dispatchable;

class NotifyCustomerOfRefundTransferProof
{
    use Dispatchable;

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
