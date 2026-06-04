<?php

namespace App\Services;

use App\Models\BookingPayment;
use App\Models\MuthowifBooking;
use App\Models\MuthowifProfile;
use Illuminate\Support\Facades\DB;

final class BookingWalletCreditingService
{
    /**
     * @return array{credited: bool, error: string|null}
     */
    public function creditOnCompletion(MuthowifBooking $booking): array
    {
        $payment = $booking->settledBookingPayment();
        if ($payment === null) {
            return ['credited' => false, 'error' => __('bookings.flash.payment_tx_not_found')];
        }

        if ($payment->wallet_credited_at !== null) {
            return ['credited' => true, 'error' => null];
        }

        $credited = false;
        $error = null;

        try {
            DB::transaction(function () use ($booking, $payment, &$credited, &$error): void {
                $payment = BookingPayment::query()->whereKey($payment->getKey())->lockForUpdate()->firstOrFail();

                if ($payment->wallet_credited_at !== null) {
                    $credited = true;

                    return;
                }

                $profile = MuthowifProfile::query()
                    ->whereKey($booking->muthowif_profile_id)
                    ->lockForUpdate()
                    ->first();

                if ($profile === null) {
                    $error = __('bookings.flash.payment_tx_not_found');

                    return;
                }

                $amount = $payment->muthowifWalletCreditAmount();
                if ($amount > 0) {
                    $profile->wallet_balance = round((float) $profile->wallet_balance + $amount, 2);
                    $profile->save();
                }

                $this->creditReferralReward($payment);

                $payment->wallet_credited_at = now();
                $payment->save();

                $credited = true;
            });
        } catch (\Throwable $e) {
            $error = $e->getMessage();
        }

        return ['credited' => $credited, 'error' => $error];
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
}
