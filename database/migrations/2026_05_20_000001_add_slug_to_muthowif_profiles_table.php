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
        if (! Schema::hasColumn('muthowif_profiles', 'slug')) {
            Schema::table('muthowif_profiles', function (Blueprint $table) {
                $table->string('slug')->nullable()->after('id')->unique();
            });
        }

        DB::table('muthowif_profiles as p')
            ->leftJoin('users as u', 'u.id', '=', 'p.user_id')
            ->whereNull('p.slug')
            ->orderBy('p.id')
            ->select(['p.id as id', 'u.name'])
            ->chunkById(100, function ($profiles) {
                foreach ($profiles as $profile) {
                    $slug = Str::slug($profile->name ?: $profile->id);
                    $slug = $this->uniqueSlug($slug, $profile->id);

                    DB::table('muthowif_profiles')
                        ->where('id', $profile->id)
                        ->update(['slug' => $slug]);
                }
            }, 'p.id');
    }

    public function down(): void
    {
        Schema::table('muthowif_profiles', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn('slug');
        });
    }

    protected function uniqueSlug(string $baseSlug, string $currentId): string
    {
        $slug = $baseSlug;
        $index = 1;

        while (DB::table('muthowif_profiles')
            ->where('slug', $slug)
            ->where('id', '!=', $currentId)
            ->exists()) {
            $slug = $baseSlug . '-' . $index;
            $index++;
        }

        return $slug;
    }
};
