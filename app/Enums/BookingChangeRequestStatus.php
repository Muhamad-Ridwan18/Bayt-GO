<?php

namespace App\Enums;

enum BookingChangeRequestStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Menunggu keputusan',
            self::Approved => 'Disetujui',
            self::Rejected => 'Ditolak',
        };
    }
}
