<?php

namespace Tests\Unit\Support;

use App\Support\WelcomePageCache;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class WelcomePageCacheTest extends TestCase
{
    public function test_data_hydrates_empty_eloquent_collections_from_empty_id_lists(): void
    {
        Cache::put(WelcomePageCache::KEY, [
            'featuredMuthowifIds' => [],
            'latestArticleIds' => [],
            'landingPages' => [],
            'latestServiceIds' => [],
            'galleryImageIds' => [],
            'activeCampaignIds' => [],
        ], now()->addHour());

        $data = WelcomePageCache::data();

        $this->assertInstanceOf(EloquentCollection::class, $data['featuredMuthowifs']);
        $this->assertTrue($data['featuredMuthowifs']->isEmpty());
        $this->assertInstanceOf(EloquentCollection::class, $data['activeCampaigns']);
        $this->assertTrue($data['activeCampaigns']->isEmpty());
    }

    public function test_forget_clears_welcome_cache_keys(): void
    {
        Cache::put(WelcomePageCache::KEY, ['sample' => true], now()->addHour());
        Cache::put('welcome:page_data:v1', ['legacy' => true], now()->addHour());

        WelcomePageCache::forget();

        $this->assertFalse(Cache::has(WelcomePageCache::KEY));
        $this->assertFalse(Cache::has('welcome:page_data:v1'));
    }
}
