<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Models\BookingPayment;
use App\Models\MuthowifBooking;
use App\Support\CustomerBookingBroadcast;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

final class CancelUnpaidBookingAfterPaymentDeadline
{
    /**
     * Batalkan booking confirmed+unpaid yang sudah lewat payment_due_at.
     *
     * @return bool true jika berhasil dibatalkan sekarang
     */
    public function cancelIfOverdue(MuthowifBooking $booking, bool $notify = true): bool
    {
        $cancelled = false;

        try {
            DB::transaction(function () use ($booking, &$cancelled): void {
                /** @var MuthowifBooking|null $locked */
                $locked = MuthowifBooking::query()
                    ->whereKey($booking->getKey())
                    ->lockForUpdate()
                    ->first();

                if ($locked === null || ! $locked->isAwaitingPayment()) {
                    return;
                }

                if ($locked->payment_due_at === null || $locked->payment_due_at->isFuture()) {
                    return;
                }

                $locked->update([
                    'status' => BookingStatus::Cancelled,
                    'muthowif_rejection_kind' => null,
                    'muthowif_rejection_note' => __('bookings.payment_deadline.auto_cancel_note'),
                ]);

                BookingPayment::query()
                    ->where('muthowif_booking_id', $locked->getKey())
                    ->where('status', 'pending')
                    ->update(['status' => 'cancelled']);

                app(AffiliateCommissionService::class)->voidForBooking($locked, 'payment_deadline_expired');

                $cancelled = true;
            });
        } catch (Throwable $e) {
            Log::warning('payment_deadline.cancel_failed', [
                'booking_id' => $booking->getKey(),
                'exception' => $e->getMessage(),
            ]);

            return false;
        }

        if (! $cancelled) {
            return false;
        }

        $fresh = $booking->fresh();
        if ($fresh !== null) {
            CustomerBookingBroadcast::afterResponse($fresh);
            Cache::forget('customer_booking_status_counts:'.(string) $fresh->customer_id);

            if ($notify) {
                $notifier = app(MuthowifBookingWhatsAppNotifier::class);

                try {
                    $notifier->notifyCustomerPaymentDeadlineExpired($fresh);
                } catch (Throwable $e) {
                    Log::warning('payment_deadline.notify_customer_failed', [
                        'booking_id' => $fresh->getKey(),
                        'exception' => $e->getMessage(),
                    ]);
                }

                try {
                    $notifier->notifyMuthowifPaymentDeadlineExpired($fresh);
                } catch (Throwable $e) {
                    Log::warning('payment_deadline.notify_muthowif_failed', [
                        'booking_id' => $fresh->getKey(),
                        'exception' => $e->getMessage(),
                    ]);
                }
            }
        }

        return true;
    }

    public function isPaymentWindowExpired(MuthowifBooking $booking): bool
    {
        return $booking->isAwaitingPayment()
            && $booking->payment_due_at !== null
            && $booking->payment_due_at->isPast();
    }
}
