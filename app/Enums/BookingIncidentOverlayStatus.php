<?php

namespace App\Enums;

enum BookingIncidentOverlayStatus: string
{
    case None = 'none';
    case Monitoring = 'monitoring';
    case Open = 'open';
    case Resolved = 'resolved';

    public function label(): string
    {
        return __('incidents.overlay.'.$this->value);
    }
}
