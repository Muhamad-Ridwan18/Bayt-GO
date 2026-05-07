<?php

namespace App\Console\Commands;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Models\MuthowifBooking;
use App\Services\BookingPendingPaymentEnsurer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Ubah booking cancelled menjadi confirmed (siap bayar), set total, lalu pastikan ada booking_payments pending.
 */
final class ReviveCancelledBookingsForPaymentCommand extends Command
{
    protected $signature = 'bookings:revive-cancelled-for-payment
                            {--dry-run : Tampilkan saja, tanpa mengubah DB}';

    protected $description = 'Set booking cancelled → confirmed + payment pending, hitung total, panggil BookingPendingPaymentEnsurer';

    public function handle(BookingPendingPaymentEnsurer $ensurer): int
    {
        $dry = (bool) $this->option('dry-run');

        $cancelled = MuthowifBooking::query()
            ->where('status', BookingStatus::Cancelled)
            ->orderBy('created_at')
            ->get();

        if ($cancelled->isEmpty()) {
            $this->info('Tidak ada booking berstatus cancelled.');

            return self::SUCCESS;
        }

        $this->info('Menemukan '.$cancelled->count().' booking cancelled.');

        foreach ($cancelled as $booking) {
            $this->line('• '.$booking->getKey().' · code: '.($booking->booking_code ?? '—'));
        }

        if ($dry) {
            $this->warn('Dry-run — tidak ada perubahan.');

            return self::SUCCESS;
        }

        try {
            DB::transaction(function () use ($cancelled, $ensurer): void {
                foreach ($cancelled as $booking) {
                    $booking->loadMissing(['muthowifProfile.services.addOns']);
                    $total = $booking->computeTotalAmount();

                    $booking->update([
                        'status' => BookingStatus::Confirmed,
                        'payment_status' => PaymentStatus::Pending,
                        'total_amount' => $total,
                    ]);

                    $payment = $ensurer->ensure($booking->fresh());
                    $this->info('OK '.$booking->getKey().' → confirmed, total='.number_format($total, 2, ',', '.').', payment='.($payment?->order_id ?? '—'));
                }
            });
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info('Selesai.');

        return self::SUCCESS;
    }
}
