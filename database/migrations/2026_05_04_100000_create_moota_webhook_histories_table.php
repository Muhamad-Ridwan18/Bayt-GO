<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('moota_webhook_histories', function (Blueprint $table) {
            $table->id();
            $table->string('source_ip', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('x_moota_user')->nullable();
            $table->string('x_moota_webhook')->nullable();
            $table->text('signature_header')->nullable();
            /** null = rahasia tidak di-set; selain itu menandakan hasil cek Signature vs body mentah */
            $table->boolean('signature_verified')->nullable();
            /** JSON array mutasi seperti dari Moota; null jika body bukan JSON array */
            $table->json('payload')->nullable();
            $table->longText('payload_raw');
            $table->string('parse_error', 2048)->nullable();
            $table->timestamps();

            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('moota_webhook_histories');
    }
};
