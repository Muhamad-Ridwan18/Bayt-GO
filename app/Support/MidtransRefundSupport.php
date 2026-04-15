<?php

namespace App\Support;

/**
 * Kanal yang mendukung refund otomatis via API Midtrans (Core API / Snap).
 * VA bank_transfer & echannel tidak termasuk — refund lewat prosedur manual / dashboard Midtrans.
 *
 * @see https://docs.midtrans.com/reference/refund-transaction
 */
final class MidtransRefundSupport
{
    /**
     * @var list<string>
     */
    private const SUPPORTED_PAYMENT_TYPES = [
        'credit_card',
        'gopay',
        'shopeepay',
        'dana',
        'ovo',
        'qris',
        'kredivo',
        'akulaku',
    ];

    public static function supportsAutomaticRefund(?string $paymentType): bool
    {
        if ($paymentType === null || $paymentType === '') {
            return false;
        }

        return in_array(strtolower($paymentType), self::SUPPORTED_PAYMENT_TYPES, true);
    }

    /**
     * ShopeePay & OVO tidak mendukung partial refund via API (dokumentasi Midtrans).
     */
    public static function allowsPartialRefund(?string $paymentType): bool
    {
        if ($paymentType === null) {
            return true;
        }

        $t = strtolower($paymentType);

        return ! in_array($t, ['shopeepay', 'ovo'], true);
    }
}
