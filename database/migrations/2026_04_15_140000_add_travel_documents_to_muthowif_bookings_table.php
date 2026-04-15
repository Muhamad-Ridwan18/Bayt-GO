<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('muthowif_bookings', function (Blueprint $table) {
            $table->string('ticket_outbound_path')->nullable()->after('paid_at');
            $table->string('ticket_return_path')->nullable()->after('ticket_outbound_path');
            $table->string('itinerary_path')->nullable()->after('ticket_return_path');
            $table->string('visa_path')->nullable()->after('itinerary_path');
        });
    }

    public function down(): void
    {
        Schema::table('muthowif_bookings', function (Blueprint $table) {
            $table->dropColumn([
                'ticket_outbound_path',
                'ticket_return_path',
                'itinerary_path',
                'visa_path',
            ]);
        });
    }
};
