<?php

namespace App\Events;

use App\Models\MuthowifBooking;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BookingChatUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public MuthowifBooking $booking)
    {
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('booking.chat.' . $this->booking->id),
        ];
    }
}
