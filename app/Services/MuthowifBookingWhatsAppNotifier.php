<?php

namespace App\Services;

use App\Models\BookingRefundRequest;
use App\Models\BookingRescheduleRequest;
use App\Models\MuthowifBooking;
use App\Models\MuthowifWithdrawal;
use App\Support\IndonesianNumber;
use App\Support\PhoneNumber;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
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

    /**
     * Admin menandai refund selesai + bukti transfer — kirim WA ke jamaah dengan lampiran.
     */
    public function notifyCustomerRefundTransferCompleted(BookingRefundRequest $refund): void
    {
        if (! config('services.fonnte.refund_transfer_proof_notify_enabled', true)) {
            return;
        }

        $token = config('services.fonnte.token');
        if ($token === null || $token === '') {
            Log::debug('WhatsApp refund proof skipped: FONNTE_TOKEN kosong.');

            return;
        }

        if ($refund->transfer_proof_path === null || $refund->transfer_proof_path === '') {
            return;
        }

        $refund->loadMissing(['muthowifBooking', 'customer']);
        $booking = $refund->muthowifBooking;
        $customer = $refund->customer;
        if ($customer === null || $booking === null) {
            return;
        }

        $target = PhoneNumber::forFonnte($customer->phone);
        if ($target === null || $target === '') {
            Log::warning('WhatsApp refund proof skipped: nomor customer kosong atau tidak valid.', [
                'customer_id' => $customer->id,
                'refund_id' => $refund->id,
            ]);

            return;
        }

        $locale = $this->localeForUser($customer->locale);
        $proofUrl = url(Storage::disk('public')->url($refund->transfer_proof_path));
        $ext = strtolower((string) pathinfo($refund->transfer_proof_path, PATHINFO_EXTENSION));
        $filename = $ext === 'pdf' ? basename($refund->transfer_proof_path) : null;
        $amountFmt = IndonesianNumber::formatThousands((string) (int) round((float) $refund->net_refund_customer));
        $appName = config('app.name', 'BaytGo');
        $detailUrl = route('bookings.show', $booking);

        $this->withLocale($locale, function () use ($refund, $booking, $target, $locale, $proofUrl, $filename, $amountFmt, $appName, $detailUrl): void {
            $lines = [
                __('whatsapp.customer.refund_transfer_done.headline', ['app' => $appName], $locale),
                '',
                __('whatsapp.customer.refund_transfer_done.body', [], $locale),
                '',
                __('whatsapp.customer.refund_transfer_done.amount', ['amount' => $amountFmt], $locale),
            ];

            if (filled($booking->booking_code)) {
                $lines[] = __('whatsapp.customer.refund_transfer_done.booking_code', ['code' => $booking->booking_code], $locale);
            }

            $lines[] = '';
            $lines[] = __('whatsapp.customer.refund_transfer_done.view_detail', [], $locale);
            $lines[] = $detailUrl;
            $lines[] = '';
            $lines[] = __('whatsapp.customer.refund_transfer_done.attachment_caption', [], $locale);

            $message = implode("\n", $lines);
            $this->sendFileProofToTarget($target, $message, $proofUrl, $filename, (string) $refund->getKey());
        });
    }

    /**
     * Admin menandai withdraw selesai + bukti — kirim WA ke muthowif dengan lampiran.
     */
    public function notifyMuthowifWithdrawalTransferCompleted(MuthowifWithdrawal $withdrawal): void
    {
        if (! config('services.fonnte.withdrawal_transfer_proof_notify_enabled', true)) {
            return;
        }

        $token = config('services.fonnte.token');
        if ($token === null || $token === '') {
            Log::debug('WhatsApp withdrawal proof skipped: FONNTE_TOKEN kosong.');

            return;
        }

        if ($withdrawal->transfer_proof_path === null || $withdrawal->transfer_proof_path === '') {
            return;
        }

        $withdrawal->loadMissing(['muthowifProfile.user']);
        $profile = $withdrawal->muthowifProfile;
        if ($profile === null) {
            return;
        }

        $target = $this->resolveTarget($profile->phone, $profile->id, (string) $withdrawal->getKey());
        if ($target === null) {
            return;
        }

        $locale = $this->localeForUser($profile->user?->locale);
        $proofUrl = url(Storage::disk('public')->url($withdrawal->transfer_proof_path));
        $ext = strtolower((string) pathinfo($withdrawal->transfer_proof_path, PATHINFO_EXTENSION));
        $filename = $ext === 'pdf' ? basename($withdrawal->transfer_proof_path) : null;
        $amountFmt = IndonesianNumber::formatThousands((string) (int) round((float) $withdrawal->amount));
        $appName = config('app.name', 'BaytGo');
        $panelUrl = route('muthowif.withdrawals.index');

        $this->withLocale($locale, function () use ($withdrawal, $target, $locale, $proofUrl, $filename, $amountFmt, $appName, $panelUrl): void {
            $lines = [
                __('whatsapp.muthowif.withdrawal_transfer_done.headline', ['app' => $appName], $locale),
                '',
                __('whatsapp.muthowif.withdrawal_transfer_done.body', [], $locale),
                '',
                __('whatsapp.muthowif.withdrawal_transfer_done.amount', ['amount' => $amountFmt], $locale),
                '',
                __('whatsapp.muthowif.withdrawal_transfer_done.open_panel', [], $locale),
                $panelUrl,
                '',
                __('whatsapp.muthowif.withdrawal_transfer_done.attachment_caption', [], $locale),
            ];

            $message = implode("\n", $lines);
            $this->sendFileProofToTarget($target, $message, $proofUrl, $filename, (string) $withdrawal->getKey());
        });
    }

    private function sendFileProofToTarget(string $target, string $message, string $proofPublicUrl, ?string $filenameForNonImage, string $contextId): void
    {
        try {
            $this->fonnte->sendMessageWithPublicFileUrl($target, $message, $proofPublicUrl, $filenameForNonImage);
        } catch (RuntimeException $e) {
            Log::warning('WhatsApp notify with attachment failed', [
                'context_id' => $contextId,
                'error' => $e->getMessage(),
            ]);
        }
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
