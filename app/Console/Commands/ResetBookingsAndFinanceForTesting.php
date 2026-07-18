<?php

namespace App\Console\Commands;

use App\Models\Affiliate;
use App\Models\AffiliateWalletTransaction;
use App\Models\AffiliateWithdrawal;
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
 * Menghapus: muthowif_bookings (+ cascade: pembayaran, refund, reschedule, chat, review, affiliate commissions),
 * muthowif_withdrawals, affiliate_withdrawals, affiliate_wallet_transactions,
 * dan mengenolkan wallet_balance muthowif + available_balance affiliate.
 */
class ResetBookingsAndFinanceForTesting extends Command
{
    protected $signature = 'testing:reset-bookings
                            {--force : Lewati konfirmasi interaktif}
                            {--with-files : Hapus folder bukti refund & withdraw di disk public}';

    protected $description = 'Hapus semua booking, pembayaran, refund, withdraw, dan reset saldo wallet muthowif + affiliate (untuk testing)';

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
            'Ini akan menghapus SEMUA booking/pembayaran/refund/withdraw (muthowif + affiliate), ledger wallet affiliate, dan mengenolkan saldo wallet muthowif + affiliate. Lanjutkan?',
            false
        )) {
            $this->info('Dibatalkan.');

            return self::SUCCESS;
        }

        $bookingCount = MuthowifBooking::query()->count();
        $withdrawCount = MuthowifWithdrawal::query()->count();
        $affiliateWithdrawCount = AffiliateWithdrawal::query()->count();
        $profileCount = MuthowifProfile::query()->count();
        $affiliateCount = Affiliate::query()->count();

        try {
            DB::transaction(function (): void {
                $deletedAffiliateWithdrawals = AffiliateWithdrawal::query()->delete();
                $this->line("Affiliate withdraw dihapus: {$deletedAffiliateWithdrawals}");

                $deletedAffiliateLedger = AffiliateWalletTransaction::query()->delete();
                $this->line("Affiliate wallet ledger dihapus: {$deletedAffiliateLedger}");

                $deletedWithdrawals = MuthowifWithdrawal::query()->delete();
                $this->line("Muthowif withdraw dihapus: {$deletedWithdrawals}");

                $deletedBookings = MuthowifBooking::query()->delete();
                $this->line("Booking dihapus: {$deletedBookings}");

                $updatedProfiles = MuthowifProfile::query()->update(['wallet_balance' => 0]);
                $this->line("Wallet muthowif diset 0: {$updatedProfiles} baris");

                $updatedAffiliates = Affiliate::query()->update(['available_balance' => 0]);
                $this->line("Wallet affiliate diset 0: {$updatedAffiliates} baris");
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

        $this->info(
            'Selesai. (Sebelum: booking ~'.$bookingCount
            .', muthowif withdraw ~'.$withdrawCount
            .', affiliate withdraw ~'.$affiliateWithdrawCount
            .', profil ~'.$profileCount
            .', affiliate ~'.$affiliateCount.')'
        );

        return self::SUCCESS;
    }
}
