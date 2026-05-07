<?php

namespace App\Enums;

enum SupportTicketCategory: string
{
    case Bug = 'bug';
    case Booking = 'booking';
    case Payment = 'payment';
    case Account = 'account';
    case Suggestion = 'suggestion';
    case Other = 'other';

    public function label(): string
    {
        return __('enums.support_ticket_category.'.$this->value);
    }
}
