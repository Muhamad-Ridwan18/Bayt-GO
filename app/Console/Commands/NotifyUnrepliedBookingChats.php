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
            $sent = count(array_filter(
                $result['recipients'],
                static fn (array $row): bool => ($row['status'] ?? '') === 'sent',
            ));
            $failed = count(array_filter(
                $result['recipients'],
                static fn (array $row): bool => ($row['status'] ?? '') === 'failed',
            ));
            $suffix = $force ? ' (mode testing — cache harian dilewati)' : '';
            $this->info("Notifikasi WhatsApp terkirim: {$sent}{$suffix}");
            if ($failed > 0) {
                $this->error("Gagal kirim: {$failed}");
            }
        }

        $this->displayRecipients($result['recipients']);

        return self::SUCCESS;
    }

    /**
     * @param  list<array{role: string, name: string, phone: string, booking_code: string, status?: string, error?: string}>  $recipients
     */
    private function displayRecipients(array $recipients): void
    {
        if ($recipients === []) {
            $this->line('Tidak ada penerima.');

            return;
        }

        $this->newLine();
        $this->table(
            ['Booking ID', 'Peran', 'Nama', 'Nomor WA', 'Kode booking', 'Status', 'Error'],
            array_map(static fn (array $row): array => [
                $row['booking_id'] ?? '—',
                $row['role'],
                $row['name'],
                $row['phone'],
                $row['booking_code'],
                $row['status'] ?? '—',
                $row['error'] ?? '—',
            ], $recipients),
        );
    }
}
