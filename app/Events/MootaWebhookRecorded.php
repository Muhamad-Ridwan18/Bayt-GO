<?php

namespace App\Events;

use App\Models\MootaWebhookHistory;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Webhook Moota tersimpan — listener (settlement booking) dipanggil tanpa broadcast dulu.
 * Broadcast UI admin: {@see MootaWebhookRealtimeBroadcast}.
 */
class MootaWebhookRecorded
{
    use Dispatchable, SerializesModels;

    public function __construct(public MootaWebhookHistory $history) {}
}
