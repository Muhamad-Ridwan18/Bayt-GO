<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('booking_chat_messages', function (Blueprint $table) {
            $table->timestamp('read_at')->nullable()->after('image_path');
            $table->index(['muthowif_booking_id', 'read_at']);
        });

        // Existing rows: treat as already delivered/read so badge and receipts stay sane.
        DB::table('booking_chat_messages')->whereNull('read_at')->update(['read_at' => DB::raw('created_at')]);
    }

    public function down(): void
    {
        Schema::table('booking_chat_messages', function (Blueprint $table) {
            $table->dropIndex(['muthowif_booking_id', 'read_at']);
            $table->dropColumn('read_at');
        });
    }
};
