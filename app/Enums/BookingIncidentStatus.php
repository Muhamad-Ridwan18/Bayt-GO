<?php

namespace App\Enums;

enum BookingIncidentStatus: string
{
    case Draft = 'draft';
    case Open = 'open';
    case Triage = 'triage';
    case Investigating = 'investigating';
    case AwaitingReplacement = 'awaiting_replacement';
    case AwaitingCustomer = 'awaiting_customer';
    case Resolved = 'resolved';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return __('incidents.status.'.$this->value);
    }

    public function isOpen(): bool
    {
        return ! in_array($this, [self::Resolved, self::Cancelled], true);
    }
}
