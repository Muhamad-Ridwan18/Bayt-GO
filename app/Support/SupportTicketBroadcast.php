<?php

namespace App\Support;

use App\Events\SupportTicketUpdated;
use App\Models\SupportTicket;
use Illuminate\Support\Facades\DB;

final class SupportTicketBroadcast
{
    public static function afterResponse(SupportTicket $ticket, ?string $action = null): void
    {
        $ticketId = (string) $ticket->getKey();
        if ($ticketId === '') {
            return;
        }

        DB::afterCommit(static function () use ($ticketId, $action): void {
            dispatch(static function () use ($ticketId, $action): void {
                $ticket = SupportTicket::query()->find($ticketId);
                if ($ticket !== null) {
                    broadcast(new SupportTicketUpdated($ticket, $action));
                }
            })->afterResponse();
        });
    }
}
