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

        $locale = $this->localeForUser($profile->user?->locale);

        $this->withLocale($locale, function () use ($booking, $target, $locale): void {
            $customerName = $booking->customer?->name ?? __('whatsapp.fallback_pilgrim', [], $locale);
            $start = $booking->starts_on->format('d/m/Y');
            $end = $booking->ends_on->format('d/m/Y');
            $appName = config('app.name', 'BaytGo');
            $url = route('muthowif.bookings.index');

            $serviceLabel = $booking->service_type?->label() ?? __('whatsapp.fallback_service', [], $locale);
            $countLine = __('whatsapp.muthowif.new_booking.pilgrim_count', ['count' => $booking->pilgrim_count], $locale);
            $lines = [
                __('whatsapp.muthowif.new_booking.headline', ['app' => $appName], $locale),
                '',
                __('whatsapp.muthowif.new_booking.body', ['customer' => $customerName], $locale),
                '',
            ];

            if (filled($booking->booking_code)) {
                $lines[] = __('whatsapp.muthowif.new_booking.booking_code', ['code' => $booking->booking_code], $locale);
                $lines[] = '';
            }

            $lines[] = __('whatsapp.muthowif.new_booking.dates', ['start' => $start, 'end' => $end], $locale);
            $lines[] = __('whatsapp.muthowif.new_booking.service', ['service' => $serviceLabel], $locale);
            $lines[] = $countLine;

            $addOnLines = $booking->resolvedAddOns()->map(fn ($addon) => __('whatsapp.muthowif.new_booking.addon_bullet', ['name' => $addon->name], $locale))->all();
            if ($addOnLines !== []) {
                $lines[] = __('whatsapp.muthowif.new_booking.addon_heading', [], $locale);
                foreach ($addOnLines as $addOnLine) {
                    $lines[] = $addOnLine;
                }
            }

            $lines[] = '';
            $lines[] = __('whatsapp.muthowif.new_booking.status', [], $locale);
            $lines[] = '';
            $lines[] = __('whatsapp.muthowif.new_booking.open_panel', [], $locale);
            $lines[] = $url;

            $this->sendToTarget($target, implode("\n", $lines), $booking->id);
        });
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

        $locale = $this->localeForUser($profile->user?->locale);

        $this->withLocale($locale, function () use ($booking, $target, $locale): void {
            $customerName = $booking->customer?->name ?? __('whatsapp.fallback_pilgrim', [], $locale);
            $start = $booking->starts_on->format('d/m/Y');
            $end = $booking->ends_on->format('d/m/Y');
            $appName = config('app.name', 'BaytGo');
            $url = route('muthowif.bookings.index');

            $lines = [
                __('whatsapp.muthowif.payment_settled.headline', ['app' => $appName], $locale),
                '',
                __('whatsapp.muthowif.payment_settled.body', ['customer' => $customerName], $locale),
                '',
            ];

            if (filled($booking->booking_code)) {
                $lines[] = __('whatsapp.muthowif.payment_settled.booking_code', ['code' => $booking->booking_code], $locale);
                $lines[] = '';
            }

            $lines[] = __('whatsapp.muthowif.payment_settled.service_dates', ['start' => $start, 'end' => $end], $locale);
            $lines[] = __('whatsapp.muthowif.payment_settled.status', [], $locale);
            $lines[] = '';
            $lines[] = __('whatsapp.muthowif.payment_settled.open', [], $locale);
            $lines[] = $url;

            $this->sendToTarget($target, implode("\n", $lines), $booking->id);
        });
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

        $locale = $this->localeForUser($customer->locale);

        $this->withLocale($locale, function () use ($booking, $target, $locale): void {
            $muthowifName = $booking->muthowifProfile?->user?->name ?? __('whatsapp.fallback_muthowif', [], $locale);
            $start = $booking->starts_on->format('d/m/Y');
            $end = $booking->ends_on->format('d/m/Y');
            $appName = config('app.name', 'BaytGo');
            $url = route('bookings.show', $booking);

            $lines = [
                __('whatsapp.customer.approved.headline', ['app' => $appName], $locale),
                '',
                __('whatsapp.customer.approved.body', ['muthowif' => $muthowifName], $locale),
                '',
            ];

            if (filled($booking->booking_code)) {
                $lines[] = __('whatsapp.customer.approved.booking_code', ['code' => $booking->booking_code], $locale);
                $lines[] = '';
            }

            $lines[] = __('whatsapp.customer.approved.service_dates', ['start' => $start, 'end' => $end], $locale);
            $lines[] = __('whatsapp.customer.approved.status', [], $locale);
            $lines[] = '';
            $lines[] = __('whatsapp.customer.approved.pay_at', [], $locale);
            $lines[] = $url;

            $this->sendToTarget($target, implode("\n", $lines), $booking->id);
        });
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

        $locale = $this->localeForUser($profile->user?->locale);

        $this->withLocale($locale, function () use ($booking, $rescheduleRequest, $target, $locale): void {
            $customerName = $booking->customer?->name ?? __('whatsapp.fallback_pilgrim', [], $locale);
            $prevStart = $rescheduleRequest->previous_starts_on->format('d/m/Y');
            $prevEnd = $rescheduleRequest->previous_ends_on->format('d/m/Y');
            $newStart = $rescheduleRequest->new_starts_on->format('d/m/Y');
            $newEnd = $rescheduleRequest->new_ends_on->format('d/m/Y');
            $appName = config('app.name', 'BaytGo');
            $url = route('muthowif.bookings.show', $booking);

            $lines = [
                __('whatsapp.muthowif.reschedule_requested.headline', ['app' => $appName], $locale),
                '',
                __('whatsapp.muthowif.reschedule_requested.body', ['customer' => $customerName], $locale),
                '',
            ];

            if (filled($booking->booking_code)) {
                $lines[] = __('whatsapp.muthowif.reschedule_requested.booking_code', ['code' => $booking->booking_code], $locale);
                $lines[] = '';
            }

            $lines[] = __('whatsapp.muthowif.reschedule_requested.current', ['start' => $prevStart, 'end' => $prevEnd], $locale);
            $lines[] = __('whatsapp.muthowif.reschedule_requested.requested', ['start' => $newStart, 'end' => $newEnd], $locale);
            $lines[] = '';
            $lines[] = __('whatsapp.muthowif.reschedule_requested.status', [], $locale);
            $lines[] = '';
            $lines[] = __('whatsapp.muthowif.reschedule_requested.open_detail', [], $locale);
            $lines[] = $url;

            $this->sendToTarget($target, implode("\n", $lines), $booking->id);
        });
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

        $locale = $this->localeForUser($customer->locale);

        $this->withLocale($locale, function () use ($booking, $rescheduleRequest, $target, $locale): void {
            $muthowifName = $booking->muthowifProfile?->user?->name ?? __('whatsapp.fallback_muthowif', [], $locale);
            $newStart = $rescheduleRequest->new_starts_on->format('d/m/Y');
            $newEnd = $rescheduleRequest->new_ends_on->format('d/m/Y');
            $appName = config('app.name', 'BaytGo');
            $url = route('bookings.show', $booking);

            $lines = [
                __('whatsapp.customer.reschedule_submitted.headline', ['app' => $appName], $locale),
                '',
                __('whatsapp.customer.reschedule_submitted.body', ['muthowif' => $muthowifName], $locale),
                '',
            ];

            if (filled($booking->booking_code)) {
                $lines[] = __('whatsapp.customer.reschedule_submitted.booking_code', ['code' => $booking->booking_code], $locale);
                $lines[] = '';
            }

            $lines[] = __('whatsapp.customer.reschedule_submitted.requested_dates', ['start' => $newStart, 'end' => $newEnd], $locale);
            $lines[] = '';
            $lines[] = __('whatsapp.customer.reschedule_submitted.followup', [], $locale);
            $lines[] = '';
            $lines[] = __('whatsapp.customer.reschedule_submitted.view_detail', [], $locale);
            $lines[] = $url;

            $this->sendToTarget($target, implode("\n", $lines), $booking->id);
        });
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

        $locale = $this->localeForUser($customer->locale);

        $this->withLocale($locale, function () use ($booking, $rescheduleRequest, $target, $locale): void {
            $muthowifName = $booking->muthowifProfile?->user?->name ?? __('whatsapp.fallback_muthowif', [], $locale);
            $start = $booking->starts_on->format('d/m/Y');
            $end = $booking->ends_on->format('d/m/Y');
            $appName = config('app.name', 'BaytGo');
            $url = route('bookings.show', $booking);

            $lines = [
                __('whatsapp.customer.reschedule_approved.headline', ['app' => $appName], $locale),
                '',
                __('whatsapp.customer.reschedule_approved.body', ['muthowif' => $muthowifName], $locale),
                '',
            ];

            if (filled($booking->booking_code)) {
                $lines[] = __('whatsapp.customer.reschedule_approved.booking_code', ['code' => $booking->booking_code], $locale);
                $lines[] = '';
            }

            $lines[] = __('whatsapp.customer.reschedule_approved.new_dates', ['start' => $start, 'end' => $end], $locale);
            $lines[] = '';
            $lines[] = __('whatsapp.customer.reschedule_approved.view_detail', [], $locale);
            $lines[] = $url;

            $note = $rescheduleRequest->muthowif_note;
            if (filled($note)) {
                $lines[] = '';
                $lines[] = __('whatsapp.customer.reschedule_approved.note_heading', [], $locale);
                $lines[] = $note;
            }

            $this->sendToTarget($target, implode("\n", $lines), $booking->id);
        });
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

        $locale = $this->localeForUser($customer->locale);

        $this->withLocale($locale, function () use ($booking, $rescheduleRequest, $target, $locale): void {
            $muthowifName = $booking->muthowifProfile?->user?->name ?? __('whatsapp.fallback_muthowif', [], $locale);
            $unchangedStart = $booking->starts_on->format('d/m/Y');
            $unchangedEnd = $booking->ends_on->format('d/m/Y');
            $appName = config('app.name', 'BaytGo');
            $url = route('bookings.show', $booking);

            $lines = [
                __('whatsapp.customer.reschedule_rejected.headline', ['app' => $appName], $locale),
                '',
                __('whatsapp.customer.reschedule_rejected.body', ['muthowif' => $muthowifName], $locale),
                '',
            ];

            if (filled($booking->booking_code)) {
                $lines[] = __('whatsapp.customer.reschedule_rejected.booking_code', ['code' => $booking->booking_code], $locale);
                $lines[] = '';
            }

            $lines[] = __('whatsapp.customer.reschedule_rejected.still', ['start' => $unchangedStart, 'end' => $unchangedEnd], $locale);
            $lines[] = '';
            $lines[] = __('whatsapp.customer.reschedule_rejected.view_detail', [], $locale);
            $lines[] = $url;

            $note = $rescheduleRequest->muthowif_note;
            if (filled($note)) {
                $lines[] = '';
                $lines[] = __('whatsapp.customer.reschedule_rejected.note_heading', [], $locale);
                $lines[] = $note;
            }

            $this->sendToTarget($target, implode("\n", $lines), $booking->id);
        });
    }

    private function localeForUser(?string $locale): string
    {
        if (filled($locale)) {
            return $locale;
        }

        return config('app.locale');
    }

    /**
     * @param  callable():void  $callback
     */
    private function withLocale(string $locale, callable $callback): void
    {
        $previous = app()->getLocale();
        try {
            app()->setLocale($locale);
            $callback();
        } finally {
            app()->setLocale($previous);
        }
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
