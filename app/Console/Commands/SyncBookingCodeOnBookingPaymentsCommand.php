<?php

namespace App\Console\Commands;

use App\Models\BookingPayment;
use App\Models\MuthowifBooking;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Mengisi booking_payments.booking_code dari muthowif_bookings.booking_code untuk baris yang ada.
 */
final class SyncBookingCodeOnBookingPaymentsCommand extends Command
{
    protected $signature = 'booking-payments:sync-booking-code
                            {--dry-run : Hitung saja; tidak menulis ke database}';

    protected $description = 'Sinkronkan booking_payments.booking_code dari muthowif_bookings';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');

        if ($dry) {
            return $this->runDry();
        }

        return $this->runBulkUpdate();
    }

    private function runDry(): int
    {
        $outOfSync = 0;
        $missingBooking = 0;
        $scanned = 0;

        BookingPayment::query()
            ->orderBy('id')
            ->chunkById(500, function ($payments) use (&$outOfSync, &$missingBooking, &$scanned) {
                $scanned += $payments->count();
                $bookingIds = $payments->pluck('muthowif_booking_id')->unique()->values();
                $codes = MuthowifBooking::query()
                    ->whereIn('id', $bookingIds)
                    ->pluck('booking_code', 'id');

                foreach ($payments as $payment) {
                    $bid = $payment->muthowif_booking_id;
                    if (! $codes->has($bid)) {
                        $missingBooking++;

                        continue;
                    }
                    $target = $codes->get($bid);
                    if (($payment->booking_code ?? null) !== ($target ?? null)) {
                        $outOfSync++;
                    }
                }
            });

        $this->info('Dry-run: memindai '.$scanned.' baris booking_payments.');
        $this->line('  · akan diperbarui (nilai beda / kosong): '.$outOfSync);
        if ($missingBooking > 0) {
            $this->warn('  · muthowif_booking tidak ditemukan untuk '.$missingBooking.' baris (abaikan jika data memang orphan).');
        }

        return self::SUCCESS;
    }

    private function runBulkUpdate(): int
    {
        $driver = DB::getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            $affected = DB::update(
                <<<'SQL'
                UPDATE booking_payments bp
                INNER JOIN muthowif_bookings b ON b.id = bp.muthowif_booking_id
                SET bp.booking_code = b.booking_code
                SQL
            );
            $this->info('Driver '.$driver.': satu kali UPDATE JOIN — baris tersentuh (report engine): '.$affected.'.');

            return self::SUCCESS;
        }

        $updated = 0;
        $missingBooking = 0;
        $scanned = 0;

        BookingPayment::query()
            ->orderBy('id')
            ->chunkById(500, function ($payments) use (&$updated, &$missingBooking, &$scanned) {
                $scanned += $payments->count();
                $bookingIds = $payments->pluck('muthowif_booking_id')->unique()->values();
                $codes = MuthowifBooking::query()
                    ->whereIn('id', $bookingIds)
                    ->pluck('booking_code', 'id');

                foreach ($payments as $payment) {
                    $bid = $payment->muthowif_booking_id;
                    if (! $codes->has($bid)) {
                        $missingBooking++;

                        continue;
                    }
                    $target = $codes->get($bid);
                    if (($payment->booking_code ?? null) === ($target ?? null)) {
                        continue;
                    }
                    BookingPayment::query()->whereKey($payment->id)->update([
                        'booking_code' => $target,
                    ]);
                    $updated++;
                }
            });

        $this->info('Disaring: '.$scanned.' baris; booking_code diperbarui: '.$updated.'.');
        if ($missingBooking > 0) {
            $this->warn('Booking induk tidak ditemukan untuk '.$missingBooking.' baris.');
        }

        return self::SUCCESS;
    }
}
