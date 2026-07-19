<?php

namespace Database\Seeders;

use App\Models\SiteSetting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            'platform_fee_rate' => '0.075',
            'affiliate_commission_rate' => '0.01',
            'affiliate_commission_tiers' => json_encode([
                ['min' => 0, 'rate' => 0.01],
                ['min' => 250000000, 'rate' => 0.015],
                ['min' => 500000000, 'rate' => 0.02],
            ]),
            'affiliate_min_withdraw' => '100000',
        ];

        foreach ($settings as $key => $value) {
            SiteSetting::updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }
    }
}
