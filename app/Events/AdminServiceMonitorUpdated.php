<?php

namespace App\Events;

use App\Events\Concerns\RescuesBroadcastFailures;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AdminServiceMonitorUpdated implements ShouldBroadcastNow, RescuesBroadcastFailures
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public ?string $bookingId = null,
        public string $reason = 'updated',
    ) {}

    public function broadcastAs(): string
    {
        return 'service_monitor.updated';
    }

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('admin.service-monitor'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'booking_id' => $this->bookingId,
            'reason' => $this->reason,
            'at' => now()->toIso8601String(),
        ];
    }
}
