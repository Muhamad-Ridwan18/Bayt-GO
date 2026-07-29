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

        $result = $reminder->process(force: $force || $dryRun, dryRun: $dryRun);
        $count = $result['count'];

        if ($dryRun) {
            $this->info("Eligible: {$count} percakapan (dry-run, tidak dikirim).");
        } else {
            $suffix = $force ? ' (mode testing — cache harian dilewati)' : '';
            $this->info("Notifikasi WhatsApp terkirim: {$count}{$suffix}");
        }

        $this->displayRecipients($result['recipients']);

        return self::SUCCESS;
    }

    /**
     * @param  list<array{role: string, name: string, phone: string, booking_code: string}>  $recipients
     */
    private function displayRecipients(array $recipients): void
    {
        if ($recipients === []) {
            $this->line('Tidak ada penerima.');

            return;
        }

        $this->newLine();
        $this->table(
            ['Peran', 'Nama', 'Nomor WA', 'Kode booking'],
            array_map(static fn (array $row): array => [
                $row['role'],
                $row['name'],
                $row['phone'],
                $row['booking_code'],
            ], $recipients),
        );
    }
}
