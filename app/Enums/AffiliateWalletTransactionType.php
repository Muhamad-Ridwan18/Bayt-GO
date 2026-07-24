<?php

namespace App\Enums;

enum AffiliateWalletTransactionType: string
{
    case CommissionCredit = 'commission_credit';
    case CommissionReversal = 'commission_reversal';
    case WithdrawDebit = 'withdraw_debit';
    case WithdrawRelease = 'withdraw_release';

    public function label(): string
    {
        return match ($this) {
            self::CommissionCredit => 'Komisi masuk',
            self::CommissionReversal => 'Komisi dibatalkan',
            self::WithdrawDebit => 'Withdraw',
            self::WithdrawRelease => 'Withdraw dikembalikan',
        };
    }
}
