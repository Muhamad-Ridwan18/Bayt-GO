<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('muthowif_booking_id')->constrained('muthowif_bookings')->cascadeOnDelete();
            $table->string('order_id', 128)->unique();
            $table->unsignedBigInteger('gross_amount');
            $table->decimal('platform_fee_amount', 15, 2);
            $table->decimal('muthowif_net_amount', 15, 2);
            $table->string('status', 32)->default('pending');
            $table->text('snap_token')->nullable();
            $table->string('midtrans_transaction_id', 64)->nullable();
            $table->string('payment_type', 64)->nullable();
            $table->timestamp('settled_at')->nullable();
            $table->json('midtrans_notification_payload')->nullable();
            $table->timestamps();

            $table->index(['muthowif_booking_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_payments');
    }
};
