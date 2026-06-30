<?php

namespace App\Support;

use App\Enums\EmergencyReportCaseType;
use App\Models\BookingReplacementOffer;
use App\Models\MuthowifBooking;

final class ApiEmergencyDetail
{
    /**
     * @return array<string, mixed>
     */
    public static function for(MuthowifBooking $booking): array
    {
        $view = EmergencyBookingViewData::for($booking);
        $report = $view['activeEmergencyReport'];

        return [
            'report' => $report ? [
                'id' => $report->id,
                'case_type' => $report->case_type->value,
                'case_type_label' => $report->case_type->label(),
                'status' => $report->status->value,
                'status_label' => $report->status->label(),
                'description' => $report->description,
                'created_at' => $report->created_at?->toIso8601String(),
            ] : null,
            'replacement_offers' => $view['selectableEmergencyOffers']
                ->map(fn (BookingReplacementOffer $offer) => self::formatOffer($offer))
                ->values()
                ->all(),
            'has_replacement' => $booking->emergency_replacement_at !== null,
            'replacement_at' => $booking->emergency_replacement_at?->toIso8601String(),
            'case_types' => self::caseTypes(),
        ];
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function caseTypes(): array
    {
        return array_map(
            static fn (EmergencyReportCaseType $case) => [
                'value' => $case->value,
                'label' => $case->label(),
            ],
            EmergencyReportCaseType::cases(),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private static function formatOffer(BookingReplacementOffer $offer): array
    {
        $profile = $offer->muthowifProfile;
        $user = $profile?->user;

        return [
            'id' => $offer->id,
            'responded_at' => $offer->responded_at?->toIso8601String(),
            'muthowif' => [
                'id' => $profile?->id,
                'name' => $user?->name ?? 'Muthowif',
                'avatar' => $profile?->photoUrl(),
                'rating' => $profile?->average_rating !== null
                    ? number_format((float) $profile->average_rating, 1)
                    : null,
                'reviews_count' => (int) ($profile?->booking_reviews_count ?? 0),
                'confirmed_bookings' => (int) ($profile?->confirmed_bookings_count ?? 0),
                'languages' => $profile?->languagesForDisplay() ?? [],
            ],
        ];
    }
}
