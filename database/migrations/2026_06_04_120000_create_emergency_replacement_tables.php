<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('muthowif_profiles', function (Blueprint $table) {
            if (! Schema::hasColumn('muthowif_profiles', 'account_status')) {
                $table->string('account_status', 20)->default('active')->after('verification_status');
            }
        });

        Schema::table('muthowif_bookings', function (Blueprint $table) {
            if (! Schema::hasColumn('muthowif_bookings', 'original_muthowif_profile_id')) {
                $table->foreignUuid('original_muthowif_profile_id')
                    ->nullable()
                    ->after('muthowif_profile_id')
                    ->constrained('muthowif_profiles')
                    ->nullOnDelete();
            }
            if (! Schema::hasColumn('muthowif_bookings', 'emergency_overlay_status')) {
                $table->string('emergency_overlay_status', 32)->default('none')->after('status');
            }
            if (! Schema::hasColumn('muthowif_bookings', 'emergency_replacement_at')) {
                $table->timestamp('emergency_replacement_at')->nullable()->after('emergency_overlay_status');
            }
        });

        if (! Schema::hasTable('booking_emergency_reports')) {
            Schema::create('booking_emergency_reports', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('muthowif_booking_id')->constrained('muthowif_bookings')->cascadeOnDelete();
            $table->foreignUuid('reported_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('case_type', 40);
            $table->text('description')->nullable();
            $table->json('evidence_paths')->nullable();
            $table->string('status', 30)->default('submitted');
            $table->foreignUuid('verified_by_admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->text('admin_note')->nullable();
            $table->unsignedSmallInteger('replacement_batch_number')->default(0);
            $table->boolean('recruitment_open')->default(false);
            $table->timestamps();

            $table->index(['status', 'created_at'], 'ber_status_created_idx');
            $table->index(['muthowif_booking_id', 'status'], 'ber_booking_status_idx');
            });
        }

        if (! Schema::hasTable('booking_replacement_offers')) {
            Schema::create('booking_replacement_offers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('booking_emergency_report_id')
                ->constrained('booking_emergency_reports')
                ->cascadeOnDelete();
            $table->foreignUuid('muthowif_profile_id')->constrained('muthowif_profiles')->cascadeOnDelete();
            $table->unsignedSmallInteger('batch_number')->default(1);
            $table->string('source', 20)->default('broadcast');
            $table->string('status', 30)->default('offered');
            $table->timestamp('offered_at');
            $table->timestamp('responded_at')->nullable();
            $table->text('decline_note')->nullable();
            $table->timestamps();

            $table->unique(
                ['booking_emergency_report_id', 'muthowif_profile_id'],
                'bro_report_profile_uq'
            );
            $table->index(['booking_emergency_report_id', 'status'], 'bro_report_status_idx');
            });
        }

        if (! Schema::hasTable('booking_replacement_logs')) {
            Schema::create('booking_replacement_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('muthowif_booking_id')->constrained('muthowif_bookings')->cascadeOnDelete();
            $table->foreignUuid('booking_emergency_report_id')->constrained('booking_emergency_reports')->cascadeOnDelete();
            $table->foreignUuid('from_muthowif_profile_id')->constrained('muthowif_profiles');
            $table->foreignUuid('to_muthowif_profile_id')->constrained('muthowif_profiles');
            $table->string('chosen_by', 20);
            $table->foreignUuid('chosen_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_replacement_logs');
        Schema::dropIfExists('booking_replacement_offers');
        Schema::dropIfExists('booking_emergency_reports');

        Schema::table('muthowif_bookings', function (Blueprint $table) {
            foreach (['emergency_replacement_at', 'emergency_overlay_status', 'original_muthowif_profile_id'] as $col) {
                if (Schema::hasColumn('muthowif_bookings', $col)) {
                    if ($col === 'original_muthowif_profile_id') {
                        $table->dropConstrainedForeignId($col);
                    } else {
                        $table->dropColumn($col);
                    }
                }
            }
        });

        Schema::table('muthowif_profiles', function (Blueprint $table) {
            if (Schema::hasColumn('muthowif_profiles', 'account_status')) {
                $table->dropColumn('account_status');
            }
        });
    }
};
