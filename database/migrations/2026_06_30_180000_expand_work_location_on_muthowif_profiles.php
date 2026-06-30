<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('muthowif_profiles', function (Blueprint $table) {
            if (Schema::hasColumn('muthowif_profiles', 'work_location')) {
                $table->string('work_location', 255)->nullable()->change();
            }
        });
    }

    public function down(): void
    {
        Schema::table('muthowif_profiles', function (Blueprint $table) {
            if (Schema::hasColumn('muthowif_profiles', 'work_location')) {
                $table->string('work_location', 32)->nullable()->change();
            }
        });
    }
};
