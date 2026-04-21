<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('booking_payments', function (Blueprint $table) {
            $table->index(['status', 'settled_at'], 'booking_payments_status_settled_at_idx');
        });

        Schema::table('booking_refund_requests', function (Blueprint $table) {
            $table->index(['status', 'decided_at'], 'booking_refund_requests_status_decided_idx');
        });

        Schema::table('muthowif_withdrawals', function (Blueprint $table) {
            $table->index(['status', 'requested_at'], 'muthowif_withdrawals_status_requested_idx');
            $table->index('updated_at', 'muthowif_withdrawals_updated_at_idx');
        });
    }

    public function down(): void
    {
        Schema::table('booking_payments', function (Blueprint $table) {
            $table->dropIndex('booking_payments_status_settled_at_idx');
        });

        Schema::table('booking_refund_requests', function (Blueprint $table) {
            $table->dropIndex('booking_refund_requests_status_decided_idx');
        });

        Schema::table('muthowif_withdrawals', function (Blueprint $table) {
            $table->dropIndex('muthowif_withdrawals_status_requested_idx');
            $table->dropIndex('muthowif_withdrawals_updated_at_idx');
        });
    }
};
