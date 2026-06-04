<?php

namespace App\Support;

use App\Events\CustomerBookingUpdated;
use App\Models\MuthowifBooking;
use Illuminate\Support\Facades\DB;

/**
 * Broadcast booking update setelah respons HTTP selesai (Reverb tidak memblokir request).
 */
final class CustomerBookingBroadcast
{
    public static function afterResponse(MuthowifBooking|string $booking): void
    {
        self::afterResponseMany([(string) ($booking instanceof MuthowifBooking ? $booking->getKey() : $booking)]);
    }

    /**
     * @param  list<string>  $bookingIds
     */
    public static function afterResponseMany(array $bookingIds): void
    {
        $ids = array_values(array_unique(array_filter(
            array_map(static fn ($id) => trim((string) $id), $bookingIds),
            static fn (string $id): bool => $id !== '',
        )));

        if ($ids === []) {
            return;
        }

        DB::afterCommit(static function () use ($ids): void {
            dispatch(static function () use ($ids): void {
                foreach ($ids as $id) {
                    $booking = MuthowifBooking::query()->find($id);
                    if ($booking !== null) {
                        broadcast(new CustomerBookingUpdated($booking));
                    }
                }
            })->afterResponse();
        });
    }
}
