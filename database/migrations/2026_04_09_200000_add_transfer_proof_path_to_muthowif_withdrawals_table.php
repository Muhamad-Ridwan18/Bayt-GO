<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('muthowif_withdrawals', function (Blueprint $table): void {
            $table->string('transfer_proof_path')->nullable()->after('failed_reason');
        });
    }

    public function down(): void
    {
        Schema::table('muthowif_withdrawals', function (Blueprint $table): void {
            $table->dropColumn('transfer_proof_path');
        });
    }
};

