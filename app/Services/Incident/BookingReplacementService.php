<?php

namespace App\Services\Incident;

use App\Enums\BookingIncidentOverlayStatus;
use App\Enums\BookingIncidentResolution;
use App\Enums\BookingIncidentStatus;
use App\Enums\BookingReplacementSource;
use App\Enums\BookingReplacementStatus;
use App\Enums\MuthowifServiceType;
use App\Events\CustomerBookingUpdated;
use App\Support\IncidentReplacementBroadcast;
use App\Models\BookingIncident;
use App\Models\BookingReplacement;
use App\Models\MuthowifBooking;
use App\Models\MuthowifProfile;
use App\Models\MuthowifServiceAddOn;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Alur pengganti multi-kandidat:
 * 1) Admin buka rekrutmen → muthowif melamar (siapa cepat, slot terbatas opsional)
 * 2) Admin setujui banyak kandidat
 * 3) Admin buka pemilihan jamaah → jamaah pilih satu dari daftar
 */
final class BookingReplacementService
{
    public function __construct(
        private readonly BookingReplacementCandidateService $candidates,
        private readonly BookingIncidentEventLogger $logger,
        private readonly BookingReplacementSettlementService $replacementSettlement,
        private readonly BookingReplacementRecruitmentService $recruitment,
    ) {}

    private function requiresAdminApproval(): bool
    {
        return (bool) config('incident.require_admin_approval_for_candidates', false);
    }

    private function statusAfterMuthowifAccepts(): BookingReplacementStatus
    {
        return $this->requiresAdminApproval()
            ? BookingReplacementStatus::PendingAdminApproval
            : BookingReplacementStatus::ApprovedForCustomer;
    }

    public function openRecruitment(BookingIncident $incident, User $admin): BookingIncident
    {
        if ($this->hasAcceptedReplacement($incident)) {
            throw new \RuntimeException(__('incidents.errors.replacement_already_accepted'));
        }

        return DB::transaction(function () use ($incident, $admin) {
            $incident->update([
                'replacement_recruitment_open' => true,
                'replacement_recruitment_opened_at' => now(),
                'status' => BookingIncidentStatus::AwaitingReplacement,
            ]);

            $this->logger->log($incident, 'replacement.recruitment_opened', 'admin', $admin->getKey(), []);

            $fresh = $incident->fresh();
            IncidentReplacementBroadcast::poolUpdated($fresh, 'recruitment_opened');

            return $fresh;
        });
    }

    public function muthowifVolunteer(BookingIncident $incident, MuthowifProfile $profile, ?string $note = null): BookingReplacement
    {
        if (! $incident->replacement_recruitment_open) {
            throw new \RuntimeException(__('incidents.errors.recruitment_closed'));
        }

        $booking = $incident->muthowifBooking;
        $this->candidates->assertCanReplace($booking, $profile);

        if ($this->hasAcceptedReplacement($incident)) {
            throw new \RuntimeException(__('incidents.errors.replacement_already_accepted'));
        }

        $existing = BookingReplacement::query()
            ->where('booking_incident_id', $incident->getKey())
            ->where('replacement_muthowif_profile_id', $profile->getKey())
            ->first();

        if ($existing && ! $existing->status->isTerminal()) {
            throw new \RuntimeException(__('incidents.errors.already_volunteered'));
        }

        if ($existing && $existing->status->isTerminal()) {
            throw new \RuntimeException(__('incidents.errors.cannot_reapply'));
        }

        $replacement = DB::transaction(function () use ($incident, $booking, $profile, $note) {
            $status = $this->statusAfterMuthowifAccepts();

            $replacement = BookingReplacement::query()->create([
                'booking_incident_id' => $incident->getKey(),
                'original_muthowif_profile_id' => $booking->muthowif_profile_id,
                'replacement_muthowif_profile_id' => $profile->getKey(),
                'status' => $status,
                'source' => BookingReplacementSource::Volunteer,
                'volunteered_at' => now(),
                'replacement_confirmed_at' => now(),
                'admin_approved_at' => $status === BookingReplacementStatus::ApprovedForCustomer ? now() : null,
                'admin_note' => $note,
            ]);

            $incident->update(['status' => BookingIncidentStatus::AwaitingReplacement]);

            $this->logger->log($incident, 'replacement.volunteered', 'muthowif', $profile->user_id, [
                'replacement_id' => $replacement->getKey(),
                'auto_approved' => ! $this->requiresAdminApproval(),
            ]);

            return $replacement;
        });

        if ($replacement->status === BookingReplacementStatus::ApprovedForCustomer) {
            $this->recruitment->afterMuthowifAcceptedOffer($incident->fresh(), $replacement);
        }

        return $replacement->fresh();
    }

    /** Muthowif menolak tawaran (tanpa melamar). */
    public function muthowifDeclineOpportunity(BookingIncident $incident, MuthowifProfile $profile, ?string $note = null): void
    {
        if (! $incident->replacement_recruitment_open) {
            throw new \RuntimeException(__('incidents.errors.recruitment_closed'));
        }

        $existing = BookingReplacement::query()
            ->where('booking_incident_id', $incident->getKey())
            ->where('replacement_muthowif_profile_id', $profile->getKey())
            ->first();

        if ($existing !== null) {
            if ($existing->status === BookingReplacementStatus::DeclinedByMuthowif) {
                return;
            }
            if (! $existing->status->isTerminal()) {
                throw new \RuntimeException(__('incidents.errors.already_in_queue'));
            }
            throw new \RuntimeException(__('incidents.errors.cannot_reapply'));
        }

        DB::transaction(function () use ($incident, $profile, $note) {
            $booking = $incident->muthowifBooking;

            BookingReplacement::query()->create([
                'booking_incident_id' => $incident->getKey(),
                'original_muthowif_profile_id' => $booking->muthowif_profile_id,
                'replacement_muthowif_profile_id' => $profile->getKey(),
                'status' => BookingReplacementStatus::DeclinedByMuthowif,
                'source' => BookingReplacementSource::Volunteer,
                'replacement_decline_note' => $note,
            ]);

            $this->logger->log($incident, 'replacement.opportunity_declined', 'muthowif', $profile->user_id, [
                'note' => $note,
            ]);
        });
    }

    /** Admin mengundang muthowif — harus konfirmasi dulu, lalu masuk antrian persetujuan admin. */
    public function adminInvite(
        BookingIncident $incident,
        MuthowifProfile $target,
        User $admin,
        ?string $adminNote = null,
    ): BookingReplacement {
        $booking = $incident->muthowifBooking;
        $this->candidates->assertCanReplace($booking, $target);

        if ($this->hasAcceptedReplacement($incident)) {
            throw new \RuntimeException(__('incidents.errors.replacement_already_accepted'));
        }

        $existing = BookingReplacement::query()
            ->where('booking_incident_id', $incident->getKey())
            ->where('replacement_muthowif_profile_id', $target->getKey())
            ->whereNotIn('status', array_map(fn ($s) => $s->value, [
                BookingReplacementStatus::RejectedByAdmin,
                BookingReplacementStatus::DeclinedByMuthowif,
                BookingReplacementStatus::Cancelled,
            ]))
            ->first();

        if ($existing) {
            throw new \RuntimeException(__('incidents.errors.already_in_queue'));
        }

        return DB::transaction(function () use ($incident, $booking, $target, $admin, $adminNote) {
            $replacement = BookingReplacement::query()->create([
                'booking_incident_id' => $incident->getKey(),
                'original_muthowif_profile_id' => $booking->muthowif_profile_id,
                'replacement_muthowif_profile_id' => $target->getKey(),
                'status' => BookingReplacementStatus::AwaitingMuthowifConfirm,
                'source' => BookingReplacementSource::AdminInvite,
                'proposed_by_admin_id' => $admin->getKey(),
                'admin_note' => $adminNote,
            ]);

            if (! $incident->replacement_recruitment_open) {
                $incident->update([
                    'replacement_recruitment_open' => true,
                    'replacement_recruitment_opened_at' => $incident->replacement_recruitment_opened_at ?? now(),
                    'status' => BookingIncidentStatus::AwaitingReplacement,
                ]);
            }

            $this->logger->log($incident, 'replacement.invited', 'admin', $admin->getKey(), [
                'replacement_id' => $replacement->getKey(),
            ]);

            return $replacement;
        });
    }

    public function replacementMuthowifConfirm(BookingReplacement $replacement, User $muthowifUser): BookingReplacement
    {
        $profile = $muthowifUser->muthowifProfile;
        abort_unless($profile, 403);

        if ((string) $replacement->replacement_muthowif_profile_id !== (string) $profile->getKey()) {
            throw new \RuntimeException(__('incidents.errors.not_replacement_muthowif'));
        }

        if ($replacement->status !== BookingReplacementStatus::AwaitingMuthowifConfirm) {
            throw new \RuntimeException(__('incidents.errors.replacement_wrong_status'));
        }

        $incident = $replacement->incident;
        $booking = $incident->muthowifBooking;

        $replacement = DB::transaction(function () use ($replacement, $muthowifUser) {
            $status = $this->statusAfterMuthowifAccepts();

            $replacement->update([
                'status' => $status,
                'replacement_confirmed_at' => now(),
                'admin_approved_at' => $status === BookingReplacementStatus::ApprovedForCustomer ? now() : null,
            ]);

            $this->logger->log($replacement->incident, 'replacement.muthowif_confirmed', 'muthowif', $muthowifUser->getKey(), [
                'replacement_id' => $replacement->getKey(),
            ]);

            return $replacement->fresh();
        });

        if ($replacement->status === BookingReplacementStatus::ApprovedForCustomer) {
            $this->recruitment->afterMuthowifAcceptedOffer($incident->fresh(), $replacement);
        }

        return $replacement;
    }

    public function replacementMuthowifDecline(BookingReplacement $replacement, User $muthowifUser, ?string $note = null): BookingReplacement
    {
        $profile = $muthowifUser->muthowifProfile;
        abort_unless($profile, 403);

        if ((string) $replacement->replacement_muthowif_profile_id !== (string) $profile->getKey()) {
            throw new \RuntimeException(__('incidents.errors.not_replacement_muthowif'));
        }

        return DB::transaction(function () use ($replacement, $muthowifUser, $note) {
            $replacement->update([
                'status' => BookingReplacementStatus::DeclinedByMuthowif,
                'replacement_decline_note' => $note,
            ]);

            $this->logger->log($replacement->incident, 'replacement.muthowif_declined', 'muthowif', $muthowifUser->getKey(), [
                'note' => $note,
            ]);

            return $replacement->fresh();
        });
    }

    public function adminApproveCandidate(BookingReplacement $replacement, User $admin): BookingReplacement
    {
        if ($replacement->status !== BookingReplacementStatus::PendingAdminApproval) {
            throw new \RuntimeException(__('incidents.errors.replacement_not_pending_approval'));
        }

        $replacement = DB::transaction(function () use ($replacement, $admin) {
            $replacement->update([
                'status' => BookingReplacementStatus::ApprovedForCustomer,
                'approved_by_admin_id' => $admin->getKey(),
                'admin_approved_at' => now(),
            ]);

            $this->logger->log($replacement->incident, 'replacement.admin_approved', 'admin', $admin->getKey(), [
                'replacement_id' => $replacement->getKey(),
            ]);

            return $replacement->fresh();
        });

        $incident = $replacement->incident->fresh();
        $this->recruitment->afterMuthowifAcceptedOffer($incident, $replacement);

        return $replacement;
    }

    public function adminRejectCandidate(BookingReplacement $replacement, User $admin, ?string $note = null): BookingReplacement
    {
        if (! in_array($replacement->status, [
            BookingReplacementStatus::PendingAdminApproval,
            BookingReplacementStatus::AwaitingMuthowifConfirm,
        ], true)) {
            throw new \RuntimeException(__('incidents.errors.replacement_wrong_status'));
        }

        return DB::transaction(function () use ($replacement, $admin, $note) {
            $replacement->update([
                'status' => BookingReplacementStatus::RejectedByAdmin,
                'replacement_decline_note' => $note,
                'approved_by_admin_id' => $admin->getKey(),
            ]);

            $this->logger->log($replacement->incident, 'replacement.admin_rejected', 'admin', $admin->getKey(), [
                'note' => $note,
            ]);

            return $replacement->fresh();
        });
    }

    /** Buka fase pemilihan jamaah — semua kandidat disetujui tampil di UI jamaah. */
    public function openCustomerChoice(BookingIncident $incident, User $admin): BookingIncident
    {
        $approvedCount = $incident->replacements()
            ->whereIn('status', array_map(fn ($s) => $s->value, BookingReplacementStatus::customerSelectable()))
            ->count();

        if ($approvedCount < 1) {
            throw new \RuntimeException(__('incidents.errors.no_approved_candidates'));
        }

        return DB::transaction(function () use ($incident, $admin, $approvedCount) {
            $incident->update([
                'customer_choice_opened_at' => now(),
                'status' => BookingIncidentStatus::AwaitingCustomer,
            ]);

            $this->logger->log($incident, 'replacement.customer_choice_opened', 'admin', $admin->getKey(), [
                'approved_count' => $approvedCount,
            ]);

            return $incident->fresh();
        });
    }

    public function customerSelect(BookingReplacement $replacement, User $customer): MuthowifBooking
    {
        $incident = $replacement->incident;
        $booking = $incident->muthowifBooking;

        if ((string) $booking->customer_id !== (string) $customer->getKey()) {
            throw new \RuntimeException(__('incidents.errors.not_booking_owner'));
        }

        if ($incident->customer_choice_opened_at === null) {
            throw new \RuntimeException(__('incidents.errors.customer_choice_not_open'));
        }

        if (! in_array($replacement->status, BookingReplacementStatus::customerSelectable(), true)) {
            throw new \RuntimeException(__('incidents.errors.replacement_not_selectable'));
        }

        return DB::transaction(function () use ($replacement, $customer, $booking, $incident) {
            $replacement->update([
                'status' => BookingReplacementStatus::AcceptedByCustomer,
                'customer_accepted_at' => now(),
            ]);

            $incident->replacements()
                ->whereKeyNot($replacement->getKey())
                ->whereIn('status', array_map(
                    fn (BookingReplacementStatus $s) => $s->value,
                    BookingReplacementStatus::customerSelectable()
                ))
                ->update(['status' => BookingReplacementStatus::NotSelected->value]);

            $target = MuthowifProfile::query()
                ->with(['services.addOns'])
                ->findOrFail($replacement->replacement_muthowif_profile_id);

            $this->applyProfileTransfer($booking, $target);

            $incident->update([
                'status' => BookingIncidentStatus::Resolved,
                'resolution_type' => BookingIncidentResolution::ReplacementCompleted,
                'resolved_at' => now(),
                'replacement_recruitment_open' => false,
            ]);

            $booking->update(['incident_status' => BookingIncidentOverlayStatus::Resolved]);

            $this->logger->log($incident, 'replacement.selected', 'customer', $customer->getKey(), [
                'replacement_id' => $replacement->getKey(),
            ]);

            $this->replacementSettlement->afterReplacementAccepted($replacement);

            $incident->update(['replacement_recruitment_open' => false]);

            IncidentReplacementBroadcast::poolUpdated($incident->fresh(), 'customer_selected');
            broadcast(new CustomerBookingUpdated($booking->fresh()));

            return $booking->fresh();
        });
    }

    public function approvedCandidatesCount(BookingIncident $incident): int
    {
        return $incident->replacements()
            ->whereIn('status', array_map(
                fn (BookingReplacementStatus $s) => $s->value,
                BookingReplacementStatus::customerSelectable()
            ))
            ->count();
    }

    /** @deprecated Gunakan customerSelect */
    public function customerAccept(BookingReplacement $replacement, User $customer): MuthowifBooking
    {
        return $this->customerSelect($replacement, $customer);
    }

    public function hasAcceptedReplacement(BookingIncident $incident): bool
    {
        return $incident->replacements()
            ->where('status', BookingReplacementStatus::AcceptedByCustomer)
            ->exists();
    }

    private function applyProfileTransfer(MuthowifBooking $booking, MuthowifProfile $target): void
    {
        $booking->loadMissing(['muthowifProfile.services.addOns']);
        $target->load(['services.addOns']);

        $newService = $target->services->firstWhere('type', $booking->service_type);
        $matchedIds = $this->matchAddOnIds($booking, $newService);

        $booking->forceFill([
            'muthowif_profile_id' => $target->getKey(),
            'selected_add_on_ids' => $matchedIds,
        ])->save();
    }

    /**
     * @return list<string>
     */
    private function matchAddOnIds(MuthowifBooking $booking, $newService): array
    {
        if ($booking->service_type !== MuthowifServiceType::PrivateJamaah || $newService === null) {
            return is_array($booking->selected_add_on_ids) ? $booking->selected_add_on_ids : [];
        }

        $names = [];
        $snapshot = $booking->add_ons_snapshot;
        if (is_array($snapshot)) {
            foreach ($snapshot as $row) {
                if (is_array($row) && filled($row['name'] ?? null)) {
                    $names[] = trim((string) $row['name']);
                }
            }
        }

        $ids = [];
        foreach ($names as $name) {
            $match = $newService->addOns->first(
                fn (MuthowifServiceAddOn $a) => strcasecmp(trim((string) $a->name), $name) === 0
            );
            if ($match !== null) {
                $ids[] = (string) $match->getKey();
            }
        }

        return array_values(array_unique($ids));
    }
}
