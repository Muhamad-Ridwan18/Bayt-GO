<?php

namespace App\Services\Incident;

use App\Enums\BookingReplacementStatus;
use App\Models\BookingIncident;
use App\Models\MuthowifProfile;
use App\Services\FonnteService;
use App\Support\IntlPhone;
use Illuminate\Support\Facades\Log;

final class ReplacementOpportunityWhatsAppNotifier
{
    public function __construct(
        private readonly FonnteService $fonnte,
    ) {}

    public function notifyOpportunity(BookingIncident $incident, MuthowifProfile $profile): void
    {
        if (! config('services.fonnte.booking_notify_enabled', true)) {
            return;
        }

        $incident->loadMissing(['muthowifBooking.customer']);
        $booking = $incident->muthowifBooking;
        if ($booking === null) {
            return;
        }

        $profile->loadMissing('user');
        $dial = IntlPhone::fonnteDial($profile->phone);
        if ($dial === null) {
            return;
        }

        $locale = $profile->user?->locale === 'en' ? 'en' : 'id';
        $url = route('muthowif.replacements.opportunities');

        $message = __('incidents.whatsapp.muthowif_opportunity', [
            'code' => $booking->booking_code ?? '—',
            'dates' => $booking->starts_on->format('d/m/Y').' – '.$booking->ends_on->format('d/m/Y'),
            'url' => $url,
        ], $locale);

        try {
            $this->fonnte->sendText($dial['target'], $message, $dial['country_calling_code']);
        } catch (\Throwable $e) {
            Log::warning('incident.replacement_broadcast_failed', [
                'incident_id' => $incident->getKey(),
                'profile_id' => $profile->getKey(),
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function notifyCustomerPoolReady(BookingIncident $incident): void
    {
        if (! config('services.fonnte.booking_notify_enabled', true)) {
            return;
        }

        $incident->loadMissing(['muthowifBooking.customer']);
        $booking = $incident->muthowifBooking;
        $customer = $booking?->customer;
        if ($booking === null || $customer === null) {
            return;
        }

        $count = $incident->replacements()
            ->whereIn('status', array_map(
                fn (BookingReplacementStatus $s) => $s->value,
                BookingReplacementStatus::customerSelectable()
            ))
            ->count();

        if ($count < 1) {
            return;
        }

        $dial = IntlPhone::fonnteDial($customer->phone);
        if ($dial === null) {
            return;
        }

        $locale = $customer->locale === 'en' ? 'en' : 'id';
        $url = route('bookings.show', $booking);

        $message = __('incidents.whatsapp.customer_pool_ready', [
            'count' => $count,
            'code' => $booking->booking_code ?? '—',
            'url' => $url,
        ], $locale);

        try {
            $this->fonnte->sendText($dial['target'], $message, $dial['country_calling_code']);
        } catch (\Throwable $e) {
            Log::warning('incident.customer_pool_notify_failed', [
                'incident_id' => $incident->getKey(),
                'error' => $e->getMessage(),
            ]);
        }
    }
}
