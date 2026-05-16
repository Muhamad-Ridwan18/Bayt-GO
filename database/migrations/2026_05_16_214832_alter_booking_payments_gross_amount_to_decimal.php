<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('booking_payments', function (Blueprint $table) {
            // Ubah dari unsignedBigInteger (integer) → decimal(15,8)
            // agar bisa menyimpan nilai USD dengan desimal (misal: 10.4050).
            $table->decimal('gross_amount', 15, 8)->change();
        });
    }

    public function down(): void
    {
        Schema::table('booking_payments', function (Blueprint $table) {
            $table->unsignedBigInteger('gross_amount')->change();
        });
    }
};
