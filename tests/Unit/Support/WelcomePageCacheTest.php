<?php

namespace Tests\Unit\Support;

use App\Support\WelcomePageCache;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class WelcomePageCacheTest extends TestCase
{
    public function test_forget_clears_welcome_cache_keys(): void
    {
        Cache::put(WelcomePageCache::KEY, ['sample' => true], now()->addHour());
        Cache::put('welcome:page_data:v1', ['legacy' => true], now()->addHour());

        WelcomePageCache::forget();

        $this->assertFalse(Cache::has(WelcomePageCache::KEY));
        $this->assertFalse(Cache::has('welcome:page_data:v1'));
    }
}
