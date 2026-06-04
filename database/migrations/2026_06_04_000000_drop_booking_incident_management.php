<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('booking_payments', function (Blueprint $table) {
            if (Schema::hasColumn('booking_payments', 'settlement_state')) {
                $table->dropColumn('settlement_state');
            }
        });

        Schema::table('booking_refund_requests', function (Blueprint $table) {
            foreach (['booking_incident_id', 'booking_settlement_id'] as $col) {
                if (Schema::hasColumn('booking_refund_requests', $col)) {
                    $table->dropConstrainedForeignId($col);
                }
            }
            if (Schema::hasColumn('booking_refund_requests', 'refund_scope')) {
                $table->dropColumn('refund_scope');
            }
        });

        if (Schema::hasTable('support_tickets')) {
            Schema::table('support_tickets', function (Blueprint $table) {
                if (Schema::hasColumn('support_tickets', 'booking_incident_id')) {
                    $table->dropConstrainedForeignId('booking_incident_id');
                }
                if (Schema::hasColumn('support_tickets', 'is_emergency')) {
                    $table->dropColumn('is_emergency');
                }
            });
        }

        Schema::dropIfExists('booking_payout_allocations');
        Schema::dropIfExists('booking_settlements');
        Schema::dropIfExists('booking_replacements');
        Schema::dropIfExists('booking_incident_events');
        Schema::dropIfExists('booking_incidents');

        Schema::table('muthowif_bookings', function (Blueprint $table) {
            foreach ([
                'incident_status',
                'service_phase',
                'h1_confirmed_at',
                'emergency_reported_at',
                'muthowif_checked_in_at',
            ] as $col) {
                if (Schema::hasColumn('muthowif_bookings', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }

    public function down(): void
    {
        // Fitur insiden dihapus; tidak dipulihkan lewat rollback.
    }
};
