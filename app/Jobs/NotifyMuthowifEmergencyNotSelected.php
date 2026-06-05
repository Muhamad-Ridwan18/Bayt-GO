<?php

namespace App\Jobs;

use App\Models\BookingReplacementOffer;
use App\Services\Emergency\EmergencyWhatsAppNotifier;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class NotifyMuthowifEmergencyNotSelected implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $offerId,
    ) {}

    public function handle(EmergencyWhatsAppNotifier $notifier): void
    {
        $offer = BookingReplacementOffer::query()->find($this->offerId);
        if ($offer) {
            $notifier->notifyMuthowifNotSelected($offer);
        }
    }
}
