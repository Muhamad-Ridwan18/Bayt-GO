<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('muthowif_bookings', function (Blueprint $table): void {
            if (! Schema::hasColumn('muthowif_bookings', 'completion_code_hash')) {
                $table->string('completion_code_hash', 64)->nullable()->after('completed_by');
            }
            if (! Schema::hasColumn('muthowif_bookings', 'completion_code')) {
                $table->string('completion_code', 6)->nullable()->after('completion_code_hash');
            }
            if (! Schema::hasColumn('muthowif_bookings', 'completion_code_sent_at')) {
                $table->timestamp('completion_code_sent_at')->nullable()->after('completion_code');
            }
        });
    }

    public function down(): void
    {
        Schema::table('muthowif_bookings', function (Blueprint $table): void {
            foreach (['completion_code_sent_at', 'completion_code', 'completion_code_hash'] as $column) {
                if (Schema::hasColumn('muthowif_bookings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
