<?php

namespace App\Services\Incident;

use App\Enums\BookingIncidentResolution;
use App\Enums\BookingIncidentStatus;
use App\Enums\BookingReplacementStatus;
use App\Enums\BookingSettlementStatus;
use App\Enums\BookingSettlementType;
use App\Enums\PayoutAllocationRole;
use App\Enums\PayoutAllocationStatus;
use App\Models\BookingPayment;
use App\Models\BookingPayoutAllocation;
use App\Models\BookingSettlement;
use App\Models\MuthowifBooking;
use App\Models\MuthowifProfile;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class BookingSettlementService
{
    public function __construct(
        private readonly BookingSettlementCalculator $calculator,
    ) {}

    /**
     * @return array{completed: bool, credited: bool, error: string|null}
     */
    public function releaseOnCompletion(MuthowifBooking $booking): array
    {
        if (EscrowFreezeGuard::isFrozen($booking)) {
            return [
                'completed' => false,
                'credited' => false,
                'error' => __('incidents.errors.escrow_frozen'),
            ];
        }

        $payment = $booking->settledBookingPayment();
        if ($payment === null) {
            return ['completed' => false, 'credited' => false, 'error' => __('bookings.flash.payment_tx_not_found')];
        }

        $existing = BookingSettlement::query()
            ->where('booking_payment_id', $payment->getKey())
            ->where('status', BookingSettlementStatus::Released)
            ->first();

        if ($existing) {
            return $this->creditFromReleasedSettlement($booking, $payment, $existing);
        }

        $pendingSettlement = BookingSettlement::query()
            ->where('muthowif_booking_id', $booking->getKey())
            ->whereIn('status', [
                BookingSettlementStatus::Draft,
                BookingSettlementStatus::Approved,
            ])
            ->latest()
            ->first();

        if ($pendingSettlement !== null) {
            return $this->approveAndRelease($pendingSettlement, null);
        }

        $fromIncident = $this->ensureIncidentSettlement($booking);
        if ($fromIncident !== null) {
            return $this->approveAndRelease($fromIncident, null);
        }

        $settlement = BookingSettlement::query()->create([
            'muthowif_booking_id' => $booking->getKey(),
            'booking_payment_id' => $payment->getKey(),
            'settlement_type' => BookingSettlementType::NormalCompletion,
            'status' => BookingSettlementStatus::Approved,
            'calculation_snapshot' => ['mode' => 'normal_completion'],
            'approved_at' => now(),
        ]);

        BookingPayoutAllocation::query()->create([
            'booking_settlement_id' => $settlement->getKey(),
            'muthowif_profile_id' => $booking->muthowif_profile_id,
            'role' => PayoutAllocationRole::Primary,
            'service_days' => $booking->billingNightsInclusive(),
            'total_service_days' => $booking->billingNightsInclusive(),
            'amount' => $payment->muthowifWalletCreditAmount(),
            'status' => PayoutAllocationStatus::Pending,
        ]);

        return $this->approveAndRelease($settlement, null);
    }

    private function ensureIncidentSettlement(MuthowifBooking $booking): ?BookingSettlement
    {
        $incident = $booking->incidents()
            ->where('status', BookingIncidentStatus::Resolved)
            ->where('resolution_type', BookingIncidentResolution::ReplacementCompleted)
            ->latest()
            ->first();

        if ($incident === null) {
            return null;
        }

        $hasAcceptedReplacement = $incident->replacements()
            ->where('status', BookingReplacementStatus::AcceptedByCustomer)
            ->exists();

        if (! $hasAcceptedReplacement) {
            return null;
        }

        try {
            return app(IncidentCompensationService::class)->proposeSettlement($incident);
        } catch (\RuntimeException) {
            return null;
        }
    }

    /**
     * @return array{completed: bool, credited: bool, error: string|null}
     */
    public function approveAndRelease(BookingSettlement $settlement, ?User $admin): array
    {
        $credited = false;
        $error = null;

        try {
            DB::transaction(function () use ($settlement, $admin, &$credited, &$error): void {
                $settlement = BookingSettlement::query()->whereKey($settlement->getKey())->lockForUpdate()->firstOrFail();

                if ($settlement->status === BookingSettlementStatus::Released) {
                    $credited = true;

                    return;
                }

                $payment = BookingPayment::query()->whereKey($settlement->booking_payment_id)->lockForUpdate()->firstOrFail();

                if ($payment->wallet_credited_at !== null) {
                    $credited = true;

                    return;
                }

                $settlement->load('payoutAllocations');

                foreach ($settlement->payoutAllocations as $row) {
                    if ($row->status === PayoutAllocationStatus::Released) {
                        continue;
                    }

                    $profile = MuthowifProfile::query()->whereKey($row->muthowif_profile_id)->lockForUpdate()->first();
                    if ($profile === null) {
                        continue;
                    }

                    $amount = round((float) $row->amount, 2);
                    if ($amount > 0) {
                        $profile->wallet_balance = round((float) $profile->wallet_balance + $amount, 2);
                        $profile->save();
                    }

                    $row->update([
                        'status' => PayoutAllocationStatus::Released,
                        'released_at' => now(),
                    ]);
                }

                $this->creditReferralReward($payment);

                $payment->wallet_credited_at = now();
                if (Schema::hasColumn('booking_payments', 'settlement_state')) {
                    $payment->settlement_state = 'fully_released';
                }
                $payment->save();

                $settlement->update([
                    'status' => BookingSettlementStatus::Released,
                    'released_at' => now(),
                    'approved_by_user_id' => $admin?->getKey() ?? $settlement->approved_by_user_id,
                    'approved_at' => $settlement->approved_at ?? now(),
                ]);

                $credited = true;
            });
        } catch (\Throwable $e) {
            $error = $e->getMessage();
        }

        return ['completed' => true, 'credited' => $credited, 'error' => $error];
    }

    private function creditReferralReward(BookingPayment $payment): void
    {
        $reward = round((float) ($payment->referral_reward_amount ?? 0), 2);
        if ($reward <= 0 || ! filled($payment->referrer_muthowif_profile_id)) {
            return;
        }

        $referrer = MuthowifProfile::query()
            ->whereKey((string) $payment->referrer_muthowif_profile_id)
            ->lockForUpdate()
            ->first();

        if ($referrer === null) {
            return;
        }

        $referrer->wallet_balance = round((float) $referrer->wallet_balance + $reward, 2);
        $referrer->save();
    }

    /**
     * @return array{completed: bool, credited: bool, error: string|null}
     */
    private function creditFromReleasedSettlement(MuthowifBooking $booking, BookingPayment $payment, BookingSettlement $settlement): array
    {
        if ($payment->wallet_credited_at !== null) {
            return ['completed' => true, 'credited' => true, 'error' => null];
        }

        return $this->approveAndRelease($settlement, null);
    }
}
