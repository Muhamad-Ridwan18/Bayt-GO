<?php

namespace App\Jobs;

use App\Models\MuthowifWithdrawal;
use App\Services\AdminWhatsAppNotifier;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class NotifyAdminsOfWithdrawalRequested implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function __construct(
        public string $withdrawalId,
    ) {}

    public static function afterWithdrawalRequested(?string $withdrawalId): void
    {
        if ($withdrawalId === null || $withdrawalId === '') {
            return;
        }

        try {
            self::dispatchAfterResponse($withdrawalId);
        } catch (Throwable $e) {
            Log::warning('withdrawal_admin_notify_dispatch_failed', [
                'withdrawal_id' => $withdrawalId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function handle(AdminWhatsAppNotifier $notifier): void
    {
        try {
            $withdrawal = MuthowifWithdrawal::query()->find($this->withdrawalId);
            if ($withdrawal) {
                $notifier->notifyWithdrawalRequested($withdrawal);
            }
        } catch (Throwable $e) {
            Log::warning('withdrawal_admin_notify_failed', [
                'withdrawal_id' => $this->withdrawalId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
