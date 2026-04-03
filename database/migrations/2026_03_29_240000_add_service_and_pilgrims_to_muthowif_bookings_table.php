<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('muthowif_bookings', function (Blueprint $table) {
            $table->string('service_type', 32)->default('group')->after('customer_id');
            $table->unsignedSmallInteger('pilgrim_count')->default(1)->after('service_type');
            $table->json('selected_add_on_ids')->nullable()->after('pilgrim_count');
        });
    }

    public function down(): void
    {
        Schema::table('muthowif_bookings', function (Blueprint $table) {
            $table->dropColumn(['service_type', 'pilgrim_count', 'selected_add_on_ids']);
        });
    }
};
