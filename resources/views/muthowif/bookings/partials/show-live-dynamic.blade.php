@php
    $b = $booking;
@endphp

<div data-live-part="main" class="min-w-0 ui-stack-compact">
    @include('muthowif.bookings.partials.show-detail-card', ['booking' => $b])

    <div class="lg:hidden">
        @include('muthowif.bookings.partials.show-sidebar', [
            'booking' => $b,
            'daily' => $daily ?? 0,
            'nights' => $nights ?? 0,
            'serviceSubtotal' => $serviceSubtotal ?? 0,
            'addonLines' => $addonLines ?? collect(),
            'sameHotelLine' => $sameHotelLine ?? 0,
            'transportLine' => $transportLine ?? 0,
            'muthowifFee' => $muthowifFee ?? 0,
            'referralRewardFromPay' => $referralRewardFromPay ?? 0,
            'muthowifNetAfterReferral' => $muthowifNetAfterReferral ?? 0,
            'fmt' => $fmt ?? fn () => '',
        ])
    </div>

    @include('muthowif.bookings.partials.show-actions', [
        'booking' => $b,
        'peerRecommendTargets' => $peerRecommendTargets ?? collect(),
    ])
</div>

<div data-live-part="aside" class="hidden min-w-0 lg:block lg:col-start-2 lg:row-start-1 lg:row-span-2">
    @include('muthowif.bookings.partials.show-sidebar', [
        'booking' => $b,
        'daily' => $daily ?? 0,
        'nights' => $nights ?? 0,
        'serviceSubtotal' => $serviceSubtotal ?? 0,
        'addonLines' => $addonLines ?? collect(),
        'sameHotelLine' => $sameHotelLine ?? 0,
        'transportLine' => $transportLine ?? 0,
        'muthowifFee' => $muthowifFee ?? 0,
        'referralRewardFromPay' => $referralRewardFromPay ?? 0,
        'muthowifNetAfterReferral' => $muthowifNetAfterReferral ?? 0,
        'fmt' => $fmt ?? fn () => '',
    ])
</div>
