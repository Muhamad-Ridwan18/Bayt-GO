<?php

namespace App\Enums;

enum BookingSettlementStatus: string
{
    case Draft = 'draft';
    case Approved = 'approved';
    case Released = 'released';
    case Void = 'void';
}
