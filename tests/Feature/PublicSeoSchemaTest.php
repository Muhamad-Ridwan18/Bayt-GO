<?php

namespace Tests\Feature;

use App\Enums\MuthowifServiceType;
use App\Enums\SupportPackageCategory;
use App\Enums\UserRole;
use App\Models\MuthowifProfile;
use App\Models\MuthowifService;
use App\Models\MuthowifSupportPackage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PublicSeoSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_exposes_website_and_organization_schema(): void
    {
        $this->get(route('welcome'))
            ->assertOk()
            ->assertSee('"@type": "WebSite"', false)
            ->assertSee('"@type": "Organization"', false)
            ->assertSee(asset('images/logo.png'), false);
    }

    public function test_support_package_page_exposes_product_and_breadcrumb_schema(): void
    {
        $package = $this->activeSupportPackage();

        $this->get(route('layanan-pendukung.show', $package))
            ->assertOk()
            ->assertSee('"@type": "Product"', false)
            ->assertSee('"@type": "Offer"', false)
            ->assertSee('"priceCurrency": "IDR"', false)
            ->assertSee('"@type": "BreadcrumbList"', false);
    }

    public function test_search_engine_visitor_without_session_gets_indonesian(): void
    {
        $this->get(route('welcome'))
            ->assertOk()
            ->assertSee('<html lang="id"', false)
            ->assertSee('Jasa Tour Guide Ibadah Umroh', false)
            ->assertSee('Temukan Muthowif terbaik', false);
    }

    public function test_meta_follows_active_locale_on_profile_pages(): void
    {
        $profile = $this->activeSupportPackage()->muthowifProfile;

        $this->get(route('layanan.show', $profile))
            ->assertOk()
            ->assertSee('<html lang="id"', false)
            ->assertSee('Jasa Tour Guide Umroh', false);

        $this->withSession(['locale' => 'en'])
            ->get(route('layanan.show', $profile))
            ->assertOk()
            ->assertSee('<html lang="en"', false)
            ->assertSee('Trusted Umrah', false)
            ->assertDontSee('Jasa Tour Guide Umroh', false);
    }

    public function test_home_preloads_hero_image_as_high_priority(): void
    {
        $this->get(route('welcome'))
            ->assertOk()
            ->assertSee('rel="preload" as="image"', false)
            ->assertSee('fetchpriority="high"', false);
    }

    public function test_directory_cards_carry_descriptive_image_alt_text(): void
    {
        $this->activeSupportPackage();

        $this->get(route('layanan.index', [
            'start_date' => now()->addWeek()->toDateString(),
            'end_date' => now()->addWeeks(2)->toDateString(),
        ]))
            ->assertOk()
            ->assertSee('Ustadz Schema')
            ->assertSee(__('common.alt_muthowif_photo', ['name' => 'Ustadz Schema']), false);
    }

    public function test_sitemap_lists_article_slug_urls_that_actually_resolve(): void
    {
        $article = \App\Models\Article::query()->create([
            'slug' => 'panduan-tawaf',
            'is_published' => true,
            'is_featured' => false,
            'sort_order' => 0,
            'published_at' => now()->subDay(),
            'translations' => [
                'id' => [
                    'title' => 'Panduan Tawaf',
                    'excerpt' => 'Ringkasan panduan tawaf.',
                    'category' => 'Umroh',
                    'author' => 'BaytGo',
                    'body' => '<p>Isi.</p>',
                    'body_json' => '',
                    'body_md' => '',
                ],
            ],
        ]);

        $sitemap = $this->get('/sitemap-articles-1.xml')->assertOk()->getContent();

        $this->assertStringContainsString(route('articles.show', ['slug' => $article->slug], true), $sitemap);
        $this->assertStringNotContainsString('/artikel/'.$article->getKey(), $sitemap);

        $this->get('/sitemap-home-1.xml')
            ->assertOk()
            ->assertSee(route('layanan.index', [], true), false)
            ->assertSee(route('articles.index', [], true), false)
            ->assertSee(route('layanan-pendukung.index', [], true), false);
    }

    private function activeSupportPackage(): MuthowifSupportPackage
    {
        $muthowifUser = User::factory()->create([
            'role' => UserRole::Muthowif,
            'name' => 'Ustadz Schema',
        ]);

        $profile = MuthowifProfile::query()->create([
            'id' => (string) Str::uuid(),
            'user_id' => $muthowifUser->id,
            'phone' => '081234567890',
            'address' => 'Makkah',
            'nik' => '1234567890123456',
            'birth_date' => '1990-01-01',
            'photo_path' => 'photos/test.jpg',
            'ktp_image_path' => 'ktp/test.jpg',
            'languages' => ['Bahasa Indonesia'],
            'work_experiences' => ['5 tahun pengalaman'],
        ]);
        $profile->forceFill(['verification_status' => 'approved'])->save();

        MuthowifService::query()->create([
            'muthowif_profile_id' => $profile->id,
            'type' => MuthowifServiceType::Group,
            'name' => 'Paket Group',
            'daily_price' => 500000,
            'min_pilgrims' => 2,
            'max_pilgrims' => 10,
        ]);

        return MuthowifSupportPackage::query()->create([
            'muthowif_profile_id' => $profile->id,
            'name' => 'Sewa Kursi Roda Tawaf',
            'category' => SupportPackageCategory::Mobility,
            'description' => 'Pendampingan tawaf dengan kursi roda beserta petugas dorong.',
            'price' => 350000,
            'min_pilgrims' => 1,
            'max_pilgrims' => 1,
            'is_active' => true,
            'sort_order' => 0,
        ]);
    }
}
