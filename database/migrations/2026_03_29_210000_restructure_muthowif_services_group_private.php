<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('muthowif_service_add_ons');
        Schema::dropIfExists('muthowif_services');

        Schema::create('muthowif_services', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('muthowif_profile_id')->constrained('muthowif_profiles')->cascadeOnDelete();
            $table->string('type', 16);
            $table->string('name', 160)->nullable();
            $table->decimal('daily_price', 15, 2)->nullable();
            $table->unsignedSmallInteger('min_pilgrims')->nullable();
            $table->unsignedSmallInteger('max_pilgrims')->nullable();
            $table->text('description')->nullable();
            $table->boolean('stays_at_same_hotel')->default(false);
            $table->boolean('includes_transport')->default(false);
            $table->timestamps();

            $table->unique(['muthowif_profile_id', 'type']);
        });

        Schema::create('muthowif_service_add_ons', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('muthowif_service_id')->constrained('muthowif_services')->cascadeOnDelete();
            $table->string('name', 160);
            $table->decimal('price', 15, 2);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('muthowif_service_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('muthowif_service_add_ons');
        Schema::dropIfExists('muthowif_services');

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
};
