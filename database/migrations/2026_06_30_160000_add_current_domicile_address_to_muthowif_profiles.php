<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('muthowif_profiles', function (Blueprint $table) {
            if (! Schema::hasColumn('muthowif_profiles', 'current_domicile_address')) {
                $table->text('current_domicile_address')->nullable()->after('address');
            }
        });
    }

    public function down(): void
    {
        Schema::table('muthowif_profiles', function (Blueprint $table) {
            if (Schema::hasColumn('muthowif_profiles', 'current_domicile_address')) {
                $table->dropColumn('current_domicile_address');
            }
        });
    }
};
