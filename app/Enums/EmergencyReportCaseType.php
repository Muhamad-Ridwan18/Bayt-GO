<?php

namespace App\Enums;

enum EmergencyReportCaseType: string
{
    case Unreachable = 'unreachable';
    case Abandoned = 'abandoned';
    case ServiceBreach = 'service_breach';
    case Other = 'other';

    public function label(): string
    {
        return __('emergency.case_type.'.$this->value);
    }
}
