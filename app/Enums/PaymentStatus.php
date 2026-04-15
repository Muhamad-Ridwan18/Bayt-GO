<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    /** Refund diajukan; admin akan transfer manual ke jamaah. */
    case RefundPending = 'refund_pending';
    case Refunded = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Menunggu pembayaran',
            self::Paid => 'Lunas',
            self::RefundPending => 'Refund menunggu transfer admin',
            self::Refunded => 'Dikembalikan (refund)',
        };
    }
}
