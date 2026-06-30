<?php

namespace Database\Seeders;

use App\Enums\MuthowifVerificationStatus;
use App\Enums\SupportPackageCategory;
use App\Models\MuthowifProfile;
use App\Models\MuthowifSupportPackage;
use Illuminate\Database\Seeder;

class SupportPackageSeeder extends Seeder
{
    /**
     * @return list<array{category: SupportPackageCategory, name: string, description: string, price: int, min_pilgrims: int, max_pilgrims: int, sort_order: int}>
     */
    private function categoryTemplates(): array
    {
        return [
            [
                'category' => SupportPackageCategory::Tawaf,
                'name' => 'Dorong kursi roda tawaf',
                'description' => 'Pendampingan dorong kursi roda selama tawaf di Masjidil Haram. Selesai setelah satu kali tawaf.',
                'price' => 350000,
                'min_pilgrims' => 1,
                'max_pilgrims' => 2,
                'sort_order' => 0,
            ],
            [
                'category' => SupportPackageCategory::Umrah,
                'name' => 'Pendamping umrah singkat',
                'description' => "Bimbingan ihram, tawaf, sa'i, dan tahallul untuk satu kali umrah.",
                'price' => 500000,
                'min_pilgrims' => 1,
                'max_pilgrims' => 5,
                'sort_order' => 1,
            ],
            [
                'category' => SupportPackageCategory::Ziarah,
                'name' => 'Ziarah Rawdah & Raudhah',
                'description' => 'Pendamping ziarah ke Rawdah dengan koordinasi jadwal kunjungan.',
                'price' => 400000,
                'min_pilgrims' => 1,
                'max_pilgrims' => 8,
                'sort_order' => 2,
            ],
            [
                'category' => SupportPackageCategory::Mobility,
                'name' => 'Bantuan mobilitas jamaah lansia',
                'description' => 'Pendampingan mobilitas di area masjid dan hotel — kursi roda, tongkat, atau panduan jalur aman.',
                'price' => 300000,
                'min_pilgrims' => 1,
                'max_pilgrims' => 3,
                'sort_order' => 3,
            ],
            [
                'category' => SupportPackageCategory::Other,
                'name' => 'Konsultasi ibadah on-site',
                'description' => 'Sesi tanya jawab singkat seputar tata cara ibadah di Tanah Suci (±1 jam).',
                'price' => 200000,
                'min_pilgrims' => 1,
                'max_pilgrims' => 4,
                'sort_order' => 4,
            ],
        ];
    }

    public function run(): void
    {
        $profiles = MuthowifProfile::query()
            ->where('verification_status', MuthowifVerificationStatus::Approved)
            ->get();

        if ($profiles->isEmpty()) {
            $this->command?->warn('SupportPackageSeeder: tidak ada muthowif approved, dilewati.');

            return;
        }

        $templates = $this->categoryTemplates();
        $created = 0;

        foreach ($profiles as $profile) {
            foreach ($templates as $template) {
                MuthowifSupportPackage::query()->updateOrCreate(
                    [
                        'muthowif_profile_id' => $profile->getKey(),
                        'category' => $template['category']->value,
                        'name' => $template['name'],
                    ],
                    [
                        'description' => $template['description'],
                        'price' => $template['price'],
                        'min_pilgrims' => $template['min_pilgrims'],
                        'max_pilgrims' => $template['max_pilgrims'],
                        'is_active' => true,
                        'sort_order' => $template['sort_order'],
                    ]
                );
                $created++;
            }
        }

        $this->command?->info(sprintf(
            'SupportPackageSeeder: %d paket (%d kategori × %d muthowif).',
            $created,
            count($templates),
            $profiles->count()
        ));
    }
}
