<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('muthowif_profiles', function (Blueprint $table) {
            if (! Schema::hasColumn('muthowif_profiles', 'referral_code')) {
                $table->string('referral_code', 16)->nullable()->unique();
            }
            if (! Schema::hasColumn('muthowif_profiles', 'referred_by_muthowif_profile_id')) {
                $table->foreignUuid('referred_by_muthowif_profile_id')
                    ->nullable()
                    ->after('referral_code')
                    ->constrained('muthowif_profiles')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('muthowif_profiles', function (Blueprint $table) {
            if (Schema::hasColumn('muthowif_profiles', 'referred_by_muthowif_profile_id')) {
                $table->dropConstrainedForeignId('referred_by_muthowif_profile_id');
            }
            if (Schema::hasColumn('muthowif_profiles', 'referral_code')) {
                $table->dropColumn('referral_code');
            }
        });
    }
};
