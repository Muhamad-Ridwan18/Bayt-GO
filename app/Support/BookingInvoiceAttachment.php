<?php

namespace App\Support;

use App\Models\MuthowifBooking;

final class BookingInvoiceAttachment
{
    /**
     * @return array{ContentType: string, Filename: string, Base64Content: string}
     */
    public static function forBooking(MuthowifBooking $booking): array
    {
        $booking->loadMissing(['customer', 'muthowifProfile.user']);
        $payment = $booking->settledBookingPayment();

        $html = view('emails.invoice-attachment', [
            'booking' => $booking,
            'payment' => $payment,
        ])->render();

        $code = filled($booking->booking_code)
            ? preg_replace('/[^A-Za-z0-9_-]+/', '-', (string) $booking->booking_code)
            : (string) $booking->getKey();

        return MailjetAttachment::fromHtml($html, 'invoice-'.$code.'.html');
    }
}
