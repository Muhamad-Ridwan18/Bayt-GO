<?php

namespace App\ViewModels\Booking;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

final class MuthowifBookingIndexPageData
{
    /**
     * @param  array<string, int>  $bookingStatusCounts
     * @param  array<string, MuthowifBookingIndexCardData>  $bookingCards
     */
    public function __construct(
        public readonly LengthAwarePaginator $bookings,
        public readonly array $bookingStatusCounts,
        public readonly ?string $statusFilter,
        public readonly array $bookingCards,
    ) {}

    /**
     * @param  array<string, mixed>|Collection<string, mixed>  $addonsById
     * @param  array<string, int>  $bookingStatusCounts
     */
    public static function make(
        LengthAwarePaginator $bookings,
        array|Collection $addonsById,
        array $bookingStatusCounts,
        ?string $statusFilter,
    ): self {
        $cards = [];
        foreach ($bookings as $booking) {
            $cards[(string) $booking->getKey()] = MuthowifBookingIndexCardData::make($booking, $addonsById);
        }

        return new self(
            bookings: $bookings,
            bookingStatusCounts: $bookingStatusCounts,
            statusFilter: $statusFilter,
            bookingCards: $cards,
        );
    }
}
