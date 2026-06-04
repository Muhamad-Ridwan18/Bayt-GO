<?php

namespace App\Enums;

enum ReplacementOfferStatus: string
{
    case Offered = 'offered';
    case Accepted = 'accepted';
    case Declined = 'declined';
    case Expired = 'expired';
    case Selected = 'selected';
    case Superseded = 'superseded';

    public function label(): string
    {
        return __('emergency.offer_status.'.$this->value);
    }

    public function isCustomerSelectable(): bool
    {
        return $this === self::Accepted;
    }
}
