<?php

namespace App\Support;

use App\Events\WithdrawalUpdated;
use App\Models\MuthowifWithdrawal;
use Illuminate\Support\Facades\DB;

final class WithdrawalBroadcast
{
    public static function afterResponse(MuthowifWithdrawal|string $withdrawal): void
    {
        $id = (string) ($withdrawal instanceof MuthowifWithdrawal ? $withdrawal->getKey() : $withdrawal);
        if ($id === '') {
            return;
        }

        DB::afterCommit(static function () use ($id): void {
            dispatch(static function () use ($id): void {
                $row = MuthowifWithdrawal::query()->find($id);
                if ($row !== null) {
                    broadcast(new WithdrawalUpdated($row));
                }
            })->afterResponse();
        });
    }
}
