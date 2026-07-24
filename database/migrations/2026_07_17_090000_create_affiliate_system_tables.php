<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('affiliates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('code', 32)->unique();
            $table->string('status', 32)->default('active');
            $table->decimal('available_balance', 15, 2)->default(0);
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('deactivated_at')->nullable();
            $table->timestamps();

            $table->index(['status']);
        });

        Schema::create('affiliate_bank_accounts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('affiliate_id')->constrained('affiliates')->cascadeOnDelete();
            $table->string('bank_code', 64);
            $table->string('bank_name', 128);
            $table->string('account_holder', 100);
            $table->string('account_number', 64);
            $table->boolean('is_primary')->default(false);
            $table->string('verification_status', 32)->default('pending');
            $table->timestamp('verified_at')->nullable();
            $table->foreignUuid('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();

            $table->index(['affiliate_id', 'verification_status']);
        });

        Schema::table('muthowif_bookings', function (Blueprint $table) {
            $table->foreignUuid('affiliate_id')->nullable()->after('customer_id')->constrained('affiliates')->nullOnDelete();
            $table->string('affiliate_code_snapshot', 32)->nullable()->after('affiliate_id');
            $table->decimal('affiliate_rate_snapshot', 8, 6)->nullable()->after('affiliate_code_snapshot');
            $table->decimal('affiliate_base_amount_snapshot', 15, 2)->nullable()->after('affiliate_rate_snapshot');
            $table->decimal('affiliate_commission_amount', 15, 2)->nullable()->after('affiliate_base_amount_snapshot');

            $table->index(['affiliate_id']);
        });

        Schema::create('affiliate_commissions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('affiliate_id')->constrained('affiliates')->cascadeOnDelete();
            $table->foreignUuid('muthowif_booking_id')->unique()->constrained('muthowif_bookings')->cascadeOnDelete();
            $table->foreignUuid('booking_payment_id')->nullable()->constrained('booking_payments')->nullOnDelete();
            $table->foreignUuid('customer_id')->constrained('users')->cascadeOnDelete();
            $table->string('affiliate_code_snapshot', 32);
            $table->decimal('commission_rate_snapshot', 8, 6);
            $table->decimal('transaction_base_amount_snapshot', 15, 2);
            $table->decimal('platform_fee_amount_snapshot', 15, 2)->default(0);
            $table->decimal('commission_amount', 15, 2);
            $table->string('status', 32)->default('pending');
            $table->timestamp('pending_at')->nullable();
            $table->timestamp('available_at')->nullable();
            $table->timestamp('voided_at')->nullable();
            $table->string('void_reason', 255)->nullable();
            $table->timestamps();

            $table->index(['affiliate_id', 'status']);
            $table->index(['status', 'available_at']);
        });

        Schema::create('affiliate_wallet_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('affiliate_id')->constrained('affiliates')->cascadeOnDelete();
            $table->decimal('amount', 15, 2);
            $table->decimal('balance_after', 15, 2);
            $table->string('type', 64);
            $table->string('source_type', 64)->nullable();
            $table->uuid('source_id')->nullable();
            $table->string('idempotency_key', 128)->unique();
            $table->string('description', 255)->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('occurred_at')->nullable();
            $table->timestamps();

            $table->index(['affiliate_id', 'occurred_at']);
            $table->index(['source_type', 'source_id']);
        });

        Schema::create('affiliate_withdrawals', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('affiliate_id')->constrained('affiliates')->cascadeOnDelete();
            $table->foreignUuid('affiliate_bank_account_id')->nullable()->constrained('affiliate_bank_accounts')->nullOnDelete();
            $table->decimal('amount', 15, 2);
            $table->string('beneficiary_name', 100);
            $table->string('beneficiary_account', 64);
            $table->string('beneficiary_bank', 64);
            $table->string('notes', 255)->nullable();
            $table->string('status', 32)->default('requested');
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->foreignUuid('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('failed_reason')->nullable();
            $table->string('transfer_proof_path', 512)->nullable();
            $table->timestamps();

            $table->index(['affiliate_id', 'status']);
            $table->index(['status', 'requested_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('affiliate_withdrawals');
        Schema::dropIfExists('affiliate_wallet_transactions');
        Schema::dropIfExists('affiliate_commissions');

        Schema::table('muthowif_bookings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('affiliate_id');
            $table->dropColumn([
                'affiliate_code_snapshot',
                'affiliate_rate_snapshot',
                'affiliate_base_amount_snapshot',
                'affiliate_commission_amount',
            ]);
        });

        Schema::dropIfExists('affiliate_bank_accounts');
        Schema::dropIfExists('affiliates');
    }
};
