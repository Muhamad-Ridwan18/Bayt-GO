<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('muthowif_profiles', 'current_domicile_address')
            && ! Schema::hasColumn('muthowif_profiles', 'work_location')) {
            Schema::table('muthowif_profiles', function (Blueprint $table) {
                $table->renameColumn('current_domicile_address', 'work_location');
            });
        }

        Schema::table('muthowif_profiles', function (Blueprint $table) {
            if (Schema::hasColumn('muthowif_profiles', 'work_location')) {
                $table->string('work_location', 32)->nullable()->change();
            }
        });
    }

    public function down(): void
    {
        Schema::table('muthowif_profiles', function (Blueprint $table) {
            if (Schema::hasColumn('muthowif_profiles', 'work_location')) {
                $table->text('work_location')->nullable()->change();
            }
        });

        if (Schema::hasColumn('muthowif_profiles', 'work_location')
            && ! Schema::hasColumn('muthowif_profiles', 'current_domicile_address')) {
            Schema::table('muthowif_profiles', function (Blueprint $table) {
                $table->renameColumn('work_location', 'current_domicile_address');
            });
        }
    }
};
