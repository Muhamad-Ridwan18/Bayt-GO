<?php

namespace App\Events;

use App\Events\Concerns\RescuesBroadcastFailures;
use App\Models\BookingEmergencyReport;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EmergencyReportUpdated implements RescuesBroadcastFailures, ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param  list<string>  $notifyUserIds
     */
    public function __construct(
        public BookingEmergencyReport $report,
        public ?string $action = null,
        public array $notifyUserIds = [],
    ) {}

    public function broadcastAs(): string
    {
        return 'emergency.report.updated';
    }

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        $this->report->loadMissing('muthowifBooking.muthowifProfile');

        $channels = [
            new PrivateChannel('admin.emergency-reports'),
        ];

        $booking = $this->report->muthowifBooking;
        if ($booking !== null) {
            $channels[] = new PrivateChannel('App.Models.User.'.$booking->customer_id);

            $muthowifUserId = $booking->muthowifProfile?->user_id;
            if ($muthowifUserId !== null) {
                $channels[] = new PrivateChannel('App.Models.User.'.$muthowifUserId);
            }
        }

        foreach ($this->notifyUserIds as $userId) {
            $id = trim((string) $userId);
            if ($id !== '') {
                $channels[] = new PrivateChannel('App.Models.User.'.$id);
            }
        }

        return $channels;
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        $report = $this->report;
        $booking = $report->muthowifBooking;

        return [
            'report_id' => (string) $report->getKey(),
            'booking_id' => $booking !== null ? (string) $booking->getKey() : null,
            'status' => $report->status->value,
            'action' => $this->action,
            'recruitment_open' => (bool) $report->recruitment_open,
        ];
    }
}
