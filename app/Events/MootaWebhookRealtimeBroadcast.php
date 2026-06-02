<?php

namespace App\Events;

use App\Events\Concerns\RescuesBroadcastFailures;
use App\Models\MootaWebhookHistory;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Live feed admin — dipanggil dari controller SETELAH event {@see MootaWebhookRecorded},
 * supaya gagal broadcast (Reverb mati) tidak memblokir listener settlement pembayaran.
 */
class MootaWebhookRealtimeBroadcast implements ShouldBroadcastNow, RescuesBroadcastFailures
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
            'webhook' => $this->history->toRealtimeSnapshot(1600, 45000),
        ];
    }
}
