<?php

namespace App\Console\Commands;

use App\Services\SupportBookingService;
use Illuminate\Console\Command;

class ProcessSupportBookingLifecycle extends Command
{
    protected $signature = 'bookings:process-support-lifecycle {--dry-run : Hanya tampilkan tanpa mengubah data}';

    protected $description = 'Mulai layanan support yang sudah lunas dan perpanjang block kalender harian';

    public function handle(SupportBookingService $support): int
    {
        if ($this->option('dry-run')) {
            $this->comment('Dry-run: command ini belum mendukung preview terpisah.');

            return self::SUCCESS;
        }

        $result = $support->processLifecycle();

        if ($result['started'] > 0 || $result['extended'] > 0) {
            $this->info(sprintf(
                'Support lifecycle: %d dimulai, %d kalender diperpanjang.',
                $result['started'],
                $result['extended']
            ));
        }

        return self::SUCCESS;
    }
}
