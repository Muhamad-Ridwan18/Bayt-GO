<?php

namespace App\Services\Incident;

use App\Models\BookingReplacement;
use App\Models\BookingSettlement;

/**
 * Koordinasi settlement setelah replacement diterima jamaah.
 */
final class BookingReplacementSettlementService
{
    public function afterReplacementAccepted(BookingReplacement $replacement): ?BookingSettlement
    {
        $incident = $replacement->incident()->with('muthowifBooking')->first();
        if ($incident === null) {
            return null;
        }

        return app(IncidentCompensationService::class)->proposeSettlement($incident);
    }
}
