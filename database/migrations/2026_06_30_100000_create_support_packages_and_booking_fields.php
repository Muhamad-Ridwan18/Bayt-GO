<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('muthowif_support_packages')) {
            Schema::create('muthowif_support_packages', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->foreignUuid('muthowif_profile_id')->constrained('muthowif_profiles')->cascadeOnDelete();
                $table->string('name', 160);
                $table->text('description')->nullable();
                $table->decimal('price', 15, 2);
                $table->unsignedSmallInteger('min_pilgrims')->default(1);
                $table->unsignedSmallInteger('max_pilgrims')->default(10);
                $table->boolean('is_active')->default(true);
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();

                $table->index(['muthowif_profile_id', 'is_active', 'sort_order'], 'muthowif_support_pkg_profile_active_idx');
            });
        } elseif (! $this->indexExists('muthowif_support_packages', 'muthowif_support_pkg_profile_active_idx')) {
            Schema::table('muthowif_support_packages', function (Blueprint $table): void {
                $table->index(['muthowif_profile_id', 'is_active', 'sort_order'], 'muthowif_support_pkg_profile_active_idx');
            });
        }

        Schema::table('muthowif_bookings', function (Blueprint $table): void {
            if (! Schema::hasColumn('muthowif_bookings', 'support_package_id')) {
                $table->foreignUuid('support_package_id')->nullable()->after('service_type')->constrained('muthowif_support_packages')->nullOnDelete();
            }
            if (! Schema::hasColumn('muthowif_bookings', 'starts_at')) {
                $table->timestamp('starts_at')->nullable()->after('ends_on');
            }
            if (! Schema::hasColumn('muthowif_bookings', 'package_price_snapshot')) {
                $table->decimal('package_price_snapshot', 15, 2)->nullable()->after('transport_price_snapshot');
            }
            if (! Schema::hasColumn('muthowif_bookings', 'package_name_snapshot')) {
                $table->string('package_name_snapshot', 160)->nullable()->after('package_price_snapshot');
            }
            if (! Schema::hasColumn('muthowif_bookings', 'completion_requested_at')) {
                $table->timestamp('completion_requested_at')->nullable()->after('package_name_snapshot');
            }
            if (! Schema::hasColumn('muthowif_bookings', 'completion_requested_by')) {
                $table->foreignUuid('completion_requested_by')->nullable()->after('completion_requested_at')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('muthowif_bookings', 'completed_at')) {
                $table->timestamp('completed_at')->nullable()->after('completion_requested_by');
            }
            if (! Schema::hasColumn('muthowif_bookings', 'completed_by')) {
                $table->foreignUuid('completed_by')->nullable()->after('completed_at')->constrained('users')->nullOnDelete();
            }
        });
    }

    private function indexExists(string $table, string $index): bool
    {
        $connection = Schema::getConnection();
        $database = $connection->getDatabaseName();

        $result = $connection->select(
            'SELECT 1 FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ? LIMIT 1',
            [$database, $table, $index]
        );

        return $result !== [];
    }

    public function down(): void
    {
        Schema::table('muthowif_bookings', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('support_package_id');
            $table->dropConstrainedForeignId('completion_requested_by');
            $table->dropConstrainedForeignId('completed_by');
            $table->dropColumn([
                'starts_at',
                'package_price_snapshot',
                'package_name_snapshot',
                'completion_requested_at',
                'completed_at',
            ]);
        });

        Schema::dropIfExists('muthowif_support_packages');
    }
};
