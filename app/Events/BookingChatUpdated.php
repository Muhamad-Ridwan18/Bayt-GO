<?php

namespace App\Events;

use App\Events\Concerns\RescuesBroadcastFailures;
use App\Models\MuthowifBooking;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BookingChatUpdated implements ShouldBroadcastNow, RescuesBroadcastFailures
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public MuthowifBooking $booking,
        public string $action = 'message',
        public ?string $messageId = null,
        public ?string $senderId = null,
    ) {}

    public function broadcastAs(): string
    {
        return 'chat.updated';
    }

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('booking.chat.'.$this->booking->id),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'booking_id' => (string) $this->booking->getKey(),
            'action' => $this->action,
            'message_id' => $this->messageId,
            'sender_id' => $this->senderId,
        ];
    }
}
