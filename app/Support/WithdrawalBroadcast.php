<?php

namespace App\Support;

use App\Events\WithdrawalUpdated;
use App\Models\MuthowifWithdrawal;

final class WithdrawalBroadcast
{
    public static function notify(MuthowifWithdrawal|string $withdrawal): void
    {
        $model = $withdrawal instanceof MuthowifWithdrawal
            ? $withdrawal
            : MuthowifWithdrawal::query()->find((string) $withdrawal);

        if ($model !== null) {
            ReverbBroadcast::send(new WithdrawalUpdated($model), 'withdrawal');
        }
    }

    public static function afterResponse(MuthowifWithdrawal|string $withdrawal): void
    {
        self::notify($withdrawal);
    }
}
