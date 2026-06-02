<?php

namespace App\Enums;

enum BookingIncidentSeverity: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';
    case Critical = 'critical';

    public function label(): string
    {
        return __('incidents.severity.'.$this->value);
    }
}
