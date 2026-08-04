<?php

namespace App\Support;

use App\Models\MuthowifBooking;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

final class BookingInvoiceAttachment
{
    /**
     * @return array{ContentType: string, Filename: string, Base64Content: string}
     */
    public static function forBooking(MuthowifBooking $booking): array
    {
        $booking->loadMissing(['customer', 'muthowifProfile.user', 'supportPackage']);
        $payment = $booking->settledBookingPayment();

        $pdf = Pdf::loadView('emails.invoice-pdf', [
            'booking' => $booking,
            'payment' => $payment,
            'logoDataUri' => self::logoDataUri(),
        ])->setPaper('a4');

        $code = filled($booking->booking_code)
            ? preg_replace('/[^A-Za-z0-9_-]+/', '-', (string) $booking->booking_code)
            : (string) $booking->getKey();

        return MailjetAttachment::fromBinary(
            $pdf->output(),
            'invoice-'.$code.'.pdf',
            'application/pdf',
        );
    }

    private static function logoDataUri(): ?string
    {
        $path = SiteBrand::logoStoragePath();
        if ($path === null || ! Storage::disk('public')->exists($path)) {
            return null;
        }

        $binary = Storage::disk('public')->get($path);
        if ($binary === null || $binary === '') {
            return null;
        }

        $mime = Storage::disk('public')->mimeType($path) ?: 'image/png';

        return 'data:'.$mime.';base64,'.base64_encode($binary);
    }
}
