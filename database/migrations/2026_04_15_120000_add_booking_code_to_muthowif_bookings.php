<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_daily_sequences', function (Blueprint $table): void {
            $table->string('date_key', 8)->primary();
            $table->unsignedBigInteger('next_seq')->default(0);
        });

        Schema::table('muthowif_bookings', function (Blueprint $table): void {
            $table->string('booking_code', 32)->nullable()->unique()->after('id');
        });

        $tz = config('app.timezone', 'UTC');
        $rows = DB::table('muthowif_bookings')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get(['id', 'created_at']);

        $perDayMax = [];

        foreach ($rows as $row) {
            $ymd = Carbon::parse($row->created_at)->timezone($tz)->format('ymd');
            $perDayMax[$ymd] = ($perDayMax[$ymd] ?? 0) + 1;
            $seq = $perDayMax[$ymd];
            $code = 'BK-BYTG'.$ymd.$seq;
            DB::table('muthowif_bookings')->where('id', $row->id)->update(['booking_code' => $code]);
        }

        foreach ($perDayMax as $ymd => $max) {
            DB::table('booking_daily_sequences')->insert([
                'date_key' => $ymd,
                'next_seq' => $max,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('muthowif_bookings', function (Blueprint $table): void {
            $table->dropUnique(['booking_code']);
            $table->dropColumn('booking_code');
        });

        Schema::dropIfExists('booking_daily_sequences');
    }
};
