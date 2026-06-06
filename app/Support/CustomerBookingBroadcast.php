<?php

namespace App\Support;

use App\Events\CustomerBookingUpdated;
use App\Models\MuthowifBooking;

final class CustomerBookingBroadcast
{
    public static function notify(MuthowifBooking|string $booking): void
    {
        self::notifyMany([(string) ($booking instanceof MuthowifBooking ? $booking->getKey() : $booking)]);
    }

    /**
     * @param  list<string>  $bookingIds
     */
    public static function notifyMany(array $bookingIds): void
    {
        $ids = array_values(array_unique(array_filter(
            array_map(static fn ($id) => trim((string) $id), $bookingIds),
            static fn (string $id): bool => $id !== '',
        )));

        if ($ids === []) {
            return;
        }

        foreach ($ids as $id) {
            $model = MuthowifBooking::query()->find($id);
            if ($model !== null) {
                ReverbBroadcast::send(new CustomerBookingUpdated($model), 'booking');
            }
        }
    }

    public static function afterResponse(MuthowifBooking|string $booking): void
    {
        self::notify($booking);
    }

    /**
     * @param  list<string>  $bookingIds
     */
    public static function afterResponseMany(array $bookingIds): void
    {
        self::notifyMany($bookingIds);
    }
}
