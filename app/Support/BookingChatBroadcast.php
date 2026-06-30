<?php

namespace App\Support;

use App\Events\BookingChatUpdated;
use App\Jobs\SendBookingChatPushNotification;
use App\Models\MuthowifBooking;
/**
 * Chat wajib realtime — broadcast sinkron ke Reverb (ShouldBroadcastNow).
 * Defer afterResponse sering tidak jalan di PHP-FPM sebelum proses terminate selesai.
 */
final class BookingChatBroadcast
{
    public static function notify(
        MuthowifBooking|string $booking,
        string $action = 'message',
        ?string $messageId = null,
        ?string $senderId = null,
    ): void {
        $model = $booking instanceof MuthowifBooking
            ? $booking
            : MuthowifBooking::query()->find((string) $booking);

        if ($model === null) {
            return;
        }

        ReverbBroadcast::send(
            new BookingChatUpdated($model, $action, $messageId, $senderId),
            'chat',
        );

        if ($action === 'message' && $messageId !== null && $senderId !== null) {
            SendBookingChatPushNotification::dispatch(
                (string) $model->getKey(),
                $messageId,
                $senderId,
            );
        }
    }

    /** @deprecated Gunakan {@see notify()}; alias agar pemanggil lama tetap jalan. */
    public static function afterResponse(
        MuthowifBooking|string $booking,
        string $action = 'message',
        ?string $messageId = null,
        ?string $senderId = null,
    ): void {
        self::notify($booking, $action, $messageId, $senderId);
    }
}
