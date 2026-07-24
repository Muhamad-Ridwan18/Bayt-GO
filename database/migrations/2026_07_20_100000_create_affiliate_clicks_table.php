<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('affiliate_clicks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('affiliate_id')->constrained('affiliates')->cascadeOnDelete();
            $table->string('code_snapshot', 32);
            $table->string('visitor_key', 64);
            $table->string('ip_hash', 64)->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->string('landing_path', 512)->nullable();
            $table->foreignUuid('converted_booking_id')->nullable()->constrained('muthowif_bookings')->nullOnDelete();
            $table->timestamp('converted_at')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['affiliate_id', 'created_at']);
            $table->index(['affiliate_id', 'visitor_key', 'created_at']);
            $table->index(['converted_booking_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('affiliate_clicks');
    }
};
