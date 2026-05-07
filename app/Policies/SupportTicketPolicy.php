<?php

namespace App\Policies;

use App\Enums\SupportTicketStatus;
use App\Models\SupportTicket;
use App\Models\User;

class SupportTicketPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * List own tickets — caller scopes by reporter; authorize after fetch.
     */
    public function create(User $user): bool
    {
        return $user->isCustomer() || $user->isMuthowif();
    }

    public function view(User $user, SupportTicket $ticket): bool
    {
        return $user->isAdmin()
            || $ticket->user_id === $user->getKey();
    }

    public function reply(User $user, SupportTicket $ticket): bool
    {
        if ($ticket->isClosed()) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return $ticket->user_id === $user->getKey()
            && in_array($ticket->status, [
                SupportTicketStatus::Open,
                SupportTicketStatus::InProgress,
                SupportTicketStatus::AwaitingCustomer,
                SupportTicketStatus::Resolved,
            ], true);
    }

    public function update(User $user, SupportTicket $ticket): bool
    {
        return $user->isAdmin();
    }

    /**
     * Admin takes ownership without changing status beyond optional rules in controller.
     */
    public function assign(User $user, SupportTicket $ticket): bool
    {
        return $user->isAdmin();
    }
}
