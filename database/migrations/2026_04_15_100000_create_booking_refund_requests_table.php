<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_refund_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('muthowif_booking_id')->constrained('muthowif_bookings')->cascadeOnDelete();
            $table->foreignUuid('customer_id')->constrained('users')->cascadeOnDelete();
            $table->string('status', 32)->default('pending');
            $table->text('customer_note')->nullable();
            $table->text('muthowif_note')->nullable();
            $table->decimal('service_base_amount', 15, 2);
            $table->unsignedInteger('customer_paid_amount');
            $table->unsignedInteger('refund_fee_platform');
            $table->unsignedInteger('refund_fee_muthowif');
            $table->unsignedInteger('net_refund_customer');
            $table->timestamp('decided_at')->nullable();
            $table->foreignUuid('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['muthowif_booking_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_refund_requests');
    }
};
