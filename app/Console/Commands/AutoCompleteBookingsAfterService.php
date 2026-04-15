<?php

namespace App\Console\Commands;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Models\MuthowifBooking;
use App\Services\BookingCompletionService;
use Illuminate\Console\Command;

class AutoCompleteBookingsAfterService extends Command
{
    protected $signature = 'bookings:auto-complete-service {--dry-run : Hanya daftar booking yang memenuhi syarat}';

    protected $description = 'Menyelesaikan booking terkonfirmasi & lunas yang sudah lewat hari layanan + jeda (lihat config booking)';

    public function handle(BookingCompletionService $completion): int
    {
        $dry = (bool) $this->option('dry-run');
        $rating = (int) config('booking.auto_complete_default_rating', 5);

        $candidates = MuthowifBooking::query()
            ->where('status', BookingStatus::Confirmed)
            ->where('payment_status', PaymentStatus::Paid)
            ->whereNotNull('ends_on')
            ->whereDate('ends_on', '<', now()->startOfDay())
            ->orderBy('ends_on')
            ->get();

        $done = 0;

        foreach ($candidates as $booking) {
            if (! $completion->shouldAutoCompleteNow($booking)) {
                continue;
            }

            if ($dry) {
                $this->line(sprintf(
                    '[dry-run] %s ends_on=%s eligible=%s',
                    $booking->booking_code ?? $booking->getKey(),
                    $booking->ends_on?->toDateString(),
                    $completion->autoCompleteEligibleAt($booking)?->toIso8601String()
                ));

                continue;
            }

            $result = $completion->complete($booking, $rating, null);

            if ($result['completed']) {
                $done++;
                $this->info(sprintf(
                    'Selesai otomatis: %s (kredit wallet: %s)',
                    $booking->booking_code ?? $booking->getKey(),
                    $result['credited'] ? 'ya' : 'tidak'
                ));
            } elseif ($result['error'] !== null) {
                $this->error(sprintf(
                    'Gagal %s: %s',
                    $booking->booking_code ?? $booking->getKey(),
                    $result['error']
                ));
            }
        }

        if ($dry) {
            $this->comment('Dry-run selesai (tidak ada perubahan).');
        } else {
            $this->info("Total diselesaikan: {$done}");
        }

        return self::SUCCESS;
    }
}
