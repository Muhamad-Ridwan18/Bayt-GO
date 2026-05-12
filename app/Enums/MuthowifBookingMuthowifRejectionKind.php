<?php

namespace App\Enums;

enum MuthowifBookingMuthowifRejectionKind: string
{
    case SlotFull = 'slot_full';
    case Other = 'other';

    public function label(): string
    {
        return __('enums.muthowif_booking_muthowif_rejection_kind.'.$this->value);
    }
}
