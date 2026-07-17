<?php

namespace App\Services;

use App\Enums\AffiliateCommissionStatus;
use App\Enums\AffiliateWalletTransactionType;
use App\Models\Affiliate;
use App\Models\AffiliateCommission;
use App\Models\BookingPayment;
use App\Models\MuthowifBooking;
use Illuminate\Support\Facades\DB;

class AffiliateCommissionService
{
    public function __construct(
        private readonly AffiliateWalletService $wallet,
    ) {}

    public function createPendingFromSettledPayment(BookingPayment $payment): ?AffiliateCommission
    {
        return DB::transaction(function () use ($payment): ?AffiliateCommission {
            /** @var BookingPayment $lockedPayment */
            $lockedPayment = BookingPayment::query()->whereKey($payment->getKey())->lockForUpdate()->firstOrFail();

            /** @var MuthowifBooking|null $booking */
            $booking = MuthowifBooking::query()
                ->whereKey($lockedPayment->muthowif_booking_id)
                ->lockForUpdate()
                ->first();

            if ($booking === null || $booking->affiliate_id === null) {
                return null;
            }

            if ($booking->affiliate_commission_amount === null || (float) $booking->affiliate_commission_amount <= 0) {
                return null;
            }

            $existing = AffiliateCommission::query()
                ->where('muthowif_booking_id', $booking->getKey())
                ->lockForUpdate()
                ->first();

            if ($existing !== null) {
                if ($existing->booking_payment_id === null) {
                    $existing->booking_payment_id = $lockedPayment->id;
                    $existing->platform_fee_amount_snapshot = (float) ($lockedPayment->platform_fee_amount ?? 0);
                    $existing->save();
                }

                return $existing;
            }

            return AffiliateCommission::query()->create([
                'affiliate_id' => $booking->affiliate_id,
                'muthowif_booking_id' => $booking->getKey(),
                'booking_payment_id' => $lockedPayment->id,
                'customer_id' => $booking->customer_id,
                'affiliate_code_snapshot' => (string) $booking->affiliate_code_snapshot,
                'commission_rate_snapshot' => (float) $booking->affiliate_rate_snapshot,
                'transaction_base_amount_snapshot' => (float) $booking->affiliate_base_amount_snapshot,
                'platform_fee_amount_snapshot' => (float) ($lockedPayment->platform_fee_amount ?? 0),
                'commission_amount' => (float) $booking->affiliate_commission_amount,
                'status' => AffiliateCommissionStatus::Pending,
                'pending_at' => now(),
            ]);
        });
    }

    public function markAvailableOnCompletion(MuthowifBooking $booking): ?AffiliateCommission
    {
        return DB::transaction(function () use ($booking): ?AffiliateCommission {
            /** @var MuthowifBooking $lockedBooking */
            $lockedBooking = MuthowifBooking::query()->whereKey($booking->getKey())->lockForUpdate()->firstOrFail();

            if ($lockedBooking->affiliate_id === null) {
                return null;
            }

            /** @var AffiliateCommission|null $commission */
            $commission = AffiliateCommission::query()
                ->where('muthowif_booking_id', $lockedBooking->getKey())
                ->lockForUpdate()
                ->first();

            if ($commission === null) {
                $payment = $lockedBooking->settledBookingPayment();
                if ($payment === null) {
                    return null;
                }
                $commission = $this->createPendingFromSettledPayment($payment);
                if ($commission === null) {
                    return null;
                }
                $commission = AffiliateCommission::query()->whereKey($commission->getKey())->lockForUpdate()->firstOrFail();
            }

            if ($commission->status === AffiliateCommissionStatus::Available) {
                return $commission;
            }

            if ($commission->status === AffiliateCommissionStatus::Void) {
                return $commission;
            }

            /** @var Affiliate $affiliate */
            $affiliate = Affiliate::query()->whereKey($commission->affiliate_id)->lockForUpdate()->firstOrFail();

            $this->wallet->credit(
                $affiliate,
                (float) $commission->commission_amount,
                AffiliateWalletTransactionType::CommissionCredit,
                'commission-credit:'.$commission->id,
                AffiliateCommission::class,
                (string) $commission->id,
                'Komisi affiliate tersedia',
            );

            $commission->status = AffiliateCommissionStatus::Available;
            $commission->available_at = now();
            $commission->save();

            return $commission;
        });
    }

    public function voidForBooking(MuthowifBooking $booking, string $reason): ?AffiliateCommission
    {
        return DB::transaction(function () use ($booking, $reason): ?AffiliateCommission {
            /** @var AffiliateCommission|null $commission */
            $commission = AffiliateCommission::query()
                ->where('muthowif_booking_id', $booking->getKey())
                ->lockForUpdate()
                ->first();

            if ($commission === null) {
                return null;
            }

            if ($commission->status === AffiliateCommissionStatus::Void) {
                return $commission;
            }

            if ($commission->status === AffiliateCommissionStatus::Available) {
                /** @var Affiliate $affiliate */
                $affiliate = Affiliate::query()->whereKey($commission->affiliate_id)->lockForUpdate()->firstOrFail();

                $this->wallet->credit(
                    $affiliate,
                    -1 * (float) $commission->commission_amount,
                    AffiliateWalletTransactionType::CommissionReversal,
                    'commission-reversal:'.$commission->id,
                    AffiliateCommission::class,
                    (string) $commission->id,
                    'Komisi affiliate dibatalkan',
                    ['reason' => $reason],
                );
            }

            $commission->status = AffiliateCommissionStatus::Void;
            $commission->voided_at = now();
            $commission->void_reason = $reason;
            $commission->save();

            return $commission;
        });
    }
}
