<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('muthowif_support_packages', function (Blueprint $table): void {
            if (! Schema::hasColumn('muthowif_support_packages', 'category')) {
                $table->string('category', 32)->default('other')->after('name');
                $table->index(['category', 'is_active'], 'muthowif_support_pkg_category_active_idx');
            }
        });
    }

    public function down(): void
    {
        Schema::table('muthowif_support_packages', function (Blueprint $table): void {
            if (Schema::hasColumn('muthowif_support_packages', 'category')) {
                $table->dropIndex('muthowif_support_pkg_category_active_idx');
                $table->dropColumn('category');
            }
        });
    }
};
