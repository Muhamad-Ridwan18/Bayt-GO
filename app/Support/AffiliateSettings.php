<?php

namespace App\Support;

use App\Models\SiteSetting;

final class AffiliateSettings
{
    public const RATE_KEY = 'affiliate_commission_rate';

    public const MIN_WITHDRAW_KEY = 'affiliate_min_withdraw';

    public const DEFAULT_RATE = 0.01;

    public const DEFAULT_MIN_WITHDRAW = 100000.0;

    public static function getRate(): float
    {
        return (float) SiteSetting::getValue(self::RATE_KEY, (string) self::DEFAULT_RATE);
    }

    public static function getMinWithdraw(): float
    {
        return (float) SiteSetting::getValue(self::MIN_WITHDRAW_KEY, (string) self::DEFAULT_MIN_WITHDRAW);
    }

    public static function putRate(float $rate): void
    {
        SiteSetting::putValue(self::RATE_KEY, (string) round($rate, 6));
    }

    public static function putMinWithdraw(float $amount): void
    {
        SiteSetting::putValue(self::MIN_WITHDRAW_KEY, (string) round($amount, 2));
    }
}
