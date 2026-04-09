<?php

namespace App\Services;

use App\Models\MuthowifBooking;
use App\Support\PhoneNumber;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class MuthowifBookingWhatsAppNotifier
{
    public function __construct(
        private readonly FonnteService $fonnte
    ) {}

    /**
     * Kirim WhatsApp ke nomor muthowif (Fonnte). Gagal API tidak mengganggu proses booking — hanya di-log.
     */
    public function notify(MuthowifBooking $booking): void
    {
        if (! config('services.fonnte.booking_notify_enabled', true)) {
            return;
        }

        $booking->loadMissing(['muthowifProfile.user', 'customer']);
        $profile = $booking->muthowifProfile;
        if ($profile === null) {
            return;
        }

        $target = $this->resolveTarget($profile->phone, $profile->id, $booking->id);
        if ($target === null) {
            return;
        }

        $customerName = $booking->customer?->name ?? 'Jamaah';
        $start = $booking->starts_on->format('d/m/Y');
        $end = $booking->ends_on->format('d/m/Y');
        $appName = config('app.name', 'BaytGo');
        $url = route('muthowif.bookings.index');

        $serviceLabel = $booking->service_type?->label() ?? 'Layanan';
        $countLine = '*Jumlah jemaah:* '.$booking->pilgrim_count;
        $lines = [
            "*{$appName}* — permintaan booking baru",
            '',
            "Ada jamaah *{$customerName}* yang mengajukan pendampingan.",
            '',
            "*Tanggal:* {$start} – {$end}",
            "*Layanan:* {$serviceLabel}",
            $countLine,
        ];

        $addOnLines = $booking->resolvedAddOns()->map(fn ($addon) => '• '.$addon->name)->all();
        if ($addOnLines !== []) {
            $lines[] = '*Add-on:*';
            $lines = array_merge($lines, $addOnLines);
        }

        $lines[] = '';
        $lines[] = '*Status:* Menunggu persetujuan Anda';
        $lines[] = '';
        $lines[] = '*Buka panel:*';
        $lines[] = $url;

        $this->sendToTarget($target, implode("\n", $lines), $booking->id);
    }

    /**
     * Setelah jamaah membayar — muthowif bisa mulai memproses layanan.
     */
    public function notifyPaymentSettled(MuthowifBooking $booking): void
    {
        if (! config('services.fonnte.payment_notify_enabled', true)) {
            return;
        }

        $booking->loadMissing(['muthowifProfile.user', 'customer']);
        $profile = $booking->muthowifProfile;
        if ($profile === null) {
            return;
        }

        $target = $this->resolveTarget($profile->phone, $profile->id, $booking->id);
        if ($target === null) {
            return;
        }

        $customerName = $booking->customer?->name ?? 'Jamaah';
        $start = $booking->starts_on->format('d/m/Y');
        $end = $booking->ends_on->format('d/m/Y');
        $appName = config('app.name', 'BaytGo');
        $url = route('muthowif.bookings.index');

        $lines = [
            "*{$appName}* — pembayaran lunas",
            '',
            "Booking dari *{$customerName}* sudah dibayar.",
            '',
            "*Tanggal layanan:* {$start} – {$end}",
            '*Status:* Siap Anda proses / dampingi sesuai kesepakatan.',
            '',
            '*Buka panel booking:*',
            $url,
        ];

        $this->sendToTarget($target, implode("\n", $lines), $booking->id);
    }

    /**
     * Saat booking disetujui muthowif, kirim WA ke customer.
     */
    public function notifyCustomerApproved(MuthowifBooking $booking): void
    {
        if (! config('services.fonnte.customer_booking_approved_notify_enabled', true)) {
            return;
        }

        $token = config('services.fonnte.token');
        if ($token === null || $token === '') {
            Log::debug('WhatsApp notify customer skipped: FONNTE_TOKEN kosong.');

            return;
        }

        $booking->loadMissing(['customer', 'muthowifProfile.user']);
        $customer = $booking->customer;
        if ($customer === null) {
            return;
        }

        $target = PhoneNumber::forFonnte($customer->phone);
        if ($target === null || $target === '') {
            Log::warning('WhatsApp notify customer skipped: nomor customer kosong atau tidak valid.', [
                'customer_id' => $customer->id,
                'booking_id' => $booking->id,
            ]);

            return;
        }

        $muthowifName = $booking->muthowifProfile?->user?->name ?? 'Muthowif';
        $start = $booking->starts_on->format('d/m/Y');
        $end = $booking->ends_on->format('d/m/Y');
        $appName = config('app.name', 'BaytGo');
        $url = route('bookings.show', $booking);

        $lines = [
            "*{$appName}* — booking disetujui",
            '',
            "Booking Anda dengan *{$muthowifName}* sudah disetujui.",
            '',
            "*Tanggal layanan:* {$start} – {$end}",
            '*Status:* Menunggu pembayaran',
            '',
            '*Lanjutkan pembayaran di:*',
            $url,
        ];

        $this->sendToTarget($target, implode("\n", $lines), $booking->id);
    }

    private function resolveTarget(string $phone, string $profileId, string $bookingId): ?string
    {
        $token = config('services.fonnte.token');
        if ($token === null || $token === '') {
            Log::debug('WhatsApp notify skipped: FONNTE_TOKEN kosong.');

            return null;
        }

        $target = PhoneNumber::forFonnte($phone);
        if ($target === null || $target === '') {
            Log::warning('WhatsApp notify skipped: nomor muthowif kosong atau tidak valid.', [
                'muthowif_profile_id' => $profileId,
                'booking_id' => $bookingId,
            ]);

            return null;
        }

        return $target;
    }

    private function sendToTarget(string $target, string $message, string $bookingId): void
    {
        try {
            $this->fonnte->sendText($target, $message);
        } catch (RuntimeException $e) {
            Log::warning('WhatsApp notify failed', [
                'booking_id' => $bookingId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
