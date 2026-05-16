<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Models\BookingPayment;
use App\Models\MuthowifBooking;
use App\Support\MuthowifReferralReward;
use App\Support\PaymentFlowLog;
use App\Support\PlatformFee;
use Illuminate\Support\Facades\DB;

/**
 * Memastikan ada satu baris {@see BookingPayment} status pending (placeholder tagihan)
 * setelah booking dikonfirmasi dan belum lunas — agar webhook/DB tidak “kosong” sebelum jamaah buka halaman bayar.
 */
final class BookingPendingPaymentEnsurer
{
    public function ensure(MuthowifBooking $booking): ?BookingPayment
    {
        $booking->refresh();

        if ($booking->status !== BookingStatus::Confirmed) {
            return null;
        }

        if ($booking->payment_status !== PaymentStatus::Pending) {
            return null;
        }

        if ($booking->isPaid()) {
            return null;
        }

        $baseAmount = $booking->resolvedAmountDue();
        if ($baseAmount < 0.01) {
            return null;
        }

        return DB::transaction(function () use ($booking, $baseAmount): ?BookingPayment {
            $booking->refresh();

            if ($booking->status !== BookingStatus::Confirmed
                || $booking->payment_status !== PaymentStatus::Pending
                || $booking->isPaid()) {
                return null;
            }

            $split = PlatformFee::split($baseAmount);

            /** Sudah ada sesi charge (Moota trx / SNAP): jangan buat kedua; biarkan satu baris aktif. */
            $withGateway = $booking->bookingPayments()
                ->where('status', 'pending')
                ->whereNotNull('gateway_transaction_id')
                ->exists();

            if ($withGateway) {
                $p = $booking->bookingPayments()
                    ->where('status', 'pending')
                    ->whereNotNull('gateway_transaction_id')
                    ->latest('id')
                    ->first();

                PaymentFlowLog::info('booking_payment.ensure.skip_has_active_gateway', [
                    'booking_id' => $booking->getKey(),
                    'payment_id' => $p?->getKey(),
                ]);

                return $p;
            }

            $withoutTrx = $booking->bookingPayments()
                ->where('status', 'pending')
                ->whereNull('gateway_transaction_id')
                ->orderByDesc('id')
                ->get();

            if ($withoutTrx->count() > 1) {
                $keep = $withoutTrx->first();
                foreach ($withoutTrx->skip(1) as $dup) {
                    $dup->update(['status' => 'cancelled']);
                }
                PaymentFlowLog::info('booking_payment.ensure.deduped_pending_without_gateway', [
                    'booking_id' => $booking->getKey(),
                    'kept_payment_id' => $keep?->getKey(),
                ]);
            }

            $placeholder = $booking->bookingPayments()
                ->where('status', 'pending')
                ->whereNull('gateway_transaction_id')
                ->latest('id')
                ->first();

            $gross = $split['customer_gross'];
            $referral = MuthowifReferralReward::paymentSnapshot(
                (float) $split['muthowif_net'],
                (string) $booking->muthowif_profile_id,
            );

            if ($placeholder !== null) {
                $placeholder->update([
                    'gross_amount' => $gross,
                    'platform_fee_amount' => $split['platform_fee_total'],
                    'muthowif_net_amount' => $split['muthowif_net'],
                    'referrer_muthowif_profile_id' => $referral['referrer_muthowif_profile_id'],
                    'referral_reward_amount' => $referral['referral_reward_amount'],
                    'booking_code' => $booking->booking_code,
                ]);
                PaymentFlowLog::info('booking_payment.ensure.updated_placeholder', [
                    'booking_id' => $booking->getKey(),
                    'order_id' => $placeholder->order_id,
                    'gross_amount' => $gross,
                ]);

                return $placeholder->fresh();
            }

            $ids = BookingPayment::newPrimaryKeyAndOrderId((string) $booking->getKey());
            $orderId = $ids['order_id'];

            $created = BookingPayment::query()->create([
                'id' => $ids['id'],
                'muthowif_booking_id' => $booking->getKey(),
                'booking_code' => $booking->booking_code,
                'order_id' => $orderId,
                'gross_amount' => $gross,
                'platform_fee_amount' => $split['platform_fee_total'],
                'muthowif_net_amount' => $split['muthowif_net'],
                'referrer_muthowif_profile_id' => $referral['referrer_muthowif_profile_id'],
                'referral_reward_amount' => $referral['referral_reward_amount'],
                'status' => 'pending',
            ]);

            PaymentFlowLog::info('booking_payment.ensure.created', [
                'booking_id' => $booking->getKey(),
                'order_id' => $orderId,
                'gross_amount' => $gross,
            ]);

            return $created;
        });
    }
}
