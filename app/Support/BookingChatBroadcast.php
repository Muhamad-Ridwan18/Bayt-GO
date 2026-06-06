<?php

namespace App\Support;

use App\Events\BookingChatUpdated;
use App\Models\MuthowifBooking;
use Illuminate\Support\Facades\Log;

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

        if (config('broadcasting.default') === 'null' || config('broadcasting.default') === null) {
            Log::warning('chat.broadcast_skipped', [
                'booking_id' => $model->getKey(),
                'reason' => 'BROADCAST_CONNECTION=null',
            ]);

            return;
        }

        try {
            broadcast(new BookingChatUpdated($model, $action, $messageId, $senderId));

            if (config('app.debug')) {
                Log::debug('chat.broadcast_sent', [
                    'booking_id' => $model->getKey(),
                    'action' => $action,
                    'message_id' => $messageId,
                    'channel' => 'booking.chat.'.$model->getKey(),
                    'event' => 'chat.updated',
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('chat.broadcast_failed', [
                'booking_id' => $model->getKey(),
                'action' => $action,
                'message' => $e->getMessage(),
            ]);
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
