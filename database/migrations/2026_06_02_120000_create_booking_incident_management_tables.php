<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('muthowif_bookings', function (Blueprint $table) {
            $table->string('incident_status', 20)->default('none')->after('status');
            $table->string('service_phase', 20)->default('pre_service')->after('incident_status');
            $table->timestamp('h1_confirmed_at')->nullable()->after('service_phase');
            $table->timestamp('emergency_reported_at')->nullable()->after('h1_confirmed_at');
            $table->timestamp('muthowif_checked_in_at')->nullable()->after('emergency_reported_at');
        });

        Schema::create('booking_incidents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('muthowif_booking_id')->constrained('muthowif_bookings')->cascadeOnDelete();
            $table->string('case_type', 40);
            $table->string('severity', 20);
            $table->string('status', 30)->default('open');
            $table->string('resolution_type', 40)->nullable();
            $table->foreignUuid('reported_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('assigned_admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('customer_statement')->nullable();
            $table->text('muthowif_statement')->nullable();
            $table->text('admin_resolution_note')->nullable();
            $table->json('metadata')->nullable();
            $table->string('policy_version', 20)->nullable();
            $table->unsignedSmallInteger('completed_service_days')->nullable();
            $table->unsignedSmallInteger('total_service_days')->nullable();
            $table->timestamp('opened_at');
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'severity']);
            $table->index(['muthowif_booking_id', 'status']);
        });

        Schema::create('booking_incident_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('booking_incident_id')->constrained('booking_incidents')->cascadeOnDelete();
            $table->string('event_type', 60);
            $table->string('actor_type', 20);
            $table->uuid('actor_id')->nullable();
            $table->json('payload')->nullable();
            $table->timestamp('created_at');

            $table->index(['booking_incident_id', 'created_at']);
        });

        Schema::create('booking_replacements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('booking_incident_id')->constrained('booking_incidents')->cascadeOnDelete();
            $table->foreignUuid('original_muthowif_profile_id')->constrained('muthowif_profiles');
            $table->foreignUuid('replacement_muthowif_profile_id')->constrained('muthowif_profiles');
            $table->string('status', 40)->default('proposed');
            $table->foreignUuid('proposed_by_admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('approved_by_admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('replacement_confirmed_at')->nullable();
            $table->timestamp('offered_to_customer_at')->nullable();
            $table->timestamp('customer_accepted_at')->nullable();
            $table->timestamp('customer_rejected_at')->nullable();
            $table->text('admin_note')->nullable();
            $table->text('replacement_decline_note')->nullable();
            $table->timestamps();

            $table->index(['booking_incident_id', 'status']);
        });

        Schema::create('booking_settlements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('muthowif_booking_id')->constrained('muthowif_bookings')->cascadeOnDelete();
            $table->foreignUuid('booking_payment_id')->constrained('booking_payments')->cascadeOnDelete();
            $table->foreignUuid('booking_incident_id')->nullable()->constrained('booking_incidents')->nullOnDelete();
            $table->string('settlement_type', 40);
            $table->string('status', 30)->default('draft');
            $table->json('calculation_snapshot')->nullable();
            $table->foreignUuid('approved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('released_at')->nullable();
            $table->timestamps();

            $table->index(['muthowif_booking_id', 'status']);
        });

        Schema::create('booking_payout_allocations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('booking_settlement_id')->constrained('booking_settlements')->cascadeOnDelete();
            $table->foreignUuid('muthowif_profile_id')->constrained('muthowif_profiles');
            $table->string('role', 30);
            $table->unsignedSmallInteger('service_days')->default(0);
            $table->unsignedSmallInteger('total_service_days')->default(0);
            $table->decimal('amount', 15, 2);
            $table->string('status', 20)->default('pending');
            $table->timestamp('released_at')->nullable();
            $table->timestamps();

            $table->index(['muthowif_profile_id', 'status']);
        });

        if (Schema::hasTable('support_tickets')) {
            Schema::table('support_tickets', function (Blueprint $table) {
                if (! Schema::hasColumn('support_tickets', 'booking_incident_id')) {
                    $table->foreignUuid('booking_incident_id')->nullable()->after('id')->constrained('booking_incidents')->nullOnDelete();
                }
                if (! Schema::hasColumn('support_tickets', 'is_emergency')) {
                    $table->boolean('is_emergency')->default(false)->after('booking_incident_id');
                }
            });
        }

        Schema::table('booking_refund_requests', function (Blueprint $table) {
            if (! Schema::hasColumn('booking_refund_requests', 'booking_incident_id')) {
                $table->foreignUuid('booking_incident_id')->nullable()->constrained('booking_incidents')->nullOnDelete();
            }
            if (! Schema::hasColumn('booking_refund_requests', 'booking_settlement_id')) {
                $table->foreignUuid('booking_settlement_id')->nullable()->constrained('booking_settlements')->nullOnDelete();
            }
            if (! Schema::hasColumn('booking_refund_requests', 'refund_scope')) {
                $table->string('refund_scope', 20)->default('standard')->after('status');
            }
        });

        Schema::table('booking_payments', function (Blueprint $table) {
            if (! Schema::hasColumn('booking_payments', 'settlement_state')) {
                $table->string('settlement_state', 30)->default('escrow_held')->after('wallet_credited_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('booking_payments', function (Blueprint $table) {
            if (Schema::hasColumn('booking_payments', 'settlement_state')) {
                $table->dropColumn('settlement_state');
            }
        });

        Schema::table('booking_refund_requests', function (Blueprint $table) {
            foreach (['booking_incident_id', 'booking_settlement_id', 'refund_scope'] as $col) {
                if (Schema::hasColumn('booking_refund_requests', $col)) {
                    $table->dropConstrainedForeignId($col);
                }
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
            $table->dropColumn([
                'incident_status',
                'service_phase',
                'h1_confirmed_at',
                'emergency_reported_at',
                'muthowif_checked_in_at',
            ]);
        });
    }
};
