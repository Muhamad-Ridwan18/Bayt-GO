<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Menyelaraskan muthowif_profiles & muthowif_supporting_documents dengan users.id (CHAR(36) / UUID).
 * Instalasi lama memakai BIGINT id + foreignId(user_id) yang tidak cocok dengan users.id.
 *
 * Me-rebuild tabel: data baris lama di tabel ini akan hilang.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! $this->shouldRebuild()) {
            return;
        }

        Schema::disableForeignKeyConstraints();

        try {
            Schema::dropIfExists('muthowif_supporting_documents');
            Schema::dropIfExists('muthowif_profiles');

            Schema::create('muthowif_profiles', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('phone', 32);
                $table->string('city', 120)->nullable();
                $table->text('address');
                $table->string('nik', 16);
                $table->date('birth_date');
                $table->string('passport_number', 64)->nullable();
                $table->json('languages')->nullable();
                $table->json('educations')->nullable();
                $table->json('work_experiences')->nullable();
                $table->text('reference_text')->nullable();
                $table->string('photo_path');
                $table->string('ktp_image_path');
                $table->string('verification_status', 32)->default('pending');
                $table->timestamp('verified_at')->nullable();
                $table->text('rejection_reason')->nullable();
                $table->timestamps();

                $table->unique('user_id');
                $table->index('verification_status');
            });

            Schema::create('muthowif_supporting_documents', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('muthowif_profile_id')->constrained('muthowif_profiles')->cascadeOnDelete();
                $table->string('path');
                $table->string('original_name')->nullable();
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->timestamps();

                $table->index('muthowif_profile_id');
            });
        } finally {
            Schema::enableForeignKeyConstraints();
        }
    }

    public function down(): void
    {
        // Tidak mengembalikan skema BIGINT; migrasi satu arah untuk perbaikan skema.
    }

    private function shouldRebuild(): bool
    {
        if (! Schema::hasTable('muthowif_profiles')) {
            return false;
        }

        $driver = Schema::getConnection()->getDriverName();
        if ($driver !== 'mysql') {
            $col = Schema::getColumnType('muthowif_profiles', 'user_id');

            return in_array($col, ['integer', 'int', 'bigint'], true);
        }

        $row = DB::selectOne(
            'SELECT DATA_TYPE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            ['muthowif_profiles', 'user_id']
        );

        if ($row === null) {
            return true;
        }

        $type = strtolower((string) $row->DATA_TYPE);

        return $type === 'bigint' || $type === 'int';
    }
};
