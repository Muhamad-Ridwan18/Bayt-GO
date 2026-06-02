<?php

namespace App\Services\Incident;

use App\Enums\BookingIncidentCaseType;
use App\Enums\BookingIncidentStatus;
use App\Enums\BookingReplacementStatus;
use App\Jobs\BroadcastReplacementOpportunityToMuthowifsJob;
use App\Jobs\NotifyCustomerOfReplacementPoolJob;
use App\Models\BookingIncident;
use App\Models\BookingReplacement;
use App\Support\IncidentReplacementBroadcast;
use Illuminate\Support\Facades\DB;

/**
 * Rekrutmen pengganti otomatis — tanpa aksi admin (admin hanya memantau).
 */
final class BookingReplacementRecruitmentService
{
    public function __construct(
        private readonly BookingReplacementCandidateService $candidates,
        private readonly BookingIncidentEventLogger $logger,
    ) {}

    public function shouldAutoStartForCase(BookingIncidentCaseType $caseType): bool
    {
        if (! config('incident.auto_start_replacement_recruitment', true)) {
            return false;
        }

        return in_array($caseType, [
            BookingIncidentCaseType::MuthowifUnavailable,
            BookingIncidentCaseType::LostContactInService,
            BookingIncidentCaseType::AbandonedService,
            BookingIncidentCaseType::NoShow,
        ], true);
    }

    public function start(BookingIncident $incident): BookingIncident
    {
        if ($incident->replacement_recruitment_open) {
            return $incident;
        }

        return DB::transaction(function () use ($incident) {
            $incident->refresh()->lockForUpdate();

            if ($incident->replacement_recruitment_open) {
                return $incident;
            }

            $incident->update([
                'replacement_recruitment_open' => true,
                'replacement_recruitment_opened_at' => now(),
                'status' => BookingIncidentStatus::AwaitingReplacement,
            ]);

            $this->logger->log($incident, 'replacement.recruitment_opened', 'system', null, [
                'auto' => true,
            ]);

            if (config('services.fonnte.incident_replacement_opportunity_notify_enabled', true)) {
                BroadcastReplacementOpportunityToMuthowifsJob::dispatchAfterResponse((string) $incident->getKey());
            }

            $fresh = $incident->fresh();
            IncidentReplacementBroadcast::poolUpdated($fresh, 'recruitment_opened');

            return $fresh;
        });
    }

    /** Setelah muthowif menerima tawaran — buka pemilihan jamaah & beri tahu jamaah. */
    public function afterMuthowifAcceptedOffer(BookingIncident $incident, BookingReplacement $replacement): void
    {
        $incident->refresh();

        $approvedCount = $incident->replacements()
            ->whereIn('status', array_map(
                fn (BookingReplacementStatus $s) => $s->value,
                BookingReplacementStatus::customerSelectable()
            ))
            ->count();

        $min = max(1, (int) config('incident.min_candidates_to_open_customer_choice', 1));

        if ($approvedCount < $min) {
            return;
        }

        $firstOpen = $incident->customer_choice_opened_at === null;

        if ($firstOpen) {
            $incident->update([
                'customer_choice_opened_at' => now(),
                'status' => BookingIncidentStatus::AwaitingCustomer,
            ]);

            $this->logger->log($incident, 'replacement.customer_choice_opened', 'system', null, [
                'auto' => true,
                'approved_count' => $approvedCount,
            ]);
        }

        $incident = $incident->fresh();
        IncidentReplacementBroadcast::poolUpdated($incident, $firstOpen ? 'customer_choice_opened' : 'candidate_added');

        $customerWaEnabled = config('services.fonnte.incident_customer_replacement_pool_notify_enabled', true);
        if ($customerWaEnabled && ($firstOpen || config('incident.notify_customer_on_each_new_candidate', false))) {
            NotifyCustomerOfReplacementPoolJob::dispatchAfterResponse((string) $incident->getKey());
        }
    }

    /**
     * @return \Illuminate\Support\Collection<int, \App\Models\MuthowifProfile>
     */
    public function eligibleMuthowifProfiles(BookingIncident $incident): \Illuminate\Support\Collection
    {
        $booking = $incident->muthowifBooking;
        if ($booking === null) {
            return collect();
        }

        $start = $booking->starts_on->copy()->startOfDay();
        $end = $booking->ends_on->copy()->startOfDay();

        return $this->candidates
            ->listCandidates($booking, $booking->muthowifProfile)
            ->filter(fn ($profile) => $profile->isJadwalAvailableForRange($start, $end, (string) $booking->getKey()))
            ->values();
    }
}
