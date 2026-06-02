<?php

namespace App\Jobs;

use App\Models\BookingIncident;
use App\Services\Incident\ReplacementOpportunityWhatsAppNotifier;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class NotifyCustomerOfReplacementPoolJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $incidentId,
    ) {}

    public function handle(ReplacementOpportunityWhatsAppNotifier $notifier): void
    {
        $incident = BookingIncident::query()
            ->with(['muthowifBooking.customer'])
            ->find($this->incidentId);

        if ($incident === null || $incident->customer_choice_opened_at === null) {
            return;
        }

        $notifier->notifyCustomerPoolReady($incident);
    }
}
