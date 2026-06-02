<?php

namespace App\Enums;

enum BookingServicePhase: string
{
    case PreService = 'pre_service';
    case InService = 'in_service';
    case PostService = 'post_service';

    public function label(): string
    {
        return __('incidents.service_phase.'.$this->value);
    }
}
