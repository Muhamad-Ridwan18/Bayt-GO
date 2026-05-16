<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$bookings = \App\Models\MuthowifBooking::latest()->limit(5)->get();
foreach ($bookings as $b) {
    echo "Booking ID: {$b->id}\n";
    echo "Total Amount (db): {$b->total_amount}\n";
    echo "Resolved Amount: {$b->resolvedAmountDue()}\n";
    foreach($b->bookingPayments as $p) {
        echo "  Payment ID: {$p->id} | Type: {$p->payment_type} | Gross: {$p->gross_amount} | Status: {$p->status} | TRX: {$p->gateway_transaction_id}\n";
    }
    echo "--------------------------\n";
}
