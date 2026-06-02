<?php

namespace App\Enums;

enum PayoutAllocationRole: string
{
    case Primary = 'primary';
    case Replacement = 'replacement';
    case Penalty = 'penalty';
}
