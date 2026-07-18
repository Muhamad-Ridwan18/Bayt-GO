<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('affiliate_bank_accounts')
            ->where('verification_status', 'pending')
            ->update([
                'verification_status' => 'verified',
                'verified_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        //
    }
};
