<?php

namespace App\Enums;

enum BookingSettlementType: string
{
    case NormalCompletion = 'normal_completion';
    case IncidentSplit = 'incident_split';
    case FullRefund = 'full_refund';
    case PartialRefund = 'partial_refund';
}
