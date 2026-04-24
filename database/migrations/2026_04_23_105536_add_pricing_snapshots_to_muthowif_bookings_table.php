<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('muthowif_bookings', function (Blueprint $table) {
            $table->decimal('daily_price_snapshot', 15, 2)->nullable()->after('total_amount');
            $table->decimal('same_hotel_price_snapshot', 15, 2)->nullable()->after('daily_price_snapshot');
            $table->decimal('transport_price_snapshot', 15, 2)->nullable()->after('same_hotel_price_snapshot');
            $table->json('add_ons_snapshot')->nullable()->after('transport_price_snapshot');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('muthowif_bookings', function (Blueprint $table) {
            $table->dropColumn([
                'daily_price_snapshot',
                'same_hotel_price_snapshot',
                'transport_price_snapshot',
                'add_ons_snapshot',
            ]);
        });
    }
};
