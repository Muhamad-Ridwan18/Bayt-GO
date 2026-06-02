<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (! DB::getSchemaBuilder()->hasTable('booking_incidents')) {
            return;
        }

        DB::table('booking_incidents')
            ->where('case_type', 'unresponsive_pre_h')
            ->update(['case_type' => 'lost_contact_in_service']);
    }

    public function down(): void
    {
        // Tipe unresponsive_pre_h tidak lagi dipakai di aplikasi.
    }
};
