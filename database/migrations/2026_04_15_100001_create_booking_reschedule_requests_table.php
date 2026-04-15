<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_reschedule_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('muthowif_booking_id')->constrained('muthowif_bookings')->cascadeOnDelete();
            $table->foreignUuid('customer_id')->constrained('users')->cascadeOnDelete();
            $table->string('status', 32)->default('pending');
            $table->date('previous_starts_on');
            $table->date('previous_ends_on');
            $table->date('new_starts_on');
            $table->date('new_ends_on');
            $table->text('customer_note')->nullable();
            $table->text('muthowif_note')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->foreignUuid('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['muthowif_booking_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_reschedule_requests');
    }
};
