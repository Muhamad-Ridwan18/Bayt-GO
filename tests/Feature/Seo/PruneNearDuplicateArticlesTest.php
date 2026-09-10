<?php

namespace Tests\Feature\Seo;

use App\Models\Article;
use App\Support\WelcomePageCache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class PruneNearDuplicateArticlesTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_unpublishes_near_duplicate_articles_and_keeps_the_canonical_ones(): void
    {
        $keeper = $this->publish('menjaga-kesehatan-jamaah-lansia-saat-umroh-tips-muthowif');
        $dupe = $this->publish('panduan-kesehatan-jamaah-lansia-umroh');
        $roleKeeper = $this->publish('mengenal-peran-muthowif-pendamping-ibadah-umrah');
        $roleDupe = $this->publish('pentingnya-muthowif-terpercaya-ibadah-umroh');

        Cache::put(WelcomePageCache::KEY, ['x' => 1], 3600);

        $this->artisan('seo:prune-duplicate-articles')->assertSuccessful();

        $this->assertTrue($keeper->fresh()->is_published);
        $this->assertTrue($roleKeeper->fresh()->is_published);
        $this->assertFalse($dupe->fresh()->is_published);
        $this->assertFalse($roleDupe->fresh()->is_published);
        $this->assertFalse(Cache::has(WelcomePageCache::KEY));
    }

    public function test_dry_run_does_not_change_publish_state(): void
    {
        $dupe = $this->publish('panduan-kesehatan-jamaah-lansia-umroh');

        $this->artisan('seo:prune-duplicate-articles', ['--dry-run' => true])->assertSuccessful();

        $this->assertTrue($dupe->fresh()->is_published);
    }

    private function publish(string $slug): Article
    {
        return Article::query()->create([
            'slug' => $slug,
            'is_published' => true,
            'is_featured' => false,
            'sort_order' => 0,
            'published_at' => now()->subDays(2),
            'translations' => [
                'id' => [
                    'title' => 'Judul '.$slug,
                    'excerpt' => 'Ringkasan',
                    'category' => 'Umroh',
                    'author' => 'BaytGo',
                    'body' => '<p>Isi.</p>',
                ],
            ],
        ]);
    }
}
