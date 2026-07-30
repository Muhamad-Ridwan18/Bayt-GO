<?php

namespace App\Console\Commands;

use App\Services\BookingChatUnrepliedReminderService;
use Illuminate\Console\Command;

class ResetUnrepliedBookingChatReminders extends Command
{
    protected $signature = 'chat:reset-unreplied-reminders
                            {--booking= : Reset cache untuk satu booking (UUID atau kode booking)}
                            {--force : Reset semua tanpa konfirmasi}';

    protected $description = 'Reset cache pengingat WA live chat (untuk testing ulang)';

    public function handle(): int
    {
        $bookingRef = $this->option('booking');
        if (is_string($bookingRef)) {
            $bookingRef = trim($bookingRef);
            if ($bookingRef === '') {
                $bookingRef = null;
            }
        } else {
            $bookingRef = null;
        }

        if ($bookingRef !== null) {
            if (! $this->bookingExists($bookingRef)) {
                $this->error("Booking tidak ditemukan: {$bookingRef}");

                return self::FAILURE;
            }

            $cleared = BookingChatUnrepliedReminderService::resetReminders($bookingRef);
            $this->info("Cache pengingat chat direset untuk booking {$bookingRef} ({$cleared} entri).");

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm('Reset semua cache pengingat chat yang belum dibalas?', true)) {
            $this->comment('Dibatalkan.');

            return self::SUCCESS;
        }

        $cleared = BookingChatUnrepliedReminderService::resetReminders();
        $this->info("Cache pengingat chat direset ({$cleared} entri).");
        $this->line('Sekarang bisa uji ulang dengan: php artisan chat:notify-unreplied --dry-run');

        return self::SUCCESS;
    }

    private function bookingExists(string $bookingRef): bool
    {
        return \App\Models\MuthowifBooking::query()
            ->where('booking_code', $bookingRef)
            ->orWhereKey($bookingRef)
            ->exists();
    }
}
