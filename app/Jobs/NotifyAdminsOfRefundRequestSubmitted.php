<?php

namespace App\Jobs;

use App\Models\BookingRefundRequest;
use App\Services\AdminWhatsAppNotifier;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class NotifyAdminsOfRefundRequestSubmitted implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function __construct(
        public string $refundId,
    ) {}

    public static function afterRefundSubmitted(?string $refundId): void
    {
        if ($refundId === null || $refundId === '') {
            return;
        }

        try {
            self::dispatchAfterResponse($refundId);
        } catch (Throwable $e) {
            Log::warning('refund_admin_notify_dispatch_failed', [
                'refund_id' => $refundId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function handle(AdminWhatsAppNotifier $notifier): void
    {
        try {
            $refund = BookingRefundRequest::query()->find($this->refundId);
            if ($refund) {
                $notifier->notifyRefundRequestSubmitted($refund);
            }
        } catch (Throwable $e) {
            Log::warning('refund_admin_notify_failed', [
                'refund_id' => $this->refundId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
