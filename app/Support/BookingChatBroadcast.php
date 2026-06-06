<?php

namespace App\Support;

use App\Events\BookingChatUpdated;
use App\Models\MuthowifBooking;
use Illuminate\Support\Facades\DB;

/**
 * Broadcast pembaruan chat setelah respons HTTP selesai (Reverb tidak memblokir request).
 */
final class BookingChatBroadcast
{
    public static function afterResponse(MuthowifBooking|string $booking): void
    {
        $bookingId = (string) ($booking instanceof MuthowifBooking ? $booking->getKey() : $booking);
        if ($bookingId === '') {
            return;
        }

        DB::afterCommit(static function () use ($bookingId): void {
            dispatch(static function () use ($bookingId): void {
                $fresh = MuthowifBooking::query()->find($bookingId);
                if ($fresh !== null) {
                    broadcast(new BookingChatUpdated($fresh));
                }
            })->afterResponse();
        });
    }
}
