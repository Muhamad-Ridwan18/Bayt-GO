<?php

namespace App\Services\Incident;

use App\Enums\BookingIncidentCaseType;
use App\Enums\BookingIncidentOverlayStatus;
use App\Enums\BookingIncidentResolution;
use App\Enums\BookingIncidentSeverity;
use App\Enums\BookingIncidentStatus;
use App\Enums\BookingServicePhase;
use App\Enums\BookingStatus;
use App\Enums\MuthowifBookingMuthowifRejectionKind;
use App\Enums\PaymentStatus;
use App\Models\BookingIncident;
use App\Models\MuthowifBooking;
use App\Models\User;
use App\Support\AdminServiceMonitorBroadcast;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

final class BookingIncidentService
{
    public function __construct(
        private readonly BookingIncidentEventLogger $logger,
        private readonly BookingReplacementRecruitmentService $recruitment,
    ) {}

    public function openFromEmergency(
        MuthowifBooking $booking,
        User $customer,
        ?string $statement = null,
        ?array $geo = null,
    ): BookingIncident {
        $this->assertCanReportEmergency($booking, $customer);
        $this->assertEmergencyRateLimit($booking);

        $booking->syncServicePhase();

        return $this->openIncident(
            $booking,
            $this->inferEmergencyCaseType($booking),
            $customer,
            $statement,
            $geo,
            BookingIncidentSeverity::Critical,
        );
    }

    public function openFromMuthowifReport(
        MuthowifBooking $booking,
        User $muthowifUser,
        BookingIncidentCaseType $caseType,
        ?string $statement = null,
        ?UploadedFile $evidence = null,
    ): BookingIncident {
        $profile = $muthowifUser->muthowifProfile;
        if ($profile === null || (string) $booking->muthowif_profile_id !== (string) $profile->getKey()) {
            throw ValidationException::withMessages(['booking' => __('incidents.errors.not_booking_muthowif')]);
        }

        if ($booking->status !== BookingStatus::Confirmed || ! $booking->isPaid()) {
            throw ValidationException::withMessages(['booking' => __('incidents.errors.booking_not_eligible')]);
        }

        if ($booking->hasOpenIncident()) {
            throw ValidationException::withMessages(['booking' => __('incidents.errors.incident_already_open')]);
        }

        $booking->syncServicePhase();

        $metadata = [];
        if ($evidence !== null) {
            $metadata['evidence_path'] = $this->storeEvidence($evidence, $booking);
        }

        $severity = match ($caseType) {
            BookingIncidentCaseType::MuthowifUnavailable, BookingIncidentCaseType::ForceMajeure => BookingIncidentSeverity::High,
            BookingIncidentCaseType::AbandonedService, BookingIncidentCaseType::LostContactInService => BookingIncidentSeverity::Critical,
            default => BookingIncidentSeverity::Medium,
        };

        return $this->openIncident(
            $booking,
            $caseType,
            $muthowifUser,
            $statement,
            $metadata,
            $severity,
            muthowifStatement: $statement,
        );
    }

    public function assignAdmin(BookingIncident $incident, User $admin): BookingIncident
    {
        $incident->update([
            'assigned_admin_id' => $admin->getKey(),
            'status' => $incident->status === BookingIncidentStatus::Open
                ? BookingIncidentStatus::Triage
                : $incident->status,
        ]);

        $this->logger->log($incident, 'incident.assigned', 'admin', $admin->getKey(), []);

        return $incident->fresh();
    }

    public function moveToInvestigating(BookingIncident $incident, User $admin): BookingIncident
    {
        $incident->update(['status' => BookingIncidentStatus::Investigating]);
        $this->logger->log($incident, 'incident.investigating', 'admin', $admin->getKey(), []);

        return $incident->fresh();
    }

    public function resolve(
        BookingIncident $incident,
        User $admin,
        BookingIncidentResolution $resolution,
        ?string $note = null,
    ): BookingIncident {
        return DB::transaction(function () use ($incident, $admin, $resolution, $note) {
            $incident->update([
                'status' => BookingIncidentStatus::Resolved,
                'resolution_type' => $resolution,
                'admin_resolution_note' => $note,
                'resolved_at' => now(),
            ]);

            $booking = $incident->muthowifBooking;
            $booking->update(['incident_status' => BookingIncidentOverlayStatus::Resolved]);

            $this->logger->log($incident, 'incident.resolved', 'admin', $admin->getKey(), [
                'resolution' => $resolution->value,
            ]);

            $fresh = $incident->fresh();
            AdminServiceMonitorBroadcast::notify($fresh->muthowifBooking, 'incident_resolved');

            return $fresh;
        });
    }

    public function cancelFalseAlarm(BookingIncident $incident, User $admin, ?string $note = null): BookingIncident
    {
        return DB::transaction(function () use ($incident, $admin, $note) {
            $incident->update([
                'status' => BookingIncidentStatus::Cancelled,
                'resolution_type' => BookingIncidentResolution::FalseAlarm,
                'admin_resolution_note' => $note,
                'resolved_at' => now(),
            ]);

            $incident->muthowifBooking->update([
                'incident_status' => BookingIncidentOverlayStatus::None,
            ]);

            $this->logger->log($incident, 'incident.cancelled', 'admin', $admin->getKey(), []);

            $fresh = $incident->fresh();
            AdminServiceMonitorBroadcast::notify($fresh->muthowifBooking, 'incident_cancelled');

            return $fresh;
        });
    }

    private function openIncident(
        MuthowifBooking $booking,
        BookingIncidentCaseType $caseType,
        User $reporter,
        ?string $customerStatement,
        array|string|null $metadataOrGeo,
        BookingIncidentSeverity $severity,
        ?string $muthowifStatement = null,
    ): BookingIncident {
        $incident = DB::transaction(function () use ($booking, $caseType, $reporter, $customerStatement, $metadataOrGeo, $severity, $muthowifStatement) {
            $booking->refresh()->lockForUpdate();

            if ($booking->hasOpenIncident()) {
                throw ValidationException::withMessages(['booking' => __('incidents.errors.incident_already_open')]);
            }

            $metadata = is_array($metadataOrGeo) ? $metadataOrGeo : [];
            if (is_array($metadataOrGeo) && isset($metadataOrGeo['lat'])) {
                $metadata['geo'] = $metadataOrGeo;
            }

            $metadata['policy_version'] = config('incident.policy_version', '2026-06');

            $incident = BookingIncident::query()->create([
                'muthowif_booking_id' => $booking->getKey(),
                'case_type' => $caseType,
                'severity' => $severity,
                'status' => BookingIncidentStatus::Open,
                'reported_by_user_id' => $reporter->getKey(),
                'customer_statement' => $customerStatement,
                'muthowif_statement' => $muthowifStatement,
                'metadata' => $metadata,
                'policy_version' => config('incident.policy_version', '2026-06'),
                'total_service_days' => $booking->billingNightsInclusive(),
                'opened_at' => now(),
            ]);

            $booking->update([
                'incident_status' => BookingIncidentOverlayStatus::Open,
                'emergency_reported_at' => $reporter->isCustomer() ? now() : $booking->emergency_reported_at,
            ]);

            $actorType = $reporter->isAdmin() ? 'admin' : ($reporter->isMuthowif() ? 'muthowif' : 'customer');

            $this->logger->log($incident, 'incident.opened', $actorType, $reporter->getKey(), [
                'case_type' => $caseType->value,
                'severity' => $severity->value,
            ]);

            return $incident;
        });

        $booking = $incident->muthowifBooking ?? MuthowifBooking::query()->find($incident->muthowif_booking_id);
        AdminServiceMonitorBroadcast::notify($booking, 'incident_opened');

        if ($this->recruitment->shouldAutoStartForCase($caseType)) {
            return $this->recruitment->start($incident);
        }

        return $incident;
    }

    private function inferEmergencyCaseType(MuthowifBooking $booking): BookingIncidentCaseType
    {
        if ($booking->service_phase === BookingServicePhase::InService) {
            return BookingIncidentCaseType::LostContactInService;
        }

        if ($booking->starts_on && $booking->starts_on->isToday()) {
            return BookingIncidentCaseType::NoShow;
        }

        return BookingIncidentCaseType::LostContactInService;
    }

    private function assertCanReportEmergency(MuthowifBooking $booking, User $customer): void
    {
        if (! $customer->isCustomer() || (string) $booking->customer_id !== (string) $customer->getKey()) {
            throw ValidationException::withMessages(['booking' => __('incidents.errors.not_booking_owner')]);
        }

        if ($booking->status !== BookingStatus::Confirmed || ! $booking->isPaid()) {
            throw ValidationException::withMessages(['booking' => __('incidents.errors.booking_not_eligible')]);
        }

        if ($booking->incident_status === BookingIncidentOverlayStatus::Open) {
            throw ValidationException::withMessages(['booking' => __('incidents.errors.incident_already_open')]);
        }
    }

    private function assertEmergencyRateLimit(MuthowifBooking $booking): void
    {
        $hours = (int) config('incident.emergency_rate_limit_hours', 6);
        if ($booking->emergency_reported_at && $booking->emergency_reported_at->gt(now()->subHours($hours))) {
            throw ValidationException::withMessages(['emergency' => __('incidents.errors.emergency_rate_limit')]);
        }
    }

    private function storeEvidence(UploadedFile $file, MuthowifBooking $booking): string
    {
        return $file->store('incident-evidence/'.$booking->getKey(), 'local');
    }

    public static function mapRejectionToCase(?MuthowifBookingMuthowifRejectionKind $kind): ?BookingIncidentCaseType
    {
        return match ($kind) {
            MuthowifBookingMuthowifRejectionKind::Illness => BookingIncidentCaseType::MuthowifUnavailable,
            MuthowifBookingMuthowifRejectionKind::ForceMajeure => BookingIncidentCaseType::ForceMajeure,
            default => null,
        };
    }
}
