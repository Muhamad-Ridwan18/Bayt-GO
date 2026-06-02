<?php

namespace App\Events\Concerns;

use Illuminate\Contracts\Broadcasting\ShouldRescue;

/**
 * Gagal kirim ke Reverb/Pusher tidak boleh menggagalkan HTTP request / webhook.
 */
interface RescuesBroadcastFailures extends ShouldRescue
{
}
