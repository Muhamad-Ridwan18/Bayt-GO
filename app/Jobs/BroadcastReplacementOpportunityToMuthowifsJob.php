<?php

namespace App\Jobs;

use App\Models\BookingIncident;
use App\Services\Incident\BookingReplacementRecruitmentService;
use App\Services\Incident\ReplacementOpportunityWhatsAppNotifier;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class BroadcastReplacementOpportunityToMuthowifsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $incidentId,
    ) {}

    public function handle(
        BookingReplacementRecruitmentService $recruitment,
        ReplacementOpportunityWhatsAppNotifier $notifier,
    ): void {
        $incident = BookingIncident::query()
            ->with(['muthowifBooking.customer', 'muthowifBooking.muthowifProfile.user'])
            ->find($this->incidentId);

        if ($incident === null || ! $incident->replacement_recruitment_open) {
            return;
        }

        foreach ($recruitment->eligibleMuthowifProfiles($incident) as $profile) {
            $notifier->notifyOpportunity($incident, $profile);
        }
    }
}
