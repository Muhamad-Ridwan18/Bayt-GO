<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('muthowif_blocked_dates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('muthowif_profile_id')->constrained('muthowif_profiles')->cascadeOnDelete();
            $table->date('blocked_on');
            $table->string('note', 255)->nullable();
            $table->timestamps();

            $table->unique(['muthowif_profile_id', 'blocked_on']);
            $table->index('blocked_on');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('muthowif_blocked_dates');
    }
};
