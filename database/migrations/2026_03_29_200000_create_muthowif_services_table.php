<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('muthowif_services', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('muthowif_profile_id')->constrained('muthowif_profiles')->cascadeOnDelete();
            $table->string('title', 160);
            $table->text('description')->nullable();
            $table->decimal('price', 15, 2)->nullable();
            $table->string('currency', 3)->default('IDR');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['muthowif_profile_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('muthowif_services');
    }
};
