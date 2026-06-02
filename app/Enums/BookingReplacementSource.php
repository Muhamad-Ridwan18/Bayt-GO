<?php

namespace App\Enums;

enum BookingReplacementSource: string
{
    case Volunteer = 'volunteer';
    case AdminInvite = 'admin_invite';

    public function label(): string
    {
        return __('incidents.replacement_source.'.$this->value);
    }
}
