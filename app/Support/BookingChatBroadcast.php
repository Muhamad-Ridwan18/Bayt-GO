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
    public static function afterResponse(
        MuthowifBooking|string $booking,
        string $action = 'message',
        ?string $messageId = null,
        ?string $senderId = null,
    ): void {
        $bookingId = (string) ($booking instanceof MuthowifBooking ? $booking->getKey() : $booking);
        if ($bookingId === '') {
            return;
        }

        DB::afterCommit(static function () use ($bookingId, $action, $messageId, $senderId): void {
            dispatch(static function () use ($bookingId, $action, $messageId, $senderId): void {
                $fresh = MuthowifBooking::query()->find($bookingId);
                if ($fresh !== null) {
                    broadcast(new BookingChatUpdated($fresh, $action, $messageId, $senderId));
                }
            })->afterResponse();
        });
    }
}
