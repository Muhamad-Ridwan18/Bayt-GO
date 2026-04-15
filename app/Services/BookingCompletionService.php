<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Models\BookingPayment;
use App\Models\BookingReview;
use App\Models\MuthowifBooking;
use App\Models\MuthowifProfile;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class BookingCompletionService
{
    /**
     * Waktu mulai boleh auto-complete: awal hari setelah ends_on + jeda menit (APP_TIMEZONE).
     * Null jika ends_on kosong.
     */
    public function autoCompleteEligibleAt(MuthowifBooking $booking): ?CarbonInterface
    {
        if ($booking->ends_on === null) {
            return null;
        }

        $minutes = (int) config('booking.auto_complete_grace_minutes_after_service_day', 0);

        return $booking->ends_on
            ->copy()
            ->timezone(config('app.timezone'))
            ->addDay()
            ->startOfDay()
            ->addMinutes(max(0, $minutes));
    }

    /**
     * @return array{completed: bool, credited: bool, error: string|null}
     */
    public function complete(MuthowifBooking $booking, int $rating, ?string $review): array
    {
        $completed = false;
        $credited = false;
        $error = null;

        try {
            DB::transaction(function () use ($booking, $rating, $review, &$completed, &$credited, &$error): void {
                $booking->refresh();

                if ($booking->status === BookingStatus::Completed) {
                    $completed = true;

                    return;
                }

                /** @var BookingPayment|null $payment */
                $payment = BookingPayment::query()
                    ->where('muthowif_booking_id', $booking->getKey())
                    ->whereIn('status', ['settlement', 'capture'])
                    ->orderByDesc('settled_at')
                    ->lockForUpdate()
                    ->first();

                if ($payment === null) {
                    $error = __('bookings.flash.payment_tx_not_found');

                    return;
                }

                if ($payment->wallet_credited_at === null) {
                    /** @var MuthowifProfile $profile */
                    $profile = MuthowifProfile::query()
                        ->whereKey($booking->muthowif_profile_id)
                        ->lockForUpdate()
                        ->firstOrFail();

                    $profile->wallet_balance = round((float) $profile->wallet_balance + (float) $payment->muthowif_net_amount, 2);
                    $profile->save();

                    $payment->wallet_credited_at = now();
                    $payment->save();
                    $credited = true;
                }

                $booking->status = BookingStatus::Completed;
                $booking->save();

                BookingReview::query()->updateOrCreate(
                    ['muthowif_booking_id' => $booking->getKey()],
                    [
                        'muthowif_profile_id' => $booking->muthowif_profile_id,
                        'customer_id' => (string) $booking->customer_id,
                        'rating' => $rating,
                        'review' => filled($review) ? trim((string) $review) : null,
                    ]
                );

                $completed = true;
            });
        } catch (\Throwable $e) {
            $error = $e->getMessage();
        }

        return [
            'completed' => $completed,
            'credited' => $credited,
            'error' => $error,
        ];
    }

    public function shouldAutoCompleteNow(MuthowifBooking $booking): bool
    {
        if ($booking->status !== BookingStatus::Confirmed || ! $booking->isPaid()) {
            return false;
        }

        $at = $this->autoCompleteEligibleAt($booking);

        if ($at === null) {
            return false;
        }

        return now()->greaterThanOrEqualTo($at);
    }
}
