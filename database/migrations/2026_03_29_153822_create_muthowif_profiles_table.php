<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('muthowif_profiles')) {
            return;
        }

        Schema::create('muthowif_profiles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('phone', 32);
            $table->string('city', 120)->nullable();
            $table->text('address');
            $table->string('nik', 16);
            $table->date('birth_date');
            $table->string('photo_path');
            $table->string('ktp_image_path');
            $table->string('certificate_path')->nullable();
            $table->string('verification_status', 32)->default('pending');
            $table->timestamp('verified_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();

            $table->unique('user_id');
            $table->index('verification_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('muthowif_profiles');
    }
};
