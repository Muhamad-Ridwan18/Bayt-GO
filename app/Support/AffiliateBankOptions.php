<?php

namespace App\Support;

final class AffiliateBankOptions
{
    /** @return array<string, string> */
    public static function all(): array
    {
        return [
            'BCA' => 'Bank Central Asia (BCA)',
            'BNI' => 'Bank Negara Indonesia (BNI)',
            'BRI' => 'Bank Rakyat Indonesia (BRI)',
            'Mandiri' => 'Bank Mandiri',
            'BSI' => 'Bank Syariah Indonesia (BSI)',
            'CIMB Niaga' => 'CIMB Niaga',
            'Permata' => 'Permata Bank',
            'Danamon' => 'Bank Danamon',
            'BTN' => 'Bank BTN',
            'OCBC NISP' => 'OCBC NISP',
            'Maybank' => 'Maybank Indonesia',
            'Bank Muamalat' => 'Bank Muamalat',
        ];
    }

    public static function label(string $code): string
    {
        return self::all()[$code] ?? $code;
    }
}
