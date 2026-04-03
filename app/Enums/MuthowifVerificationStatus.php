<?php

namespace App\Enums;

enum MuthowifVerificationStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Menunggu verifikasi',
            self::Approved => 'Terverifikasi',
            self::Rejected => 'Ditolak',
        };
    }
}
