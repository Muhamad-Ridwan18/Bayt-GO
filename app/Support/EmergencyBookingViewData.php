<?php

namespace App\Support;

use App\Enums\ReplacementOfferStatus;
use App\Models\BookingEmergencyReport;
use App\Models\MuthowifBooking;
use Illuminate\Support\Collection;

final class EmergencyBookingViewData
{
    /**
     * @return array{
     *   activeEmergencyReport: BookingEmergencyReport|null,
     *   selectableEmergencyOffers: Collection<int, \App\Models\BookingReplacementOffer>
     * }
     */
    public static function for(MuthowifBooking $booking): array
    {
        $report = $booking->activeEmergencyReport();

        if ($report === null) {
            return [
                'activeEmergencyReport' => null,
                'selectableEmergencyOffers' => collect(),
            ];
        }

        $report->load(['offers.muthowifProfile.user']);

        $selectable = $report->offers
            ->filter(fn ($o) => $o->status === ReplacementOfferStatus::Accepted)
            ->values();

        return [
            'activeEmergencyReport' => $report,
            'selectableEmergencyOffers' => $selectable,
        ];
    }
}
