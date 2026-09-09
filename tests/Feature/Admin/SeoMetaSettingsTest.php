<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\User;
use App\Support\SeoMetaOverrides;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoMetaSettingsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => UserRole::Admin]);
    }

    public function test_page_is_restricted_to_admins(): void
    {
        $this->get(route('admin.seo-meta-settings.edit'))->assertRedirect();

        $this->actingAs(User::factory()->create(['role' => UserRole::Customer]))
            ->get(route('admin.seo-meta-settings.edit'))
            ->assertRedirect();

        $this->actingAs($this->admin())
            ->get(route('admin.seo-meta-settings.edit'))
            ->assertOk()
            ->assertSee(__('admin.seo_meta.title'));
    }

    public function test_saved_override_replaces_the_homepage_title_and_description(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.seo-meta-settings.update'), [
                SeoMetaOverrides::inputName('home', 'id', 'title') => 'Sewa Muthowif Terpercaya',
                SeoMetaOverrides::inputName('home', 'id', 'description') => 'Deskripsi beranda hasil override admin.',
            ])
            ->assertRedirect(route('admin.seo-meta-settings.edit'))
            ->assertSessionHas('status');

        $this->get(route('welcome'))
            ->assertOk()
            ->assertSee('<title>Sewa Muthowif Terpercaya | '.config('app.name').'</title>', false)
            ->assertSee('content="Deskripsi beranda hasil override admin."', false);
    }

    public function test_empty_override_falls_back_to_the_language_file(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.seo-meta-settings.update'), [
                SeoMetaOverrides::inputName('articles', 'id', 'title') => '   ',
            ])->assertRedirect();

        $this->get(route('articles.index'))
            ->assertOk()
            ->assertSee(__('articles.index_title'));
    }

    public function test_override_is_applied_per_locale(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.seo-meta-settings.update'), [
                SeoMetaOverrides::inputName('articles', 'id', 'title') => 'Artikel Umroh Pilihan',
                SeoMetaOverrides::inputName('articles', 'en', 'title') => 'Curated Umrah Articles',
            ])->assertRedirect();

        $this->get(route('articles.index'))->assertOk()->assertSee('Artikel Umroh Pilihan', false);
        $this->get(route('en.articles.index'))->assertOk()->assertSee('Curated Umrah Articles', false);
    }

    public function test_landing_override_changes_meta_without_changing_visible_heading(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.seo-meta-settings.update'), [
                SeoMetaOverrides::inputName('landing_jakarta', 'id', 'title') => 'Muthowif Jakarta Bersertifikat',
            ])->assertRedirect();

        $this->get(route('seo.landing', 'jakarta'))
            ->assertOk()
            ->assertSee('<title>Muthowif Jakarta Bersertifikat | '.config('app.name').'</title>', false)
            ->assertSee(config('seo.landing_pages.jakarta.subtitle'), false);
    }

    public function test_overlong_values_are_rejected(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.seo-meta-settings.update'), [
                SeoMetaOverrides::inputName('home', 'id', 'title') => str_repeat('a', SeoMetaOverrides::TITLE_MAX + 1),
            ])
            ->assertSessionHasErrors(SeoMetaOverrides::inputName('home', 'id', 'title'));
    }
}
