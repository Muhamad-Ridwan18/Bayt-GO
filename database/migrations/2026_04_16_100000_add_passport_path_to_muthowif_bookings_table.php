<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('muthowif_bookings', function (Blueprint $table): void {
            $table->string('passport_path')->nullable()->after('ticket_return_path');
        });
    }

    public function down(): void
    {
        Schema::table('muthowif_bookings', function (Blueprint $table): void {
            $table->dropColumn('passport_path');
        });
    }
};
