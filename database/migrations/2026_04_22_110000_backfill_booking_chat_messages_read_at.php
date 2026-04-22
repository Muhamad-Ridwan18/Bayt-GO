<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('booking_chat_messages')->whereNull('read_at')->update(['read_at' => DB::raw('created_at')]);
    }

    public function down(): void
    {
        // Data backfill — no safe rollback.
    }
};
