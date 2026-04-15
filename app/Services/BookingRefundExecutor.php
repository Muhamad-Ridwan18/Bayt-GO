<?php

namespace App\Services;

use App\Enums\BookingChangeRequestStatus;
use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Models\BookingRefundRequest;
use App\Models\MuthowifBooking;
use App\Models\User;
use App\Support\BookingRefundFee;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class BookingRefundExecutor
{
    /**
     * Refund manual: catat permintaan, booking dibatalkan, pembayaran menunggu transfer admin.
     *
     * @throws RuntimeException
     */
    public function execute(MuthowifBooking $booking, User $customer, ?string $customerNote): void
    {
        $payment = $booking->settledBookingPayment();
        if ($payment === null) {
            throw new RuntimeException('Data pembayaran tidak ditemukan.');
        }

        $snapshot = BookingRefundFee::snapshot($booking, $payment);

        DB::transaction(function () use (
            $booking,
            $customer,
            $customerNote,
            $snapshot
        ): void {
            $booking->refresh()->lockForUpdate();

            if (! $booking->isPaid() || $booking->status !== BookingStatus::Confirmed) {
                throw new RuntimeException('Status booking tidak valid untuk refund.');
            }

            $pay = $booking->settledBookingPayment();
            if ($pay !== null && $pay->wallet_credited_at !== null) {
                throw new RuntimeException('Saldo muthowif untuk pembayaran ini sudah dicairkan. Hubungi admin.');
            }

            BookingRefundRequest::query()->create([
                'muthowif_booking_id' => $booking->getKey(),
                'customer_id' => $customer->id,
                'status' => BookingChangeRequestStatus::Pending,
                'customer_note' => filled($customerNote) ? trim($customerNote) : null,
                'service_base_amount' => $snapshot['service_base_amount'],
                'customer_paid_amount' => $snapshot['customer_paid_amount'],
                'refund_fee_platform' => $snapshot['refund_fee_platform'],
                'refund_fee_muthowif' => $snapshot['refund_fee_muthowif'],
                'net_refund_customer' => $snapshot['net_refund_customer'],
            ]);

            $booking->update([
                'status' => BookingStatus::Cancelled,
                'payment_status' => PaymentStatus::RefundPending,
            ]);
        });
    }
}
