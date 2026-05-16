<?php

namespace App\Console\Commands;

use App\Models\MuthowifBooking;
use Illuminate\Console\Command;

class CheckBookingDb extends Command
{
    protected $signature = 'app:check-db';
    protected $description = 'Check booking DB';

    public function handle()
    {
        try {
            $bookings = MuthowifBooking::latest()->limit(5)->get();
            foreach ($bookings as $b) {
                $this->info("Booking ID: {$b->id}");
                $this->line("Total Amount (db): {$b->total_amount}");
                $this->line("Resolved Amount: {$b->resolvedAmountDue()}");
                foreach($b->bookingPayments as $p) {
                    $this->line("  Payment ID: {$p->id} | Type: {$p->payment_type} | Gross: {$p->gross_amount} | Status: {$p->status} | TRX: {$p->gateway_transaction_id}");
                }
                $this->line("--------------------------");
            }
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }
}
