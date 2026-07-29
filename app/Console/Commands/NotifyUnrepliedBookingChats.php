<?php

namespace App\Console\Commands;

use App\Services\BookingChatUnrepliedReminderService;
use Illuminate\Console\Command;

class NotifyUnrepliedBookingChats extends Command
{
    protected $signature = 'chat:notify-unreplied {--dry-run : Hanya hitung percakapan yang memenuhi syarat}';

    protected $description = 'Kirim WA harian ke muthowif/jamaah yang belum membalas live chat (>30 menit)';

    public function handle(BookingChatUnrepliedReminderService $reminder): int
    {
        if ($this->option('dry-run')) {
            $this->warn('Dry-run belum didukung — jalankan tanpa --dry-run untuk mengirim notifikasi.');

            return self::SUCCESS;
        }

        $sent = $reminder->process();
        $this->info("Notifikasi WhatsApp terkirim: {$sent}");

        return self::SUCCESS;
    }
}
