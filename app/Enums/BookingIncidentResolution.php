<?php

namespace App\Enums;

enum BookingIncidentResolution: string
{
    case ReplacementCompleted = 'replacement_completed';
    case FullRefund = 'full_refund';
    case PartialRefund = 'partial_refund';
    case ServiceContinued = 'service_continued';
    case NoAction = 'no_action';
    case FalseAlarm = 'false_alarm';

    public function label(): string
    {
        return __('incidents.resolution.'.$this->value);
    }
}
