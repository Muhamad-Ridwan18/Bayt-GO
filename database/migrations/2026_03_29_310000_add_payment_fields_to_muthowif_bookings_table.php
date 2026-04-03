<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('muthowif_bookings', function (Blueprint $table) {
            $table->string('payment_status', 32)->default('pending')->after('status');
            $table->decimal('total_amount', 15, 2)->nullable()->after('payment_status');
            $table->timestamp('paid_at')->nullable()->after('total_amount');
        });
    }

    public function down(): void
    {
        Schema::table('muthowif_bookings', function (Blueprint $table) {
            $table->dropColumn(['payment_status', 'total_amount', 'paid_at']);
        });
    }
};
