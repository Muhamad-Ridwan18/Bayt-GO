<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('booking_refund_requests', function (Blueprint $table) {
            $table->string('midtrans_refund_key', 64)->nullable()->after('net_refund_customer');
            $table->timestamp('midtrans_refunded_at')->nullable()->after('midtrans_refund_key');
            $table->json('midtrans_refund_response')->nullable()->after('midtrans_refunded_at');
        });
    }

    public function down(): void
    {
        Schema::table('booking_refund_requests', function (Blueprint $table) {
            $table->dropColumn(['midtrans_refund_key', 'midtrans_refunded_at', 'midtrans_refund_response']);
        });
    }
};
