<?php

namespace App\Console\Commands;

use App\Services\BookingChatUnrepliedReminderService;
use Illuminate\Console\Command;

class NotifyUnrepliedBookingChats extends Command
{
    protected $signature = 'chat:notify-unreplied
                            {--force : Lewati batas 1x sehari — untuk testing}
                            {--dry-run : Hitung percakapan eligible tanpa mengirim WA}';

    protected $description = 'Kirim WA harian ke muthowif/jamaah yang belum membalas live chat';

    public function handle(BookingChatUnrepliedReminderService $reminder): int
    {
        $force = (bool) $this->option('force');
        $dryRun = (bool) $this->option('dry-run');

        if ($dryRun) {
            $count = $reminder->process(force: true, dryRun: true);
            $this->info("Eligible: {$count} percakapan (dry-run, tidak dikirim).");

            return self::SUCCESS;
        }

        $sent = $reminder->process(force: $force);
        $suffix = $force ? ' (mode testing — cache harian dilewati)' : '';
        $this->info("Notifikasi WhatsApp terkirim: {$sent}{$suffix}");

        return self::SUCCESS;
    }
}
