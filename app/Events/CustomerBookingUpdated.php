<?php

namespace App\Events;

use App\Models\MuthowifBooking;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CustomerBookingUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public MuthowifBooking $booking) {}

    public function broadcastAs(): string
    {
        return 'booking.updated';
    }

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        $this->booking->loadMissing('muthowifProfile');

        $channels = [
            new PrivateChannel('App.Models.User.'.$this->booking->customer_id),
        ];

        $muthowifUserId = $this->booking->muthowifProfile?->user_id;
        if ($muthowifUserId !== null
            && (string) $muthowifUserId !== (string) $this->booking->customer_id) {
            $channels[] = new PrivateChannel('App.Models.User.'.$muthowifUserId);
        }

        return $channels;
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        $b = $this->booking;

        return [
            'booking_id' => (string) $b->getKey(),
            'status' => $b->status->value,
            'payment_status' => $b->payment_status->value,
            'total_amount' => $b->total_amount !== null ? (string) $b->total_amount : null,
        ];
    }
}
