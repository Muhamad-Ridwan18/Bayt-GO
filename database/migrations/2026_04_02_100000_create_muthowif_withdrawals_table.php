<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('muthowif_withdrawals', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('muthowif_profile_id')->constrained('muthowif_profiles')->cascadeOnDelete();
            $table->decimal('amount', 15, 2);

            $table->string('beneficiary_name');
            $table->string('beneficiary_account', 64);
            $table->string('beneficiary_bank', 64);

            $table->string('notes', 255)->nullable();

            $table->string('status', 32)->default('pending_approval');

            $table->string('midtrans_reference_no', 128)->unique()->nullable();
            $table->string('midtrans_initial_status', 32)->nullable();

            $table->timestamp('requested_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('processing_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('failed_at')->nullable();

            $table->text('failed_reason')->nullable();
            $table->json('midtrans_notification_payload')->nullable();

            $table->timestamps();

            $table->index(['muthowif_profile_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('muthowif_withdrawals');
    }
};

