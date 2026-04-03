<?php

namespace App\Enums;

enum BookingStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Menunggu',
            self::Confirmed => 'Terkonfirmasi',
            self::Completed => 'Selesai',
            self::Cancelled => 'Dibatalkan',
        };
    }

    /**
     * Status yang membuat slot tanggal dianggap terisi (tidak tampil di pencarian).
     */
    public static function blocksAvailability(): array
    {
        return [self::Pending, self::Confirmed];
    }
}
