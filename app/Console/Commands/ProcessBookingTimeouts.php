<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ProcessBookingTimeouts extends Command
{
    protected $signature = 'bookings:process-timeouts';

    protected $description = 'Memproses booking kedaluwarsa (placeholder untuk integrasi pembayaran nanti)';

    public function handle(): int
    {
        return self::SUCCESS;
    }
}
