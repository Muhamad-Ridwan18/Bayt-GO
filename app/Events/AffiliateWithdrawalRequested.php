<?php

namespace App\Events;

use App\Models\AffiliateWithdrawal;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AffiliateWithdrawalRequested implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public AffiliateWithdrawal $withdrawal,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('admin.affiliate-withdrawals'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'affiliate.withdrawal.requested';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->withdrawal->id,
            'affiliate_id' => $this->withdrawal->affiliate_id,
            'amount' => (float) $this->withdrawal->amount,
            'status' => $this->withdrawal->status->value,
        ];
    }
}
