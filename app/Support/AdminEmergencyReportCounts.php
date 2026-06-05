<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\EmergencyReportStatus;
use App\Models\BookingEmergencyReport;

final class AdminEmergencyReportCounts
{
    public static function openCount(): int
    {
        return BookingEmergencyReport::query()
            ->where('status', EmergencyReportStatus::Submitted->value)
            ->count();
    }
}
