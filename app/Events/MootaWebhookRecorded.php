<?php

namespace App\Events;

use App\Models\MootaWebhookHistory;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MootaWebhookRecorded implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public MootaWebhookHistory $history) {}

    public function broadcastAs(): string
    {
        return 'moota.webhook.recorded';
    }

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [new PrivateChannel('admin.moota-webhooks')];
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            // Potong preview agar websocket ringan (~400–900 karakter biasanya).
            'webhook' => $this->history->toRealtimeSnapshot(900),
        ];
    }
}
