<?php

namespace App\Enums;

enum BookingIncidentCaseType: string
{
    case MuthowifUnavailable = 'muthowif_unavailable';
    case LostContactInService = 'lost_contact_in_service';
    case AbandonedService = 'abandoned_service';
    case NoShow = 'no_show';
    case LastMinuteCancel = 'last_minute_cancel';
    case ForceMajeure = 'force_majeure';
    case FalseAlarm = 'false_alarm';

    public function label(): string
    {
        return __('incidents.case_type.'.$this->value);
    }
}
