@php
    $b = $booking;
@endphp

<div data-live-part="main" class="min-w-0 space-y-6">
    @include('bookings.partials.show-detail-card', ['booking' => $b])

    @include('bookings.partials.show-cancellation-alert', [
        'booking' => $b,
        'showReferralNetworkPanel' => $showReferralNetworkPanel ?? false,
        'referralNetworkAlternatives' => $referralNetworkAlternatives ?? collect(),
    ])

    @include('bookings.partials.emergency-panel', [
        'booking' => $b,
        'activeEmergencyReport' => $activeEmergencyReport ?? null,
        'selectableEmergencyOffers' => $selectableEmergencyOffers ?? collect(),
    ])
</div>

<div data-live-part="aside" class="min-w-0 lg:col-start-2 lg:row-start-1 lg:row-span-2">
    @include('bookings.partials.show-sidebar', [
        'booking' => $b,
        'showReferralNetworkPanel' => $showReferralNetworkPanel ?? false,
        'referralNetworkAlternatives' => $referralNetworkAlternatives ?? collect(),
    ])
</div>
