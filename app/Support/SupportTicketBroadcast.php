<?php

namespace App\Support;

use App\Events\SupportTicketUpdated;
use App\Models\SupportTicket;

final class SupportTicketBroadcast
{
    public static function notify(SupportTicket|string $ticket, ?string $action = null): void
    {
        $model = $ticket instanceof SupportTicket
            ? $ticket
            : SupportTicket::query()->find((string) $ticket);

        if ($model !== null) {
            ReverbBroadcast::send(new SupportTicketUpdated($model, $action), 'support_ticket');
        }
    }

    public static function afterResponse(SupportTicket $ticket, ?string $action = null): void
    {
        self::notify($ticket, $action);
    }
}
