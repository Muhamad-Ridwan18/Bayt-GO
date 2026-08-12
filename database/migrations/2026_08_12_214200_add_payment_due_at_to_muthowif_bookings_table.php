<?php

use App\Enums\BookingStatus;
use App\Enums\MuthowifServiceType;
use App\Enums\PaymentStatus;
use App\Support\BookingPaymentDeadlineSettings;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('muthowif_bookings', function (Blueprint $table) {
            $table->timestamp('payment_due_at')->nullable()->after('paid_at');
            $table->index('payment_due_at');
        });

        $regularMinutes = BookingPaymentDeadlineSettings::regularMinutes();
        $supportMinutes = BookingPaymentDeadlineSettings::supportMinutes();

        $rows = DB::table('muthowif_bookings')
            ->where('status', BookingStatus::Confirmed->value)
            ->where('payment_status', PaymentStatus::Pending->value)
            ->whereNull('payment_due_at')
            ->select(['id', 'service_type', 'updated_at'])
            ->get();

        foreach ($rows as $row) {
            $minutes = $row->service_type === MuthowifServiceType::Support->value
                ? $supportMinutes
                : $regularMinutes;
            $base = $row->updated_at ? strtotime((string) $row->updated_at) : time();
            DB::table('muthowif_bookings')
                ->where('id', $row->id)
                ->update([
                    'payment_due_at' => date('Y-m-d H:i:s', $base + ($minutes * 60)),
                ]);
        }
    }

    public function down(): void
    {
        Schema::table('muthowif_bookings', function (Blueprint $table) {
            $table->dropIndex(['payment_due_at']);
            $table->dropColumn('payment_due_at');
        });
    }
};
