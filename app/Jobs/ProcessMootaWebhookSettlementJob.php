<?php

namespace App\Jobs;

use App\Events\MootaWebhookRecorded;
use App\Listeners\ProcessMootaWebhookForBookingPayments;
use App\Models\MootaWebhookHistory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessMootaWebhookSettlementJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(public MootaWebhookHistory $history) {}

    public function handle(ProcessMootaWebhookForBookingPayments $listener): void
    {
        $listener->handle(new MootaWebhookRecorded($this->history));
    }
}
