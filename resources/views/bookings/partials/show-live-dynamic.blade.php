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

    @isset($fmt)
        @include('bookings.partials.show-live-extended-main', [
            'booking' => $b,
            'daily' => $daily,
            'nights' => $nights,
            'baseSubtotal' => $baseSubtotal,
            'addonLines' => $addonLines,
            'sameHotelLine' => $sameHotelLine,
            'transportLine' => $transportLine,
            'customerTotal' => $customerTotal,
            'customerPlatformFee' => $customerPlatformFee,
            'fmt' => $fmt,
            'review' => $review ?? null,
        ])
    @endisset
</div>

<div data-live-part="aside" class="min-w-0 lg:col-start-2 lg:row-start-1 lg:sticky lg:top-24 lg:self-start">
    @include('bookings.partials.show-sidebar', [
        'booking' => $b,
        'showReferralNetworkPanel' => $showReferralNetworkPanel ?? false,
        'referralNetworkAlternatives' => $referralNetworkAlternatives ?? collect(),
    ])
</div>
