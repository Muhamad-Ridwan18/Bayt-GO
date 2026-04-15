<?php

namespace App\Services;

use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/**
 * Format: BK-BYTG + yymmdd (tahun 2 digit) + urutan harian tanpa batas (reset per hari, timezone aplikasi).
 * Panggil hanya di dalam {@see DB::transaction} agar penomoran aman dari race condition.
 */
final class BookingOrderCodeService
{
    private const PREFIX = 'BK-BYTG';

    public function allocateNextWithinTransaction(?CarbonInterface $moment = null): string
    {
        $at = ($moment ?? now())->copy()->timezone(config('app.timezone'));
        $ymd = $at->format('ymd');

        DB::table('booking_daily_sequences')->insertOrIgnore([
            'date_key' => $ymd,
            'next_seq' => 0,
        ]);

        $row = DB::table('booking_daily_sequences')
            ->where('date_key', $ymd)
            ->lockForUpdate()
            ->first();

        if ($row === null) {
            throw new \RuntimeException('Gagal mengalokasi nomor booking.');
        }

        $seq = (int) $row->next_seq + 1;

        DB::table('booking_daily_sequences')
            ->where('date_key', $ymd)
            ->update(['next_seq' => $seq]);

        return self::PREFIX.$ymd.(string) $seq;
    }
}
