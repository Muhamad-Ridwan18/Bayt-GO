<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('muthowif_profiles')) {
            return;
        }

        Schema::table('muthowif_profiles', function (Blueprint $table) {
            if (! Schema::hasColumn('muthowif_profiles', 'passport_number')) {
                $table->string('passport_number', 64)->nullable()->after('birth_date');
            }
            if (! Schema::hasColumn('muthowif_profiles', 'languages')) {
                $table->json('languages')->nullable()->after('passport_number');
            }
            if (! Schema::hasColumn('muthowif_profiles', 'educations')) {
                $table->json('educations')->nullable()->after('languages');
            }
            if (! Schema::hasColumn('muthowif_profiles', 'work_experiences')) {
                $table->json('work_experiences')->nullable()->after('educations');
            }
            if (! Schema::hasColumn('muthowif_profiles', 'reference_text')) {
                $table->text('reference_text')->nullable()->after('work_experiences');
            }
        });

        if (Schema::hasColumn('muthowif_profiles', 'certificate_path')) {
            Schema::table('muthowif_profiles', function (Blueprint $table) {
                $table->dropColumn('certificate_path');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('muthowif_profiles')) {
            return;
        }

        Schema::table('muthowif_profiles', function (Blueprint $table) {
            if (Schema::hasColumn('muthowif_profiles', 'passport_number')) {
                $table->dropColumn('passport_number');
            }
            if (Schema::hasColumn('muthowif_profiles', 'languages')) {
                $table->dropColumn('languages');
            }
            if (Schema::hasColumn('muthowif_profiles', 'educations')) {
                $table->dropColumn('educations');
            }
            if (Schema::hasColumn('muthowif_profiles', 'work_experiences')) {
                $table->dropColumn('work_experiences');
            }
            if (Schema::hasColumn('muthowif_profiles', 'reference_text')) {
                $table->dropColumn('reference_text');
            }
        });

        if (! Schema::hasColumn('muthowif_profiles', 'certificate_path')) {
            Schema::table('muthowif_profiles', function (Blueprint $table) {
                $table->string('certificate_path')->nullable()->after('ktp_image_path');
            });
        }
    }
};
