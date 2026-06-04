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

        $report->load([
            'muthowifBooking.muthowifProfile.user',
            'offers' => static function ($query): void {
                $query
                    ->where('status', ReplacementOfferStatus::Accepted->value)
                    ->orderByDesc('responded_at')
                    ->with([
                        'muthowifProfile' => static fn ($profileQuery) => $profileQuery
                            ->withMarketplaceStats()
                            ->with(['user', 'services']),
                    ]);
            },
        ]);

        $selectable = $report->offers->values();

        return [
            'activeEmergencyReport' => $report,
            'selectableEmergencyOffers' => $selectable,
        ];
    }
}
