@php
    $b = $booking;
@endphp

<div data-live-part="main" class="min-w-0 ui-stack-compact lg:col-start-1 lg:row-start-1">
    @include('bookings.partials.show-detail-card', ['booking' => $b])

    @include('bookings.partials.show-cancellation-alert', [
        'booking' => $b,
        'showReferralNetworkPanel' => $showReferralNetworkPanel ?? false,
        'referralNetworkAlternatives' => $referralNetworkAlternatives ?? collect(),
        'customerRecommendationSource' => $customerRecommendationSource ?? null,
    ])

    @include('bookings.partials.emergency-panel', [
        'booking' => $b,
        'activeEmergencyReport' => $activeEmergencyReport ?? null,
        'selectableEmergencyOffers' => $selectableEmergencyOffers ?? collect(),
    ])

    @include('bookings.partials.show-live-extended-main', [
        'booking' => $b,
        'daily' => $daily ?? 0.0,
        'nights' => $nights ?? $b->billingNightsInclusive(),
        'baseSubtotal' => $baseSubtotal ?? 0.0,
        'addonLines' => $addonLines ?? collect(),
        'sameHotelLine' => $sameHotelLine ?? 0.0,
        'transportLine' => $transportLine ?? 0.0,
        'customerTotal' => $customerTotal ?? 0.0,
        'customerPlatformFee' => $customerPlatformFee ?? 0.0,
        'fmt' => $fmt ?? static fn (float $n): string => number_format((int) round($n), 0, ',', '.'),
        'review' => $review ?? null,
    ])
</div>

<div data-live-part="aside" class="min-w-0 lg:col-start-2 lg:row-start-1 lg:sticky lg:top-24 lg:self-start">
    @include('bookings.partials.show-sidebar', [
        'booking' => $b,
        'showReferralNetworkPanel' => $showReferralNetworkPanel ?? false,
        'referralNetworkAlternatives' => $referralNetworkAlternatives ?? collect(),
        'customerRecommendationSource' => $customerRecommendationSource ?? null,
    ])
</div>
