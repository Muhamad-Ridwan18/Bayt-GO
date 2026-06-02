<?php

namespace App\Events;

use App\Events\Concerns\RescuesBroadcastFailures;
use App\Models\SupportTicket;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SupportTicketUpdated implements ShouldBroadcastNow, RescuesBroadcastFailures
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public SupportTicket $ticket,
        public ?string $action = null
    ) {}

    public function broadcastAs(): string
    {
        return 'support.ticket.updated';
    }

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('admin.support-tickets'),
            new PrivateChannel("App.Models.User.{$this->ticket->user_id}"),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'ticket_id' => (string) $this->ticket->getKey(),
            'code' => $this->ticket->code,
            'subject' => $this->ticket->subject,
            'status' => $this->ticket->status instanceof \App\Enums\SupportTicketStatus 
                ? $this->ticket->status->value 
                : (string) $this->ticket->status,
            'action' => $this->action,
            'last_activity_at' => $this->ticket->last_activity_at?->toDateTimeString(),
        ];
    }
}
