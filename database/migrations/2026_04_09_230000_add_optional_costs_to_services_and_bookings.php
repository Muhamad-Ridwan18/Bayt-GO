<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('muthowif_services', function (Blueprint $table): void {
            $table->decimal('same_hotel_price_per_day', 15, 2)->nullable()->after('description');
            $table->decimal('transport_price_flat', 15, 2)->nullable()->after('same_hotel_price_per_day');
        });

        Schema::table('muthowif_bookings', function (Blueprint $table): void {
            $table->boolean('with_same_hotel')->default(false)->after('selected_add_on_ids');
            $table->boolean('with_transport')->default(false)->after('with_same_hotel');
        });
    }

    public function down(): void
    {
        Schema::table('muthowif_bookings', function (Blueprint $table): void {
            $table->dropColumn(['with_same_hotel', 'with_transport']);
        });

        Schema::table('muthowif_services', function (Blueprint $table): void {
            $table->dropColumn(['same_hotel_price_per_day', 'transport_price_flat']);
        });
    }
};
