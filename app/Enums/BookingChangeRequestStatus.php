<?php

namespace App\Enums;

enum BookingChangeRequestStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return __('enums.booking_change_request_status.'.$this->value);
    }
}
