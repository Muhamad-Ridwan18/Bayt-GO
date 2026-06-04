@php
    use App\Enums\MuthowifServiceType;
    use App\Support\IndonesianNumber;
    use App\Support\PlatformFee;

    $b = $booking;
    $nights = $b->billingNightsInclusive();
    $service = $b->muthowifProfile?->services->firstWhere('type', $b->service_type);
    $daily = (float) ($b->daily_price_snapshot ?? ($service ? $service->daily_price : 0.0));
    $serviceSubtotal = (float) ($nights * $daily);

    $addonLines = collect();
    if ($b->service_type === MuthowifServiceType::PrivateJamaah) {
        if (! empty($b->add_ons_snapshot)) {
            $addonLines = collect($b->add_ons_snapshot)->map(fn ($a) => (object) $a);
        } elseif (! empty($b->selected_add_on_ids)) {
            foreach ($b->selected_add_on_ids as $aid) {
                if (isset($addonsById[$aid])) {
                    $addonLines->push($addonsById[$aid]);
                }
            }
        }
    }
    $addonsSum = $addonLines->sum(fn ($a) => (float) $a->price);

    $sameHotelPrice = (float) ($b->same_hotel_price_snapshot ?? ($service ? $service->same_hotel_price_per_day : 0.0));
    $sameHotelLine = $b->with_same_hotel ? ($nights * $sameHotelPrice) : 0.0;

    $transportLine = (float) ($b->transport_price_snapshot ?? ($b->with_transport && $service ? (float) $service->transport_price_flat : 0.0));

    $totalGross = (float) ($serviceSubtotal + $addonsSum + $sameHotelLine + $transportLine);
    $split = PlatformFee::split($totalGross);
    $muthowifNet = (float) ($split['muthowif_net'] ?? 0.0);
    $muthowifFee = (float) ($split['muthowif_fee'] ?? 0.0);
    $referralRewardFromPay = (float) ($referralRewardFromPay ?? 0);
    $muthowifNetAfterReferral = round(max(0.0, $muthowifNet - $referralRewardFromPay), 2);
    $fmt = fn (float $n) => IndonesianNumber::formatThousands((string) (int) round($n));
@endphp

@include('muthowif.bookings.partials.show-live-dynamic', [
    'booking' => $b,
    'daily' => $daily,
    'nights' => $nights,
    'serviceSubtotal' => $serviceSubtotal,
    'addonLines' => $addonLines,
    'sameHotelLine' => $sameHotelLine,
    'transportLine' => $transportLine,
    'muthowifFee' => $muthowifFee,
    'referralRewardFromPay' => $referralRewardFromPay,
    'muthowifNetAfterReferral' => $muthowifNetAfterReferral,
    'fmt' => $fmt,
    'peerRecommendTargets' => $peerRecommendTargets ?? collect(),
])

<div data-live-part="extended">
    @include('muthowif.bookings.partials.show-live-extended', [
        'booking' => $b,
        'fmt' => $fmt,
    ])
</div>
