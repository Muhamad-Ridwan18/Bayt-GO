<?php

namespace App\Enums;

enum AffiliateCommissionStatus: string
{
    case Pending = 'pending';
    case Available = 'available';
    case Void = 'void';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Available => 'Available',
            self::Void => 'Void',
        };
    }
}
