<?php

namespace App\Services\Incident;

use App\Enums\BookingIncidentCaseType;
use App\Enums\BookingSettlementStatus;
use App\Enums\BookingSettlementType;
use App\Enums\PayoutAllocationStatus;
use App\Models\BookingIncident;
use App\Models\BookingPayoutAllocation;
use App\Models\BookingReplacement;
use App\Models\BookingSettlement;
use App\Models\User;

final class IncidentCompensationService
{
    public function __construct(
        private readonly BookingSettlementCalculator $calculator,
        private readonly BookingReplacementSettlementService $replacementSettlement,
    ) {}

    public function proposeSettlement(BookingIncident $incident, ?int $daysPrimary = null, ?int $daysReplacement = null): BookingSettlement
    {
        $booking = $incident->muthowifBooking;
        $payment = $booking->settledBookingPayment();
        if ($payment === null) {
            throw new \RuntimeException(__('bookings.flash.payment_tx_not_found'));
        }

        $totalDays = $incident->total_service_days ?? $booking->billingNightsInclusive();
        $elapsed = $daysPrimary ?? $this->calculator->elapsedServiceDays($booking);
        $elapsed = min($totalDays, max(0, $elapsed));

        $replacement = $incident->replacements()
            ->where('status', \App\Enums\BookingReplacementStatus::AcceptedByCustomer)
            ->latest()
            ->first();

        $primaryProfileId = (string) ($replacement?->original_muthowif_profile_id ?? $booking->muthowif_profile_id);
        $replacementProfileId = $replacement?->replacement_muthowif_profile_id;

        $primaryZero = in_array($incident->case_type, [
            BookingIncidentCaseType::AbandonedService,
            BookingIncidentCaseType::LostContactInService,
        ], true);

        $daysReplacement = $daysReplacement ?? max(0, $totalDays - $elapsed);
        if ($replacement === null) {
            $daysReplacement = 0;
            $replacementProfileId = null;
        }

        $split = $this->calculator->incidentSplit(
            $payment,
            $booking,
            $primaryProfileId,
            $replacementProfileId,
            $elapsed,
            $daysReplacement,
            $totalDays,
            $primaryZero,
        );

        $existing = BookingSettlement::query()
            ->where('booking_incident_id', $incident->getKey())
            ->where('status', BookingSettlementStatus::Draft)
            ->first();

        if ($existing) {
            $existing->payoutAllocations()->delete();
            $settlement = $existing;
            $settlement->update([
                'calculation_snapshot' => $split['snapshot'],
                'settlement_type' => BookingSettlementType::IncidentSplit,
            ]);
        } else {
            $settlement = BookingSettlement::query()->create([
                'muthowif_booking_id' => $booking->getKey(),
                'booking_payment_id' => $payment->getKey(),
                'booking_incident_id' => $incident->getKey(),
                'settlement_type' => BookingSettlementType::IncidentSplit,
                'status' => BookingSettlementStatus::Draft,
                'calculation_snapshot' => $split['snapshot'],
            ]);
        }

        foreach ($split['allocations'] as $row) {
            BookingPayoutAllocation::query()->create([
                'booking_settlement_id' => $settlement->getKey(),
                'muthowif_profile_id' => $row['profile_id'],
                'role' => $row['role'],
                'service_days' => $row['days'],
                'total_service_days' => $totalDays,
                'amount' => $row['amount'],
                'status' => PayoutAllocationStatus::Pending,
            ]);
        }

        $incident->update([
            'completed_service_days' => $elapsed,
            'total_service_days' => $totalDays,
        ]);

        return $settlement->fresh(['payoutAllocations']);
    }

    public function approveAndReleaseSettlement(BookingSettlement $settlement, User $admin): void
    {
        $settlement->update([
            'status' => BookingSettlementStatus::Approved,
            'approved_by_user_id' => $admin->getKey(),
            'approved_at' => now(),
        ]);

        app(BookingSettlementService::class)->approveAndRelease($settlement, $admin);
    }
}
