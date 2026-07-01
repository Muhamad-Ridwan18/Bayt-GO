<?php

namespace App\Support;

use App\Models\MuthowifBooking;
use Illuminate\Support\Facades\URL;

final class BookingInvoiceUrl
{
    public static function signed(MuthowifBooking $booking, ?\DateTimeInterface $expires = null): string
    {
        return URL::temporarySignedRoute(
            'bookings.invoice.signed',
            $expires ?? now()->addDays(90),
            ['booking' => $booking],
        );
    }
}
