<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('muthowif_bookings', function (Blueprint $table) {
            if (! Schema::hasColumn('muthowif_bookings', 'muthowif_rejection_kind')) {
                $table->string('muthowif_rejection_kind', 32)->nullable();
            }
            if (! Schema::hasColumn('muthowif_bookings', 'muthowif_rejection_note')) {
                $table->text('muthowif_rejection_note')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('muthowif_bookings', function (Blueprint $table) {
            if (Schema::hasColumn('muthowif_bookings', 'muthowif_rejection_note')) {
                $table->dropColumn('muthowif_rejection_note');
            }
            if (Schema::hasColumn('muthowif_bookings', 'muthowif_rejection_kind')) {
                $table->dropColumn('muthowif_rejection_kind');
            }
        });
    }
};
