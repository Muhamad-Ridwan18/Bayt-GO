<?php

namespace App\Enums;

enum EmergencyOverlayStatus: string
{
    case None = 'none';
    case Reported = 'reported';
    case ReplacementActive = 'replacement_active';
    case Resolved = 'resolved';

    public function label(): string
    {
        return __('emergency.overlay.'.$this->value);
    }
}
