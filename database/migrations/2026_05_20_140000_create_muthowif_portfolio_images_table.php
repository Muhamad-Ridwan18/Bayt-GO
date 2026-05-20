<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('muthowif_portfolio_images', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('muthowif_portfolio_id')->constrained('muthowif_portfolios')->cascadeOnDelete();
            $table->string('path', 1000);
            $table->string('original_name')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['muthowif_portfolio_id', 'sort_order'], 'mpi_portfolio_sort_idx');
        });

        $now = now();
        $portfolios = DB::table('muthowif_portfolios')
            ->select(['id', 'image_path'])
            ->whereNotNull('image_path')
            ->get();

        foreach ($portfolios as $portfolio) {
            DB::table('muthowif_portfolio_images')->insert([
                'id' => (string) Str::uuid(),
                'muthowif_portfolio_id' => (string) $portfolio->id,
                'path' => (string) $portfolio->image_path,
                'original_name' => basename((string) $portfolio->image_path),
                'sort_order' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('muthowif_portfolio_images');
    }
};
