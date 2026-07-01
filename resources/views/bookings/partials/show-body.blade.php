@php
    use App\Enums\MuthowifServiceType;
    use App\Support\IndonesianNumber;

    $b = $booking;
    $isSupport = $b->isSupport();

    if ($isSupport) {
        $packagePrice = (float) ($b->package_price_snapshot ?? 0);
        $baseTotal = $packagePrice;
        $nights = 0;
        $daily = null;
        $baseSubtotal = $packagePrice;
        $addonLines = collect();
        $sameHotelLine = 0.0;
        $transportLine = 0.0;
    } else {
        $nights = $b->billingNightsInclusive();
        $b->loadMissing(['muthowifProfile.services']);
        $service = $b->muthowifProfile?->services->firstWhere('type', $b->service_type);
        $daily = (float) ($b->daily_price_snapshot ?? ($service ? $service->daily_price : 0.0));
        $baseSubtotal = (float) $nights * $daily;

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

        $sameHotelPrice = (float) ($b->same_hotel_price_snapshot ?? ($service ? $service->same_hotel_price_per_day : 0.0));
        $sameHotelLine = $b->with_same_hotel ? ($nights * $sameHotelPrice) : 0.0;
        $transportPrice = (float) ($b->transport_price_snapshot ?? ($service ? (float) $service->transport_price_flat : 0.0));
        $transportLine = $b->with_transport ? $transportPrice : 0.0;
        $baseTotal = (float) ($baseSubtotal + $addonLines->sum(fn ($a) => (float) $a->price) + $sameHotelLine + $transportLine);
    }

    $addonsSum = $addonLines->sum(fn ($a) => (float) $a->price);
    $isCompany = $b->customer?->isCompanyCustomer() ?? false;
    $split = \App\Support\PlatformFee::split($baseTotal, $isCompany);
    $customerTotal = (float) ($split['customer_gross'] ?? $baseTotal);
    $customerPlatformFee = (float) ($split['customer_fee'] ?? 0.0);
    $review = $b->review;
    $fmt = fn (float $n) => IndonesianNumber::formatThousands((string) (int) round($n));
@endphp

@include('bookings.partials.show-live-dynamic', [
    'booking' => $b,
    'showReferralNetworkPanel' => $showReferralNetworkPanel ?? false,
    'referralNetworkAlternatives' => $referralNetworkAlternatives ?? collect(),
    'customerRecommendationSource' => $customerRecommendationSource ?? null,
    'activeEmergencyReport' => $activeEmergencyReport ?? null,
    'selectableEmergencyOffers' => $selectableEmergencyOffers ?? collect(),
    'daily' => $daily,
    'nights' => $nights,
    'isSupport' => $isSupport,
    'packageName' => $isSupport ? ($b->package_name_snapshot ?? $b->supportPackage?->name) : null,
    'baseSubtotal' => $baseSubtotal,
    'addonLines' => $addonLines,
    'sameHotelLine' => $sameHotelLine,
    'transportLine' => $transportLine,
    'customerTotal' => $customerTotal,
    'customerPlatformFee' => $customerPlatformFee,
    'fmt' => $fmt,
    'review' => $review,
])
