<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('muthowif_bookings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('muthowif_profile_id')->constrained('muthowif_profiles')->cascadeOnDelete();
            $table->foreignUuid('customer_id')->constrained('users')->cascadeOnDelete();
            $table->date('starts_on');
            $table->date('ends_on');
            $table->string('status', 32)->default('pending');
            $table->timestamps();

            $table->index(['muthowif_profile_id', 'status', 'starts_on', 'ends_on'], 'muthowif_bookings_avail_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('muthowif_bookings');
    }
};
