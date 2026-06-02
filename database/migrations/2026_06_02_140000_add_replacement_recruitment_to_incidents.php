<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('booking_incidents', function (Blueprint $table) {
            $table->boolean('replacement_recruitment_open')->default(false)->after('total_service_days');
            $table->timestamp('replacement_recruitment_opened_at')->nullable()->after('replacement_recruitment_open');
            $table->timestamp('customer_choice_opened_at')->nullable()->after('replacement_recruitment_opened_at');
        });

        Schema::table('booking_replacements', function (Blueprint $table) {
            $table->string('source', 20)->default('admin_invite')->after('status');
            $table->timestamp('volunteered_at')->nullable()->after('source');
            $table->timestamp('admin_approved_at')->nullable()->after('approved_by_admin_id');

            $table->unique(
                ['booking_incident_id', 'replacement_muthowif_profile_id'],
                'booking_replacements_incident_profile_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('booking_replacements', function (Blueprint $table) {
            $table->dropUnique('booking_replacements_incident_profile_unique');
            $table->dropColumn(['source', 'volunteered_at', 'admin_approved_at']);
        });

        Schema::table('booking_incidents', function (Blueprint $table) {
            $table->dropColumn([
                'replacement_recruitment_open',
                'replacement_recruitment_opened_at',
                'customer_choice_opened_at',
            ]);
        });
    }
};
