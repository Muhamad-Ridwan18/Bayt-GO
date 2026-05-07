<?php

namespace App\Enums;

enum SupportTicketPriority: string
{
    case Low = 'low';
    case Normal = 'normal';
    case High = 'high';

    public function label(): string
    {
        return __('enums.support_ticket_priority.'.$this->value);
    }
}
