<?php

namespace App\Services\Emergency;

use App\Models\BookingReplacementOffer;
use App\Services\FonnteService;
use App\Support\IntlPhone;
use Illuminate\Support\Facades\Log;
use RuntimeException;

final class EmergencyWhatsAppNotifier
{
    public function __construct(
        private readonly FonnteService $fonnte,
    ) {}

    public function notifyCustomerOfCandidate(BookingReplacementOffer $offer): void
    {
        if (! config('services.fonnte.emergency_candidate_notify_enabled', true)) {
            return;
        }

        $offer->loadMissing([
            'muthowifProfile.user',
            'report.muthowifBooking.customer',
        ]);

        $booking = $offer->report?->muthowifBooking;
        $customer = $booking?->customer;
        if ($booking === null || $customer === null) {
            return;
        }

        $fonnteDial = $this->resolveCustomerDial($customer->phone, (string) $customer->getKey(), (string) $booking->getKey());
        if ($fonnteDial === null) {
            return;
        }

        $locale = $this->localeForUser($customer->locale);
        $muthowifName = $offer->muthowifProfile?->user?->name ?? __('whatsapp.fallback_muthowif', [], $locale);

        $this->withLocale($locale, function () use ($booking, $fonnteDial, $locale, $muthowifName): void {
            $start = $booking->starts_on->format('d/m/Y');
            $end = $booking->ends_on->format('d/m/Y');
            $appName = config('app.name', 'BaytGo');
            $url = route('bookings.show', $booking);

            $lines = [
                __('whatsapp.customer.emergency_candidate.headline', ['app' => $appName], $locale),
                '',
                __('whatsapp.customer.emergency_candidate.body', ['muthowif' => $muthowifName], $locale),
                '',
            ];

            if (filled($booking->booking_code)) {
                $lines[] = __('whatsapp.customer.emergency_candidate.booking_code', ['code' => $booking->booking_code], $locale);
                $lines[] = '';
            }

            $lines[] = __('whatsapp.customer.emergency_candidate.service_dates', ['start' => $start, 'end' => $end], $locale);
            $lines[] = __('whatsapp.customer.emergency_candidate.action', [], $locale);
            $lines[] = '';
            $lines[] = __('whatsapp.customer.emergency_candidate.view_detail', [], $locale);
            $lines[] = $url;

            $this->sendToTarget($fonnteDial, implode("\n", $lines), (string) $booking->getKey());
        });
    }

    public function notifyMuthowifSelected(BookingReplacementOffer $offer): void
    {
        if (! config('services.fonnte.emergency_selection_notify_enabled', true)) {
            return;
        }

        $this->notifyMuthowifSelectionResult($offer, selected: true);
    }

    public function notifyMuthowifNotSelected(BookingReplacementOffer $offer): void
    {
        if (! config('services.fonnte.emergency_selection_notify_enabled', true)) {
            return;
        }

        $this->notifyMuthowifSelectionResult($offer, selected: false);
    }

    private function notifyMuthowifSelectionResult(BookingReplacementOffer $offer, bool $selected): void
    {
        $offer->loadMissing([
            'muthowifProfile.user',
            'report.muthowifBooking.customer',
        ]);

        $booking = $offer->report?->muthowifBooking;
        $profile = $offer->muthowifProfile;
        if ($booking === null || $profile === null) {
            return;
        }

        $fonnteDial = $this->resolveMuthowifDial($profile->phone, (string) $profile->getKey(), (string) $booking->getKey());
        if ($fonnteDial === null) {
            return;
        }

        $locale = $this->localeForUser($profile->user?->locale);
        $customerName = $booking->customer?->name ?? __('whatsapp.fallback_pilgrim', [], $locale);
        $key = $selected ? 'selected' : 'not_selected';

        $this->withLocale($locale, function () use ($booking, $fonnteDial, $locale, $customerName, $key, $selected): void {
            $start = $booking->starts_on->format('d/m/Y');
            $end = $booking->ends_on->format('d/m/Y');
            $appName = config('app.name', 'BaytGo');
            $url = $selected
                ? route('muthowif.bookings.index')
                : route('muthowif.emergency-offers.index');

            $lines = [
                __("whatsapp.muthowif.emergency_selection.{$key}.headline", ['app' => $appName], $locale),
                '',
                __("whatsapp.muthowif.emergency_selection.{$key}.body", ['customer' => $customerName], $locale),
                '',
            ];

            if (filled($booking->booking_code)) {
                $lines[] = __("whatsapp.muthowif.emergency_selection.{$key}.booking_code", ['code' => $booking->booking_code], $locale);
                $lines[] = '';
            }

            $lines[] = __("whatsapp.muthowif.emergency_selection.{$key}.service_dates", ['start' => $start, 'end' => $end], $locale);
            $lines[] = '';
            $lines[] = __("whatsapp.muthowif.emergency_selection.{$key}.open", [], $locale);
            $lines[] = $url;

            $this->sendToTarget($fonnteDial, implode("\n", $lines), (string) $booking->getKey());
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

    /**
     * @return array{target: string, country_calling_code: string}|null
     */
    private function resolveMuthowifDial(string $phone, string $profileId, string $bookingId): ?array
    {
        $token = config('services.fonnte.token');
        if ($token === null || $token === '') {
            Log::debug('WhatsApp emergency notify skipped: FONNTE_TOKEN kosong.');

            return null;
        }

        $dial = IntlPhone::fonnteDial($phone);
        if ($dial === null) {
            Log::warning('WhatsApp emergency notify skipped: nomor muthowif kosong atau tidak valid.', [
                'muthowif_profile_id' => $profileId,
                'booking_id' => $bookingId,
            ]);

            return null;
        }

        return $dial;
    }

    /**
     * @return array{target: string, country_calling_code: string}|null
     */
    private function resolveCustomerDial(string $phone, string $customerId, string $bookingId): ?array
    {
        $token = config('services.fonnte.token');
        if ($token === null || $token === '') {
            Log::debug('WhatsApp emergency notify skipped: FONNTE_TOKEN kosong.');

            return null;
        }

        $dial = IntlPhone::fonnteDial($phone);
        if ($dial === null) {
            Log::warning('WhatsApp emergency notify skipped: nomor customer kosong atau tidak valid.', [
                'customer_id' => $customerId,
                'booking_id' => $bookingId,
            ]);

            return null;
        }

        return $dial;
    }

    /**
     * @param  array{target: string, country_calling_code: string}  $fonnteDial
     */
    private function sendToTarget(array $fonnteDial, string $message, string $bookingId): void
    {
        try {
            $this->fonnte->sendText(
                $fonnteDial['target'],
                $message,
                $fonnteDial['country_calling_code'],
            );
        } catch (RuntimeException $e) {
            Log::warning('WhatsApp emergency notify failed', [
                'booking_id' => $bookingId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
