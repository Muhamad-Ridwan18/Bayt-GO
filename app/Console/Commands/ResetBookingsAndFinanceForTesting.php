<?php

namespace App\Console\Commands;

use App\Models\MuthowifBooking;
use App\Models\MuthowifProfile;
use App\Models\MuthowifWithdrawal;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Hapus seluruh booking & transaksi terkait untuk reset lingkungan pengujian.
 *
 * Menghapus: muthowif_bookings (+ cascade: pembayaran, refund, reschedule, chat, review),
 * muthowif_withdrawals, dan mengenolkan wallet_balance semua profil muthowif.
 */
class ResetBookingsAndFinanceForTesting extends Command
{
    protected $signature = 'testing:reset-bookings
                            {--force : Lewati konfirmasi interaktif}
                            {--with-files : Hapus folder bukti refund & withdraw di disk public}';

    protected $description = 'Hapus semua booking, pembayaran, refund, withdraw, dan reset saldo wallet muthowif (untuk testing)';

    public function handle(): int
    {
        if (app()->environment('production')) {
            $this->error('Perintah ini tidak dijalankan saat APP_ENV=production.');

            return self::FAILURE;
        }

        if (! $this->input->isInteractive() && ! $this->option('force')) {
            $this->error('Mode non-interaktif: jalankan dengan --force (contoh: php artisan testing:reset-bookings --force).');

            return self::FAILURE;
        }

        if (! $this->option('force') && ! $this->confirm(
            'Ini akan menghapus SEMUA muthowif_bookings (beserta pembayaran, refund, reschedule, chat, review), SEMUA muthowif_withdrawals, dan mengenolkan wallet semua muthowif. Lanjutkan?',
            false
        )) {
            $this->info('Dibatalkan.');

            return self::SUCCESS;
        }

        $bookingCount = MuthowifBooking::query()->count();
        $withdrawCount = MuthowifWithdrawal::query()->count();
        $profileCount = MuthowifProfile::query()->count();

        try {
            DB::transaction(function (): void {
                $deletedWithdrawals = MuthowifWithdrawal::query()->delete();
                $this->line("Withdraw dihapus: {$deletedWithdrawals}");

                $deletedBookings = MuthowifBooking::query()->delete();
                $this->line("Booking dihapus: {$deletedBookings}");

                $updated = MuthowifProfile::query()->update(['wallet_balance' => 0]);
                $this->line("Profil muthowif diset saldo 0: {$updated} baris");
            });
        } catch (Throwable $e) {
            $this->error('Gagal: '.$e->getMessage());

            return self::FAILURE;
        }

        if ($this->option('with-files')) {
            $disk = Storage::disk('public');
            foreach (['refunds/proofs', 'withdrawals/proofs'] as $dir) {
                if ($disk->exists($dir)) {
                    $disk->deleteDirectory($dir);
                }
                $disk->makeDirectory($dir);
                $this->line("Storage public/{$dir} dikosongkan.");
            }
        }

        $this->info('Selesai. (Sebelum: booking ~'.$bookingCount.', withdraw ~'.$withdrawCount.', profil ~'.$profileCount.')');

        return self::SUCCESS;
    }
}
