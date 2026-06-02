<?php

namespace App\Events;

use App\Events\Concerns\RescuesBroadcastFailures;
use App\Models\MuthowifWithdrawal;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WithdrawalRequested implements ShouldBroadcastNow, RescuesBroadcastFailures
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public MuthowifWithdrawal $withdrawal) {}

    public function broadcastAs(): string
    {
        return 'withdrawal.requested';
    }

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('admin.withdrawals'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        $this->withdrawal->loadMissing('muthowifProfile.user');

        $pendingAgg = MuthowifWithdrawal::query()
            ->where('status', 'pending_approval')
            ->selectRaw('COUNT(*) as pending_count, COALESCE(SUM(amount), 0) as pending_amount')
            ->first();

        return [
            'withdrawal_id'  => (string) $this->withdrawal->getKey(),
            'muthowif_name'  => $this->withdrawal->muthowifProfile?->user?->name ?? 'Muthowif',
            'amount'         => (float) $this->withdrawal->amount,
            'pending_count'  => (int) ($pendingAgg->pending_count ?? 0),
            'pending_amount' => (float) ($pendingAgg->pending_amount ?? 0),
        ];
    }
}
