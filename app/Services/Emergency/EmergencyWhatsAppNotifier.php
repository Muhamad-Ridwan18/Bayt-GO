<?php

namespace App\Services\Emergency;

use App\Jobs\SendWhatsAppTextJob;
use App\Models\BookingEmergencyReport;
use App\Models\BookingReplacementOffer;
use App\Support\IntlPhone;
use App\Support\WhatsAppNotifySettings;
use Illuminate\Support\Facades\Log;

final class EmergencyWhatsAppNotifier
{
    public function notifyAdminsOfSubmittedReport(BookingEmergencyReport $report): void
    {
        if (! WhatsAppNotifySettings::enabled('emergency_admin_report')) {
            return;
        }

        $numbers = WhatsAppNotifySettings::adminNumbers();
        if (! is_array($numbers) || $numbers === []) {
            return;
        }

        $report->loadMissing([
            'reportedBy',
            'muthowifBooking.customer',
            'muthowifBooking.muthowifProfile.user',
        ]);

        $booking = $report->muthowifBooking;
        if ($booking === null) {
            return;
        }

        $locale = config('app.locale');
        $customerName = $booking->customer?->name ?? $report->reportedBy?->name ?? __('whatsapp.fallback_pilgrim', [], $locale);
        $muthowifName = $booking->muthowifProfile?->user?->name ?? __('whatsapp.fallback_muthowif', [], $locale);

        $message = $this->withLocale($locale, function () use ($report, $booking, $locale, $customerName, $muthowifName): string {
            $start = $booking->starts_on->format('d/m/Y');
            $end = $booking->ends_on->format('d/m/Y');
            $appName = config('app.name', 'BaytGo');
            $url = route('admin.emergency.show', $report);

            $lines = [
                __('whatsapp.admin.emergency_report_submitted.headline', ['app' => $appName], $locale),
                '',
                __('whatsapp.admin.emergency_report_submitted.body', ['customer' => $customerName], $locale),
                '',
            ];

            if (filled($booking->booking_code)) {
                $lines[] = __('whatsapp.admin.emergency_report_submitted.booking_code', ['code' => $booking->booking_code], $locale);
            }

            $lines[] = __('whatsapp.admin.emergency_report_submitted.case', ['case' => $report->case_type->label()], $locale);
            $lines[] = __('whatsapp.admin.emergency_report_submitted.muthowif', ['muthowif' => $muthowifName], $locale);
            $lines[] = __('whatsapp.admin.emergency_report_submitted.service_dates', ['start' => $start, 'end' => $end], $locale);

            if (filled($report->description)) {
                $lines[] = '';
                $lines[] = __('whatsapp.admin.emergency_report_submitted.description_heading', [], $locale);
                $lines[] = $report->description;
            }

            $lines[] = '';
            $lines[] = __('whatsapp.admin.emergency_report_submitted.open', [], $locale);
            $lines[] = $url;

            return implode("\n", $lines);
        });

        $this->sendToPhoneNumbers($numbers, $message, (string) $report->getKey());
    }

    /**
     * @param  'under_review'|'verified'|'rejected'  $statusKey
     */
    public function notifyCustomerOfReportStatus(BookingEmergencyReport $report, string $statusKey): void
    {
        if (! WhatsAppNotifySettings::enabled('emergency_customer_report')) {
            return;
        }

        if (! in_array($statusKey, ['under_review', 'verified', 'rejected'], true)) {
            return;
        }

        $report->loadMissing([
            'muthowifBooking.customer',
            'muthowifBooking.muthowifProfile.user',
        ]);

        $booking = $report->muthowifBooking;
        $customer = $booking?->customer;
        if ($booking === null || $customer === null) {
            return;
        }

        $fonnteDial = $this->resolveCustomerDial($customer->phone, (string) $customer->getKey(), (string) $booking->getKey());
        if ($fonnteDial === null) {
            return;
        }

        $locale = $this->localeForUser($customer->locale);
        $langKey = "whatsapp.customer.emergency_{$statusKey}";

        $this->withLocale($locale, function () use ($report, $booking, $fonnteDial, $locale, $langKey, $statusKey): void {
            $start = $booking->starts_on->format('d/m/Y');
            $end = $booking->ends_on->format('d/m/Y');
            $appName = config('app.name', 'BaytGo');
            $url = route('bookings.show', $booking);

            $lines = [
                __($langKey.'.headline', ['app' => $appName], $locale),
                '',
                __($langKey.'.body', [], $locale),
                '',
            ];

            if (filled($booking->booking_code)) {
                $lines[] = __($langKey.'.booking_code', ['code' => $booking->booking_code], $locale);
                $lines[] = '';
            }

            $lines[] = __($langKey.'.service_dates', ['start' => $start, 'end' => $end], $locale);

            if ($statusKey === 'verified') {
                $lines[] = '';
                $lines[] = __($langKey.'.hint', [], $locale);
            }

            if ($statusKey === 'rejected' && filled($report->admin_note)) {
                $lines[] = '';
                $lines[] = __($langKey.'.note_heading', [], $locale);
                $lines[] = $report->admin_note;
            }

            $lines[] = '';
            $lines[] = __($langKey.'.view_detail', [], $locale);
            $lines[] = $url;

            $this->sendToTarget($fonnteDial, implode("\n", $lines), (string) $booking->getKey());
        });
    }

    public function notifyCustomerOfCandidate(BookingReplacementOffer $offer): void
    {
        if (! WhatsAppNotifySettings::enabled('emergency_candidate')) {
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

    public function notifyMuthowifOfReplacementOffer(BookingReplacementOffer $offer): void
    {
        if (! WhatsAppNotifySettings::enabled('emergency_offer')) {
            return;
        }

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
        $serviceLabel = $booking->service_type?->label() ?? __('whatsapp.fallback_service', [], $locale);

        $this->withLocale($locale, function () use ($booking, $fonnteDial, $locale, $customerName, $serviceLabel): void {
            $start = $booking->starts_on->format('d/m/Y');
            $end = $booking->ends_on->format('d/m/Y');
            $appName = config('app.name', 'BaytGo');
            $url = route('muthowif.emergency-offers.index');

            $lines = [
                __('whatsapp.muthowif.emergency_replacement_offer.headline', ['app' => $appName], $locale),
                '',
                __('whatsapp.muthowif.emergency_replacement_offer.body', ['customer' => $customerName], $locale),
                '',
            ];

            if (filled($booking->booking_code)) {
                $lines[] = __('whatsapp.muthowif.emergency_replacement_offer.booking_code', ['code' => $booking->booking_code], $locale);
                $lines[] = '';
            }

            $lines[] = __('whatsapp.muthowif.emergency_replacement_offer.service_dates', ['start' => $start, 'end' => $end], $locale);
            $lines[] = __('whatsapp.muthowif.emergency_replacement_offer.service', ['service' => $serviceLabel], $locale);
            $lines[] = __('whatsapp.muthowif.emergency_replacement_offer.pilgrim_count', ['count' => $booking->pilgrim_count], $locale);
            $lines[] = '';
            $lines[] = __('whatsapp.muthowif.emergency_replacement_offer.action', [], $locale);
            $lines[] = '';
            $lines[] = __('whatsapp.muthowif.emergency_replacement_offer.open', [], $locale);
            $lines[] = $url;

            $this->sendToTarget($fonnteDial, implode("\n", $lines), (string) $booking->getKey());
        });
    }

    public function notifyMuthowifSelected(BookingReplacementOffer $offer): void
    {
        if (! WhatsAppNotifySettings::enabled('emergency_selection')) {
            return;
        }

        $this->notifyMuthowifSelectionResult($offer, selected: true);
    }

    public function notifyMuthowifNotSelected(BookingReplacementOffer $offer): void
    {
        if (! WhatsAppNotifySettings::enabled('emergency_selection')) {
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
     * @param  callable():void|callable():string  $callback
     */
    private function withLocale(string $locale, callable $callback): mixed
    {
        $previous = app()->getLocale();
        try {
            app()->setLocale($locale);

            return $callback();
        } finally {
            app()->setLocale($previous);
        }
    }

    /**
     * @param  list<string>  $phones
     */
    private function sendToPhoneNumbers(array $phones, string $message, string $contextId): void
    {
        if (! WhatsAppNotifySettings::hasToken()) {
            Log::debug('WhatsApp emergency admin notify skipped: FONNTE_TOKEN kosong.');

            return;
        }

        foreach ($phones as $index => $phone) {
            $dial = IntlPhone::fonnteDial((string) $phone);
            if ($dial === null) {
                Log::warning('WhatsApp emergency admin notify skipped: nomor tidak valid.', [
                    'phone' => $phone,
                    'context_id' => $contextId,
                ]);

                continue;
            }

            $job = SendWhatsAppTextJob::dispatch(
                $dial['target'],
                $message,
                $dial['country_calling_code'],
            );

            if ($index > 0) {
                $job->delay(now()->addSeconds($index));
            }
        }
    }

    /**
     * @return array{target: string, country_calling_code: string}|null
     */
    private function resolveMuthowifDial(string $phone, string $profileId, string $bookingId): ?array
    {
        if (! WhatsAppNotifySettings::hasToken()) {
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
        if (! WhatsAppNotifySettings::hasToken()) {
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
        SendWhatsAppTextJob::dispatch(
            $fonnteDial['target'],
            $message,
            $fonnteDial['country_calling_code'],
        );
    }
}
