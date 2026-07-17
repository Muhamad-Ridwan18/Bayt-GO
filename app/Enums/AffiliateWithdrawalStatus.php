<?php

namespace App\Enums;

enum AffiliateWithdrawalStatus: string
{
    case Requested = 'requested';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Paid = 'paid';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Requested => 'Requested',
            self::Approved => 'Approved',
            self::Rejected => 'Rejected',
            self::Paid => 'Paid',
            self::Failed => 'Failed',
        };
    }
}
