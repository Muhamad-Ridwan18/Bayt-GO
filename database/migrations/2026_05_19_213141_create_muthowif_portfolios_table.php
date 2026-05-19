<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('muthowif_portfolios', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('muthowif_profile_id')->constrained('muthowif_profiles')->cascadeOnDelete();
            $table->string('title', 255)->nullable();
            $table->text('description')->nullable();
            $table->string('image_path', 1000);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('muthowif_portfolios');
    }
};
