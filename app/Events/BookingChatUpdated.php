<?php

namespace App\Events;

use App\Events\Concerns\RescuesBroadcastFailures;
use App\Models\BookingChatMessage;
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
        $channels = [
            new PrivateChannel('booking.chat.'.$this->booking->id),
            new PrivateChannel('App.Models.User.'.$this->booking->customer_id),
        ];

        $this->booking->loadMissing('muthowifProfile');
        $muthowifUserId = $this->booking->muthowifProfile?->user_id;
        if ($muthowifUserId) {
            $channels[] = new PrivateChannel('App.Models.User.'.$muthowifUserId);
        }

        return $channels;
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        $preview = null;
        if ($this->action === 'message' && $this->messageId) {
            $message = BookingChatMessage::query()->find($this->messageId);
            if ($message !== null) {
                $body = trim((string) $message->body);
                $preview = $body !== '' ? $body : '📷 Gambar';
            }
        }

        return [
            'booking_id' => (string) $this->booking->getKey(),
            'action' => $this->action,
            'message_id' => $this->messageId,
            'sender_id' => $this->senderId,
            'preview' => $preview,
        ];
    }
}
