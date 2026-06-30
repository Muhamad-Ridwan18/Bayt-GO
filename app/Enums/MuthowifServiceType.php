<?php

namespace App\Enums;

enum MuthowifServiceType: string
{
    case Group = 'group';
    case PrivateJamaah = 'private';
    case Support = 'support';

    public function label(): string
    {
        return __('enums.muthowif_service_type.'.$this->value);
    }
}
