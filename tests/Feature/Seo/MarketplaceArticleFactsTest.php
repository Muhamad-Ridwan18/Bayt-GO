<?php

namespace Tests\Feature\Seo;

use App\Enums\MuthowifServiceType;
use App\Enums\SupportPackageCategory;
use App\Enums\UserRole;
use App\Models\MuthowifProfile;
use App\Models\MuthowifService;
use App\Models\MuthowifSupportPackage;
use App\Models\User;
use App\Support\MarketplaceArticleFacts;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Tests\TestCase;

class MarketplaceArticleFactsTest extends TestCase
{
    use RefreshDatabase;

    private const TOKEN = 'test-articles-token';

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.articles_api.token' => self::TOKEN]);
        MarketplaceArticleFacts::forget();
    }

    public function test_it_computes_counts_and_price_bands_from_approved_profiles(): void
    {
        $this->seedMarketplace();

        $facts = MarketplaceArticleFacts::all();

        $this->assertSame(2, $facts['approved_muthowif_count']);
        $this->assertSame(2, $facts['marketplace_ready_count']);
        $this->assertSame(1, $facts['active_support_package_count']);
        $this->assertSame(400000, $facts['service_daily_price']['min']);
        $this->assertSame(900000, $facts['service_daily_price']['max']);
        $this->assertGreaterThan(0, $facts['service_daily_price']['sample_size']);
        $this->assertStringContainsString('DATA MARKETPLACE BAYTGO', $facts['prompt_text']);
        $this->assertStringContainsString('/layanan', $facts['prompt_text']);
        $this->assertNotEmpty($facts['work_locations']);
        $this->assertContains($facts['work_locations'][0]['label'], ['Makkah', 'Mekkah', 'makkah']);
        // Label lokasi mengikuti locale; cukup pastikan muncul di prompt.
        $this->assertStringContainsString($facts['work_locations'][0]['label'], $facts['prompt_text']);
    }

    public function test_outliers_and_unapproved_profiles_are_ignored(): void
    {
        $this->seedMarketplace();

        // Harga uji ekstrem tidak boleh menggeser max.
        $outlier = $this->createApprovedProfile('081200000099', 'Madinah');
        MuthowifService::query()->create([
            'muthowif_profile_id' => $outlier->id,
            'type' => MuthowifServiceType::PrivateJamaah,
            'name' => 'Outlier',
            'daily_price' => 200_000_000,
            'min_pilgrims' => 1,
            'max_pilgrims' => 2,
        ]);

        $pendingUser = User::factory()->create(['role' => UserRole::Muthowif]);
        $pending = MuthowifProfile::query()->create([
            'id' => (string) Str::uuid(),
            'user_id' => $pendingUser->id,
            'phone' => '081200000088',
            'address' => 'Jakarta',
            'nik' => '1234567890123499',
            'birth_date' => '1990-01-01',
            'photo_path' => 'photos/x.jpg',
            'ktp_image_path' => 'ktp/x.jpg',
            'languages' => ['Bahasa Indonesia'],
            'work_experiences' => ['1 tahun'],
            'work_location' => 'Jakarta',
        ]);
        MuthowifService::query()->create([
            'muthowif_profile_id' => $pending->id,
            'type' => MuthowifServiceType::Group,
            'name' => 'Pending',
            'daily_price' => 100000,
            'min_pilgrims' => 2,
            'max_pilgrims' => 10,
        ]);

        MarketplaceArticleFacts::forget();
        $facts = MarketplaceArticleFacts::all();

        $this->assertSame(3, $facts['approved_muthowif_count']);
        $this->assertSame(900000, $facts['service_daily_price']['max'], 'Harga 200jt harus diabaikan dari band.');
        $this->assertSame(3, $facts['marketplace_ready_count']);
        $this->assertStringNotContainsString('200.000.000', $facts['prompt_text']);
    }

    public function test_api_index_exposes_marketplace_facts(): void
    {
        $this->seedMarketplace();

        $facts = $this->withToken(self::TOKEN)
            ->getJson('/api/articles?limit=10')
            ->assertOk()
            ->json('marketplace_facts');

        $this->assertSame(2, $facts['approved_muthowif_count']);
        $this->assertNotEmpty($facts['prompt_text']);
    }

    private function seedMarketplace(): void
    {
        $a = $this->createApprovedProfile('081211111111', 'makkah');
        MuthowifService::query()->create([
            'muthowif_profile_id' => $a->id,
            'type' => MuthowifServiceType::Group,
            'name' => 'Grup',
            'daily_price' => 400000,
            'min_pilgrims' => 2,
            'max_pilgrims' => 10,
        ]);
        MuthowifService::query()->create([
            'muthowif_profile_id' => $a->id,
            'type' => MuthowifServiceType::PrivateJamaah,
            'name' => 'Private',
            'daily_price' => 900000,
            'min_pilgrims' => 1,
            'max_pilgrims' => 4,
        ]);
        MuthowifSupportPackage::query()->create([
            'muthowif_profile_id' => $a->id,
            'name' => 'Kursi roda',
            'category' => SupportPackageCategory::Mobility,
            'description' => 'Pendampingan',
            'price' => 350000,
            'min_pilgrims' => 1,
            'max_pilgrims' => 1,
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $b = $this->createApprovedProfile('081222222222', 'Madinah');
        MuthowifService::query()->create([
            'muthowif_profile_id' => $b->id,
            'type' => MuthowifServiceType::Group,
            'name' => 'Grup B',
            'daily_price' => 550000,
            'min_pilgrims' => 2,
            'max_pilgrims' => 8,
        ]);
    }

    private function createApprovedProfile(string $phone, string $workLocation): MuthowifProfile
    {
        $user = User::factory()->create(['role' => UserRole::Muthowif]);

        $profile = MuthowifProfile::query()->create([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'phone' => $phone,
            'address' => 'Alamat',
            'nik' => str_pad((string) random_int(1, 999999999999999), 16, '0', STR_PAD_LEFT),
            'birth_date' => '1990-01-01',
            'photo_path' => 'photos/test.jpg',
            'ktp_image_path' => 'ktp/test.jpg',
            'languages' => ['Bahasa Indonesia'],
            'work_experiences' => ['5 tahun'],
            'work_location' => $workLocation,
        ]);

        $profile->forceFill(['verification_status' => 'approved'])->save();

        return $profile->fresh();
    }
}
