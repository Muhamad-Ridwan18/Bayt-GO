<?php

namespace Tests\Unit\Support;

use App\Jobs\SendMailjetTextJob;
use App\Support\DualChannelNotify;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class DualChannelNotifyTest extends TestCase
{
    public function test_queue_email_never_throws_and_skips_without_mailjet(): void
    {
        Bus::fake();

        DualChannelNotify::queueEmail(null, 'Hello');
        DualChannelNotify::queueEmail('not-an-email', 'Hello');
        DualChannelNotify::queueEmail('user@example.com', "Subject line\nBody");

        Bus::assertNotDispatched(SendMailjetTextJob::class);
    }
}
