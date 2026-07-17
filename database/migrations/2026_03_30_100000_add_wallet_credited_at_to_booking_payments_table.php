<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('booking_payments', function (Blueprint $table) {
            $table->timestamp('wallet_credited_at')->nullable()->after('settled_at');
        });

        // Backfill: data yang sudah settlement/capture sebelumnya sudah terlanjur mengkredit
        // saldo di versi kode lama. Tandai supaya saat "selesaikan layanan" tidak double kredit.
        $nowFn = DB::getDriverName() === 'sqlite' ? "COALESCE(settled_at, datetime('now'))" : 'COALESCE(settled_at, NOW())';

        DB::table('booking_payments')
            ->whereIn('status', ['settlement', 'capture'])
            ->whereNull('wallet_credited_at')
            ->update([
                'wallet_credited_at' => DB::raw($nowFn),
            ]);
    }

    public function down(): void
    {
        Schema::table('booking_payments', function (Blueprint $table) {
            $table->dropColumn('wallet_credited_at');
        });
    }
};

