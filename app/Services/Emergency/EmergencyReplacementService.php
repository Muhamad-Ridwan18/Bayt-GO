<?php

namespace App\Services\Emergency;

use App\Enums\BookingStatus;
use App\Enums\EmergencyOverlayStatus;
use App\Enums\EmergencyReportCaseType;
use App\Enums\EmergencyReportStatus;
use App\Enums\MuthowifAccountStatus;
use App\Enums\ReplacementOfferStatus;
use App\Models\BookingEmergencyReport;
use App\Models\BookingReplacementLog;
use App\Models\BookingReplacementOffer;
use App\Models\MuthowifBooking;
use App\Models\MuthowifProfile;
use App\Models\User;
use App\Services\UploadedImageOptimizer;
use App\Support\CustomerBookingBroadcast;
use App\Support\EmergencyReportBroadcast;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

final class EmergencyReplacementService
{
    public function __construct(
        private readonly EmergencyReplacementCandidateService $candidates,
    ) {}

    public function submitReport(
        MuthowifBooking $booking,
        User $customer,
        EmergencyReportCaseType $caseType,
        ?string $description,
        array $evidenceFiles = [],
    ): BookingEmergencyReport {
        if ($booking->status !== BookingStatus::Confirmed || ! $booking->isPaid()) {
            throw new \RuntimeException(__('emergency.errors.booking_not_eligible'));
        }

        if ($booking->activeEmergencyReport() !== null) {
            throw new \RuntimeException(__('emergency.errors.report_already_open'));
        }

        $paths = $this->storeEvidence($booking, $evidenceFiles);

        return DB::transaction(function () use ($booking, $customer, $caseType, $description, $paths) {
            $report = BookingEmergencyReport::query()->create([
                'muthowif_booking_id' => $booking->getKey(),
                'reported_by_user_id' => $customer->getKey(),
                'case_type' => $caseType,
                'description' => filled($description) ? trim($description) : null,
                'evidence_paths' => $paths !== [] ? $paths : null,
                'status' => EmergencyReportStatus::Submitted,
            ]);

            $booking->update(['emergency_overlay_status' => EmergencyOverlayStatus::Reported]);

            $report = $report->fresh();
            CustomerBookingBroadcast::afterResponse($booking->fresh());
            EmergencyReportBroadcast::afterResponse($report, 'submitted');

            return $report;
        });
    }

    public function markUnderReview(BookingEmergencyReport $report, User $admin): BookingEmergencyReport
    {
        if ($report->status !== EmergencyReportStatus::Submitted) {
            throw new \RuntimeException(__('emergency.errors.invalid_report_status'));
        }

        $report->update(['status' => EmergencyReportStatus::UnderReview]);
        $report = $report->fresh();
        EmergencyReportBroadcast::afterResponse($report, 'under_review');

        return $report;
    }

    public function verifyAndStartRecruitment(BookingEmergencyReport $report, User $admin, ?string $adminNote = null): BookingEmergencyReport
    {
        if (! in_array($report->status, [EmergencyReportStatus::Submitted, EmergencyReportStatus::UnderReview], true)) {
            throw new \RuntimeException(__('emergency.errors.invalid_report_status'));
        }

        return DB::transaction(function () use ($report, $admin, $adminNote) {
            $report->update([
                'status' => EmergencyReportStatus::Verified,
                'verified_by_admin_id' => $admin->getKey(),
                'verified_at' => now(),
                'admin_note' => filled($adminNote) ? trim($adminNote) : null,
                'recruitment_open' => true,
            ]);

            $booking = $report->muthowifBooking;
            $booking->update(['emergency_overlay_status' => EmergencyOverlayStatus::ReplacementActive]);

            $report = $report->fresh();
            $this->broadcastNextBatch($report);

            CustomerBookingBroadcast::afterResponse($booking->fresh());
            EmergencyReportBroadcast::afterResponse($report->fresh(), 'verified');

            return $report->fresh();
        });
    }

    public function rejectReport(BookingEmergencyReport $report, User $admin, ?string $adminNote = null): BookingEmergencyReport
    {
        if (! in_array($report->status, [EmergencyReportStatus::Submitted, EmergencyReportStatus::UnderReview], true)) {
            throw new \RuntimeException(__('emergency.errors.invalid_report_status'));
        }

        return DB::transaction(function () use ($report, $admin, $adminNote) {
            $report->update([
                'status' => EmergencyReportStatus::Rejected,
                'verified_by_admin_id' => $admin->getKey(),
                'verified_at' => now(),
                'admin_note' => filled($adminNote) ? trim($adminNote) : null,
                'recruitment_open' => false,
            ]);

            $report->muthowifBooking->update([
                'emergency_overlay_status' => EmergencyOverlayStatus::None,
            ]);

            CustomerBookingBroadcast::afterResponse($report->muthowifBooking->fresh());
            EmergencyReportBroadcast::afterResponse($report->fresh(), 'rejected');

            return $report->fresh();
        });
    }

    public function broadcastNextBatch(BookingEmergencyReport $report): int
    {
        if (! $report->recruitment_open || $report->status !== EmergencyReportStatus::Verified) {
            throw new \RuntimeException(__('emergency.errors.recruitment_closed'));
        }

        $booking = $report->muthowifBooking;
        if ($booking->emergency_replacement_at !== null) {
            throw new \RuntimeException(__('emergency.errors.replacement_already_done'));
        }

        $batchSize = max(1, (int) config('emergency.broadcast_batch_size', 5));
        $alreadyOffered = $report->offers()->pluck('muthowif_profile_id')->all();
        $exclude = array_merge(
            $alreadyOffered,
            [(string) $booking->muthowif_profile_id],
            $booking->original_muthowif_profile_id ? [(string) $booking->original_muthowif_profile_id] : [],
        );

        $candidates = $this->candidates
            ->listEligible($booking, excludeProfileIds: $exclude)
            ->take($batchSize);

        if ($candidates->isEmpty()) {
            return 0;
        }

        $batchNumber = (int) $report->replacement_batch_number + 1;

        DB::transaction(function () use ($report, $candidates, $batchNumber) {
            foreach ($candidates as $profile) {
                BookingReplacementOffer::query()->create([
                    'booking_emergency_report_id' => $report->getKey(),
                    'muthowif_profile_id' => $profile->getKey(),
                    'batch_number' => $batchNumber,
                    'source' => 'broadcast',
                    'status' => ReplacementOfferStatus::Offered,
                    'offered_at' => now(),
                ]);
            }

            $report->update(['replacement_batch_number' => $batchNumber]);
        });

        $notifyUserIds = $candidates
            ->pluck('user_id')
            ->map(static fn ($id) => (string) $id)
            ->all();

        EmergencyReportBroadcast::afterResponse($report->fresh(), 'batch_offered', $notifyUserIds);

        return $candidates->count();
    }

    public function adminInvite(
        BookingEmergencyReport $report,
        MuthowifProfile $target,
        User $admin,
    ): BookingReplacementOffer {
        if (! $report->recruitment_open) {
            throw new \RuntimeException(__('emergency.errors.recruitment_closed'));
        }

        $booking = $report->muthowifBooking;
        $this->candidates->assertCanReplace($booking, $target);

        $existing = $report->offers()
            ->where('muthowif_profile_id', $target->getKey())
            ->first();

        if ($existing !== null) {
            if (in_array($existing->status, [
                ReplacementOfferStatus::Offered,
                ReplacementOfferStatus::Accepted,
                ReplacementOfferStatus::Selected,
            ], true)) {
                throw new \RuntimeException(__('emergency.errors.already_offered'));
            }

            $existing->update([
                'source' => 'admin_invite',
                'status' => ReplacementOfferStatus::Offered,
                'offered_at' => now(),
                'responded_at' => null,
                'decline_note' => null,
            ]);

            EmergencyReportBroadcast::afterResponse(
                $report->fresh(),
                'admin_invite',
                [(string) $target->user_id],
            );

            return $existing->fresh();
        }

        $offer = BookingReplacementOffer::query()->create([
            'booking_emergency_report_id' => $report->getKey(),
            'muthowif_profile_id' => $target->getKey(),
            'batch_number' => max(1, (int) $report->replacement_batch_number),
            'source' => 'admin_invite',
            'status' => ReplacementOfferStatus::Offered,
            'offered_at' => now(),
        ]);

        EmergencyReportBroadcast::afterResponse(
            $report->fresh(),
            'admin_invite',
            [(string) $target->user_id],
        );

        return $offer;
    }

    public function muthowifAccept(BookingReplacementOffer $offer, User $muthowifUser): BookingReplacementOffer
    {
        $profile = $muthowifUser->muthowifProfile;
        abort_unless($profile, 403);

        if ((string) $offer->muthowif_profile_id !== (string) $profile->getKey()) {
            throw new \RuntimeException(__('emergency.errors.not_offer_owner'));
        }

        if ($offer->status !== ReplacementOfferStatus::Offered) {
            throw new \RuntimeException(__('emergency.errors.offer_not_pending'));
        }

        $report = $offer->report;
        if (! $report->recruitment_open || $report->muthowifBooking->emergency_replacement_at !== null) {
            throw new \RuntimeException(__('emergency.errors.recruitment_closed'));
        }

        $offer = DB::transaction(function () use ($offer) {
            $offer->update([
                'status' => ReplacementOfferStatus::Accepted,
                'responded_at' => now(),
            ]);

            return $offer->fresh();
        });

        CustomerBookingBroadcast::afterResponse($report->muthowifBooking->fresh());
        EmergencyReportBroadcast::afterResponse($report->fresh(), 'offer_accepted');

        return $offer;
    }

    public function muthowifDecline(BookingReplacementOffer $offer, User $muthowifUser, ?string $note = null): BookingReplacementOffer
    {
        $profile = $muthowifUser->muthowifProfile;
        abort_unless($profile, 403);

        if ((string) $offer->muthowif_profile_id !== (string) $profile->getKey()) {
            throw new \RuntimeException(__('emergency.errors.not_offer_owner'));
        }

        if ($offer->status !== ReplacementOfferStatus::Offered) {
            throw new \RuntimeException(__('emergency.errors.offer_not_pending'));
        }

        $offer->update([
            'status' => ReplacementOfferStatus::Declined,
            'responded_at' => now(),
            'decline_note' => filled($note) ? trim($note) : null,
        ]);

        $report = $offer->report->fresh();
        $this->maybeAutoNextBatch($report);
        EmergencyReportBroadcast::afterResponse($report, 'offer_declined');

        return $offer->fresh();
    }

    public function customerSelect(BookingReplacementOffer $offer, User $customer): MuthowifBooking
    {
        $report = $offer->report;
        $booking = $report->muthowifBooking;

        if ((string) $booking->customer_id !== (string) $customer->getKey()) {
            throw new \RuntimeException(__('emergency.errors.not_booking_owner'));
        }

        if ($offer->status !== ReplacementOfferStatus::Accepted) {
            throw new \RuntimeException(__('emergency.errors.offer_not_selectable'));
        }

        if ($booking->emergency_replacement_at !== null) {
            throw new \RuntimeException(__('emergency.errors.replacement_already_done'));
        }

        return DB::transaction(function () use ($offer, $customer, $report, $booking) {
            $fromProfileId = (string) $booking->muthowif_profile_id;
            $target = MuthowifProfile::query()->findOrFail($offer->muthowif_profile_id);

            $this->candidates->assertCanReplace($booking, $target);

            $offer->update(['status' => ReplacementOfferStatus::Selected]);

            $report->offers()
                ->whereKeyNot($offer->getKey())
                ->where('status', ReplacementOfferStatus::Accepted->value)
                ->update(['status' => ReplacementOfferStatus::Superseded->value]);

            $report->offers()
                ->where('status', ReplacementOfferStatus::Offered->value)
                ->update(['status' => ReplacementOfferStatus::Expired->value]);

            $originalId = $booking->original_muthowif_profile_id ?? $fromProfileId;

            $booking->update([
                'original_muthowif_profile_id' => $originalId,
                'muthowif_profile_id' => $target->getKey(),
                'emergency_replacement_at' => now(),
                'emergency_overlay_status' => EmergencyOverlayStatus::Resolved,
            ]);

            MuthowifProfile::query()
                ->whereKey($fromProfileId)
                ->update(['account_status' => MuthowifAccountStatus::Terminated->value]);

            $settlementPreview = null;
            $payment = $booking->settledBookingPayment();
            if ($payment !== null) {
                $settlementPreview = app(EmergencySettlementCalculator::class)
                    ->replacementPayoutOnCompletion($booking->fresh(), $payment);
            }

            BookingReplacementLog::query()->create([
                'muthowif_booking_id' => $booking->getKey(),
                'booking_emergency_report_id' => $report->getKey(),
                'from_muthowif_profile_id' => $fromProfileId,
                'to_muthowif_profile_id' => $target->getKey(),
                'chosen_by' => 'customer',
                'chosen_by_user_id' => $customer->getKey(),
                'metadata' => [
                    'offer_id' => $offer->getKey(),
                    'settlement_preview' => $settlementPreview,
                ],
            ]);

            $report->update([
                'status' => EmergencyReportStatus::Resolved,
                'recruitment_open' => false,
            ]);

            CustomerBookingBroadcast::afterResponse($booking->fresh());
            EmergencyReportBroadcast::afterResponse($report->fresh(), 'replacement_selected');

            return $booking->fresh();
        });
    }

    public function maybeAutoNextBatch(BookingEmergencyReport $report): void
    {
        if (! $report->recruitment_open || $report->status !== EmergencyReportStatus::Verified) {
            return;
        }

        if ($report->muthowifBooking->emergency_replacement_at !== null) {
            return;
        }

        $pendingOffers = $report->offers()->where('status', ReplacementOfferStatus::Offered->value)->exists();
        if ($pendingOffers) {
            return;
        }

        if ($report->acceptedOffersCount() > 0) {
            return;
        }

        $maxBatches = max(1, (int) config('emergency.max_auto_batches', 10));
        if ((int) $report->replacement_batch_number >= $maxBatches) {
            return;
        }

        $this->broadcastNextBatch($report->fresh());
    }

    /**
     * @param  list<UploadedFile>  $files
     * @return list<string>
     */
    private function storeEvidence(MuthowifBooking $booking, array $files): array
    {
        $paths = [];
        foreach ($files as $file) {
            if (! $file instanceof UploadedFile || ! $file->isValid()) {
                continue;
            }
            $paths[] = app(UploadedImageOptimizer::class)->store(
                $file,
                'emergency-evidence/'.$booking->getKey(),
                'local',
                'document',
            );
        }

        return $paths;
    }
}
