<?php

namespace App\Services\Incident;

use App\Enums\BookingIncidentStatus;
use App\Enums\BookingReplacementStatus;
use App\Models\BookingIncident;
use App\Models\BookingReplacement;
use App\Models\MuthowifProfile;
use App\Models\MuthowifServiceAddOn;
use Illuminate\Support\Collection;

final class MuthowifReplacementInboxService
{
    public function __construct(
        private readonly BookingReplacementCandidateService $candidates,
    ) {}

    /**
     * @return Collection<int, BookingIncident>
     */
    public function openRecruitmentIncidents(MuthowifProfile $profile): Collection
    {
        return BookingIncident::query()
            ->with(['muthowifBooking.customer', 'muthowifBooking.muthowifProfile.user', 'muthowifBooking.muthowifProfile.services.addOns'])
            ->where('replacement_recruitment_open', true)
            ->whereNotIn('status', [
                BookingIncidentStatus::Resolved->value,
                BookingIncidentStatus::Cancelled->value,
            ])
            ->orderByDesc('replacement_recruitment_opened_at')
            ->limit(50)
            ->get()
            ->filter(function (BookingIncident $incident) use ($profile) {
                $booking = $incident->muthowifBooking;
                if ($booking === null) {
                    return false;
                }

                if ((string) $booking->muthowif_profile_id === (string) $profile->getKey()) {
                    return false;
                }

                try {
                    $this->candidates->assertCanReplace($booking, $profile);

                    return true;
                } catch (\RuntimeException) {
                    return false;
                }
            })
            ->filter(function (BookingIncident $incident) use ($profile) {
                return ! BookingReplacement::query()
                    ->where('booking_incident_id', $incident->getKey())
                    ->where('replacement_muthowif_profile_id', $profile->getKey())
                    ->whereNotIn('status', [
                        BookingReplacementStatus::RejectedByAdmin->value,
                        BookingReplacementStatus::DeclinedByMuthowif->value,
                        BookingReplacementStatus::Cancelled->value,
                        BookingReplacementStatus::NotSelected->value,
                    ])
                    ->exists();
            })
            ->values();
    }

    public function awaitingInviteCount(MuthowifProfile $profile): int
    {
        return BookingReplacement::query()
            ->where('replacement_muthowif_profile_id', $profile->getKey())
            ->where('status', BookingReplacementStatus::AwaitingMuthowifConfirm)
            ->whereColumn('replacement_muthowif_profile_id', '!=', 'original_muthowif_profile_id')
            ->count();
    }

    public function pendingActionCount(MuthowifProfile $profile): int
    {
        return $this->awaitingInviteCount($profile) + $this->openRecruitmentIncidents($profile)->count();
    }

    /**
     * @return Collection<string, MuthowifServiceAddOn>
     */
    public function addonsByIdForIncidents(Collection $incidents): Collection
    {
        $bookings = $incidents->map(fn (BookingIncident $i) => $i->muthowifBooking)->filter();
        $addonIds = $bookings->flatMap(fn ($b) => $b->selected_add_on_ids ?? [])->unique()->filter()->values();

        if ($addonIds->isEmpty()) {
            return collect();
        }

        return MuthowifServiceAddOn::query()->whereIn('id', $addonIds)->get()->keyBy('id');
    }
}
