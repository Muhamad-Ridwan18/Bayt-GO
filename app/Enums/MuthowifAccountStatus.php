<?php

namespace App\Enums;

enum MuthowifAccountStatus: string
{
    case Active = 'active';
    case Suspended = 'suspended';
    case Terminated = 'terminated';

    public function label(): string
    {
        return __('emergency.account_status.'.$this->value);
    }
}
