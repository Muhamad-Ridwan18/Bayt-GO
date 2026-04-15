<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('booking_refund_requests', function (Blueprint $table) {
            $table->string('transfer_proof_path')->nullable()->after('admin_note');
        });
    }

    public function down(): void
    {
        Schema::table('booking_refund_requests', function (Blueprint $table) {
            $table->dropColumn('transfer_proof_path');
        });
    }
};
