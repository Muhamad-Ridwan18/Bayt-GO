<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('booking_payments', function (Blueprint $table) {
            if (! Schema::hasColumn('booking_payments', 'referrer_muthowif_profile_id')) {
                $table->foreignUuid('referrer_muthowif_profile_id')
                    ->nullable()
                    ->after('muthowif_net_amount')
                    ->constrained('muthowif_profiles')
                    ->nullOnDelete();
            }
            if (! Schema::hasColumn('booking_payments', 'referral_reward_amount')) {
                $table->decimal('referral_reward_amount', 15, 2)->default(0)->after('referrer_muthowif_profile_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('booking_payments', function (Blueprint $table) {
            if (Schema::hasColumn('booking_payments', 'referral_reward_amount')) {
                $table->dropColumn('referral_reward_amount');
            }
            if (Schema::hasColumn('booking_payments', 'referrer_muthowif_profile_id')) {
                $table->dropConstrainedForeignId('referrer_muthowif_profile_id');
            }
        });
    }
};
