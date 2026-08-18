<?php

namespace Tests\Unit\Jobs;

use App\Jobs\SendWhatsAppTextJob;
use App\Services\FonnteService;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\TestCase;

class SendWhatsAppTextJobTest extends TestCase
{
    public function test_cached_message_is_pulled_and_not_left_in_job_payload(): void
    {
        Cache::flush();

        $fonnte = Mockery::mock(FonnteService::class);
        $fonnte->shouldReceive('sendText')
            ->once()
            ->with('81234567890', 'secret-otp-body', '62');

        $this->app->instance(FonnteService::class, $fonnte);

        $key = 'wa_outbound:testkey';
        Cache::put($key, 'secret-otp-body', now()->addMinutes(5));

        $job = new SendWhatsAppTextJob(
            target: '81234567890',
            message: '',
            countryCallingCode: '62',
            messageCacheKey: $key,
        );

        $this->assertSame('', $job->message);

        $job->handle(app(FonnteService::class));

        $this->assertNull(Cache::get($key));
    }
}
