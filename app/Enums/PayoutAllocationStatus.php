<?php

namespace App\Enums;

enum PayoutAllocationStatus: string
{
    case Pending = 'pending';
    case Released = 'released';
    case Reversed = 'reversed';
}
