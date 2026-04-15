<?php

namespace App\Enums;

enum MuthowifVerificationStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return __('enums.muthowif_verification_status.'.$this->value);
    }
}
