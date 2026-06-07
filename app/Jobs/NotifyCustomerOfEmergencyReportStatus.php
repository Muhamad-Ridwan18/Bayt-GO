<?php

namespace App\Jobs;

use App\Models\BookingEmergencyReport;
use App\Services\Emergency\EmergencyWhatsAppNotifier;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class NotifyCustomerOfEmergencyReportStatus implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $reportId,
        /** @var 'under_review'|'verified'|'rejected' */
        public string $statusKey,
    ) {}

    public function handle(EmergencyWhatsAppNotifier $notifier): void
    {
        $report = BookingEmergencyReport::query()->find($this->reportId);
        if ($report) {
            $notifier->notifyCustomerOfReportStatus($report, $this->statusKey);
        }
    }
}
