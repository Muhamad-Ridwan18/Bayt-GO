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
        return __('enums.booking_status.'.$this->value);
    }

    /**
     * Status yang membuat slot tanggal dianggap terisi (disembunyikan dari pencarian / tidak bisa dibooking).
     * Pending sengaja tidak ikut: muthowif belum menyetujui, slot tetap terbuka untuk pesanan lain;
     * saat menyetujui, konfirmasi harus mengecek bentrok dengan booking confirmed lain.
     */
    public static function blocksAvailability(): array
    {
        return [self::Confirmed];
    }
}
