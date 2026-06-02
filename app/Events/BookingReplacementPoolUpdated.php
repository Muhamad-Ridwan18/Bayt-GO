<?php

namespace App\Events;

use App\Enums\BookingReplacementStatus;
use App\Events\Concerns\RescuesBroadcastFailures;
use App\Models\BookingIncident;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BookingReplacementPoolUpdated implements ShouldBroadcastNow, RescuesBroadcastFailures
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public BookingIncident $incident,
        public string $reason = 'pool_changed',
    ) {}

    public function broadcastAs(): string
    {
        return 'incident.replacement_pool.updated';
    }

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        $this->incident->loadMissing('muthowifBooking');

        $booking = $this->incident->muthowifBooking;
        $channels = [
            new PrivateChannel('admin.incidents'),
            new PrivateChannel('muthowif.recruitment'),
        ];

        if ($booking?->customer_id) {
            $channels[] = new PrivateChannel('App.Models.User.'.$booking->customer_id);
        }

        return $channels;
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        $booking = $this->incident->muthowifBooking;

        return [
            'incident_id' => (string) $this->incident->getKey(),
            'booking_id' => $booking ? (string) $booking->getKey() : null,
            'booking_code' => $booking?->booking_code,
            'candidate_count' => $this->incident->replacements()
                ->whereIn('status', array_map(
                    fn (BookingReplacementStatus $s) => $s->value,
                    BookingReplacementStatus::customerSelectable()
                ))
                ->count(),
            'customer_choice_open' => $this->incident->customer_choice_opened_at !== null,
            'recruitment_open' => (bool) $this->incident->replacement_recruitment_open,
            'reason' => $this->reason,
        ];
    }
}
