<?php

namespace App\Services;

use App\Models\BookingRescheduleRequest;
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
        ];

        if (filled($booking->booking_code)) {
            $lines[] = '*Kode booking:* '.$booking->booking_code;
            $lines[] = '';
        }

        $lines[] = "*Tanggal:* {$start} - {$end}";
        $lines[] = "*Layanan:* {$serviceLabel}";
        $lines[] = $countLine;

        $addOnLines = $booking->resolvedAddOns()->map(fn ($addon) => '• '.$addon->name)->all();
        if ($addOnLines !== []) {
            $lines[] = '*Add-on:*';
            foreach ($addOnLines as $addOnLine) {
                $lines[] = $addOnLine;
            }
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
        ];

        if (filled($booking->booking_code)) {
            $lines[] = '*Kode booking:* '.$booking->booking_code;
            $lines[] = '';
        }

        $lines[] = "*Tanggal layanan:* {$start} - {$end}";
        $lines[] = '*Status:* Siap Anda proses / dampingi sesuai kesepakatan.';
        $lines[] = '';
        $lines[] = '*Buka panel booking:*';
        $lines[] = $url;

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
        ];

        if (filled($booking->booking_code)) {
            $lines[] = '*Kode booking:* '.$booking->booking_code;
            $lines[] = '';
        }

        $lines[] = "*Tanggal layanan:* {$start} - {$end}";
        $lines[] = '*Status:* Menunggu pembayaran';
        $lines[] = '';
        $lines[] = '*Lanjutkan pembayaran di:*';
        $lines[] = $url;

        $this->sendToTarget($target, implode("\n", $lines), $booking->id);
    }

    /**
     * Jamaah mengajukan reschedule — beri tahu muthowif (panel booking).
     */
    public function notifyMuthowifRescheduleRequested(MuthowifBooking $booking, BookingRescheduleRequest $rescheduleRequest): void
    {
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
        $prevStart = $rescheduleRequest->previous_starts_on->format('d/m/Y');
        $prevEnd = $rescheduleRequest->previous_ends_on->format('d/m/Y');
        $newStart = $rescheduleRequest->new_starts_on->format('d/m/Y');
        $newEnd = $rescheduleRequest->new_ends_on->format('d/m/Y');
        $appName = config('app.name', 'BaytGo');
        $url = route('muthowif.bookings.show', $booking);

        $lines = [
            "*{$appName}* — pengajuan reschedule",
            '',
            "*{$customerName}* mengajukan pergantian tanggal layanan.",
            '',
        ];

        if (filled($booking->booking_code)) {
            $lines[] = '*Kode booking:* '.$booking->booking_code;
            $lines[] = '';
        }

        $lines[] = "*Tanggal saat ini:* {$prevStart} - {$prevEnd}";
        $lines[] = "*Tanggal diajukan:* {$newStart} - {$newEnd}";
        $lines[] = '';
        $lines[] = '*Status:* Menunggu keputusan Anda';
        $lines[] = '';
        $lines[] = '*Buka detail booking:*';
        $lines[] = $url;

        $this->sendToTarget($target, implode("\n", $lines), $booking->id);
    }

    /**
     * Konfirmasi ke jamaah: pengajuan reschedule sudah masuk.
     */
    public function notifyCustomerRescheduleSubmitted(MuthowifBooking $booking, BookingRescheduleRequest $rescheduleRequest): void
    {
        $token = config('services.fonnte.token');
        if ($token === null || $token === '') {
            Log::debug('WhatsApp reschedule submitted skipped: FONNTE_TOKEN kosong.');

            return;
        }

        $booking->loadMissing(['customer', 'muthowifProfile.user']);
        $customer = $booking->customer;
        if ($customer === null) {
            return;
        }

        $target = PhoneNumber::forFonnte($customer->phone);
        if ($target === null || $target === '') {
            Log::warning('WhatsApp reschedule submitted skipped: nomor customer kosong atau tidak valid.', [
                'customer_id' => $customer->id,
                'booking_id' => $booking->id,
            ]);

            return;
        }

        $muthowifName = $booking->muthowifProfile?->user?->name ?? 'Muthowif';
        $newStart = $rescheduleRequest->new_starts_on->format('d/m/Y');
        $newEnd = $rescheduleRequest->new_ends_on->format('d/m/Y');
        $appName = config('app.name', 'BaytGo');
        $url = route('bookings.show', $booking);

        $lines = [
            "*{$appName}* — pengajuan reschedule",
            '',
            'Pengajuan pergantian tanggal Anda sudah kami teruskan ke *'.$muthowifName.'*.',
            '',
        ];

        if (filled($booking->booking_code)) {
            $lines[] = '*Kode booking:* '.$booking->booking_code;
            $lines[] = '';
        }

        $lines[] = "*Tanggal yang diajukan:* {$newStart} - {$newEnd}";
        $lines[] = '';
        $lines[] = 'Anda akan mendapat notifikasi lagi setelah muthowif memutuskan.';
        $lines[] = '';
        $lines[] = '*Lihat detail booking:*';
        $lines[] = $url;

        $this->sendToTarget($target, implode("\n", $lines), $booking->id);
    }

    /**
     * Muthowif menyetujui reschedule — beri tahu jamaah (tanggal booking sudah diperbarui).
     */
    public function notifyCustomerRescheduleApproved(MuthowifBooking $booking, BookingRescheduleRequest $rescheduleRequest): void
    {
        $token = config('services.fonnte.token');
        if ($token === null || $token === '') {
            Log::debug('WhatsApp reschedule approved skipped: FONNTE_TOKEN kosong.');

            return;
        }

        $booking->loadMissing(['customer', 'muthowifProfile.user']);
        $customer = $booking->customer;
        if ($customer === null) {
            return;
        }

        $target = PhoneNumber::forFonnte($customer->phone);
        if ($target === null || $target === '') {
            Log::warning('WhatsApp reschedule approved skipped: nomor customer kosong atau tidak valid.', [
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
            "*{$appName}* — reschedule disetujui",
            '',
            "Muthowif *{$muthowifName}* menyetujui pergantian tanggal layanan Anda.",
            '',
        ];

        if (filled($booking->booking_code)) {
            $lines[] = '*Kode booking:* '.$booking->booking_code;
            $lines[] = '';
        }

        $lines[] = "*Tanggal layanan baru:* {$start} - {$end}";
        $lines[] = '';
        $lines[] = '*Lihat detail booking:*';
        $lines[] = $url;

        $note = $rescheduleRequest->muthowif_note;
        if (filled($note)) {
            $lines[] = '';
            $lines[] = '*Catatan muthowif:*';
            $lines[] = $note;
        }

        $this->sendToTarget($target, implode("\n", $lines), $booking->id);
    }

    /**
     * Muthowif menolak reschedule — jamaah tetap pada jadwal lama.
     */
    public function notifyCustomerRescheduleRejected(MuthowifBooking $booking, BookingRescheduleRequest $rescheduleRequest): void
    {
        $token = config('services.fonnte.token');
        if ($token === null || $token === '') {
            Log::debug('WhatsApp reschedule rejected skipped: FONNTE_TOKEN kosong.');

            return;
        }

        $booking->loadMissing(['customer', 'muthowifProfile.user']);
        $customer = $booking->customer;
        if ($customer === null) {
            return;
        }

        $target = PhoneNumber::forFonnte($customer->phone);
        if ($target === null || $target === '') {
            Log::warning('WhatsApp reschedule rejected skipped: nomor customer kosong atau tidak valid.', [
                'customer_id' => $customer->id,
                'booking_id' => $booking->id,
            ]);

            return;
        }

        $muthowifName = $booking->muthowifProfile?->user?->name ?? 'Muthowif';
        $unchangedStart = $booking->starts_on->format('d/m/Y');
        $unchangedEnd = $booking->ends_on->format('d/m/Y');
        $appName = config('app.name', 'BaytGo');
        $url = route('bookings.show', $booking);

        $lines = [
            "*{$appName}* — pengajuan reschedule ditolak",
            '',
            "Muthowif *{$muthowifName}* tidak menyetujui pergantian tanggal.",
            '',
        ];

        if (filled($booking->booking_code)) {
            $lines[] = '*Kode booking:* '.$booking->booking_code;
            $lines[] = '';
        }

        $lines[] = "*Tetap berlaku:* {$unchangedStart} - {$unchangedEnd}";
        $lines[] = '';
        $lines[] = '*Lihat detail booking:*';
        $lines[] = $url;

        $note = $rescheduleRequest->muthowif_note;
        if (filled($note)) {
            $lines[] = '';
            $lines[] = '*Catatan muthowif:*';
            $lines[] = $note;
        }

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
