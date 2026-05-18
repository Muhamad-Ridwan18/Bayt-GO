<?php

namespace App\Jobs;

use App\Models\MuthowifWithdrawal;
use App\Services\MuthowifBookingWhatsAppNotifier;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class NotifyMuthowifOfWithdrawalTransferProof implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $withdrawalId
    ) {}

    public function handle(MuthowifBookingWhatsAppNotifier $notifier): void
    {
        $withdrawal = MuthowifWithdrawal::query()->find($this->withdrawalId);
        if ($withdrawal === null || $withdrawal->transfer_proof_path === null || $withdrawal->transfer_proof_path === '') {
            return;
        }

        if ($withdrawal->status !== 'succeeded') {
            return;
        }

        $notifier->notifyMuthowifWithdrawalTransferCompleted($withdrawal);
    }
}
