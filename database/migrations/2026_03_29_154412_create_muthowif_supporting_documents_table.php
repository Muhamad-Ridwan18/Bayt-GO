<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('muthowif_supporting_documents');

        Schema::create('muthowif_supporting_documents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('muthowif_profile_id')->constrained('muthowif_profiles')->cascadeOnDelete();
            $table->string('path');
            $table->string('original_name')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('muthowif_profile_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('muthowif_supporting_documents');
    }
};
