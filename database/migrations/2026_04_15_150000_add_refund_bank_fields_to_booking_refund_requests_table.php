<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('booking_refund_requests', function (Blueprint $table) {
            $table->string('refund_bank_name', 100)->nullable()->after('customer_note');
            $table->string('refund_account_holder', 255)->nullable()->after('refund_bank_name');
            $table->string('refund_account_number', 64)->nullable()->after('refund_account_holder');
        });
    }

    public function down(): void
    {
        Schema::table('booking_refund_requests', function (Blueprint $table) {
            $table->dropColumn([
                'refund_bank_name',
                'refund_account_holder',
                'refund_account_number',
            ]);
        });
    }
};
