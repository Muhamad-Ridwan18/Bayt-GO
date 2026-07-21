<?php

namespace App\Support;

use App\Enums\MuthowifServiceType;
use App\Models\MuthowifBooking;
use Illuminate\Support\Collection;

final class BookingPricingViewData
{
    /**
     * @param  array<string, mixed>|Collection<string, mixed>  $addonsById
     * @return array{
     *   isSupport: bool,
     *   nights: int,
     *   sleepNights: int,
     *   daily: ?float,
     *   baseSubtotal: float,
     *   addonLines: Collection,
     *   sameHotelLine: float,
     *   transportLine: float,
     *   customerTotal: float,
     *   customerPlatformFee: float,
     *   packageName: ?string
     * }
     */
    public static function forCustomer(MuthowifBooking $booking, array|Collection $addonsById = []): array
    {
        if ($addonsById instanceof Collection) {
            $addonsById = $addonsById->all();
        }

        $isSupport = $booking->isSupport();

        if ($isSupport) {
            $packagePrice = (float) ($booking->package_price_snapshot ?? 0);
            $split = PlatformFee::split($packagePrice, $booking->customer?->isCompanyCustomer() ?? false);

            return [
                'isSupport' => true,
                'nights' => 0,
                'sleepNights' => 0,
                'daily' => null,
                'baseSubtotal' => $packagePrice,
                'addonLines' => collect(),
                'sameHotelLine' => 0.0,
                'transportLine' => 0.0,
                'customerTotal' => (float) ($split['customer_gross'] ?? $packagePrice),
                'customerPlatformFee' => (float) ($split['customer_fee'] ?? 0.0),
                'packageName' => $booking->package_name_snapshot ?? $booking->supportPackage?->name,
            ];
        }

        $nights = $booking->billingNightsInclusive();
        $service = $booking->muthowifProfile?->services->firstWhere('type', $booking->service_type);
        $daily = (float) ($booking->daily_price_snapshot ?? ($service ? $service->daily_price : 0.0));
        $baseSubtotal = (float) $nights * $daily;

        $addonLines = collect();
        if ($booking->service_type === MuthowifServiceType::PrivateJamaah) {
            if (! empty($booking->add_ons_snapshot)) {
                $addonLines = collect($booking->add_ons_snapshot)->map(fn ($a) => (object) $a);
            } elseif (! empty($booking->selected_add_on_ids)) {
                foreach ($booking->selected_add_on_ids as $aid) {
                    if (isset($addonsById[$aid])) {
                        $addonLines->push($addonsById[$aid]);
                    }
                }
            }
        }

        $sameHotelPrice = (float) ($booking->same_hotel_price_snapshot ?? ($service ? $service->same_hotel_price_per_day : 0.0));
        $sameHotelLine = $booking->with_same_hotel ? ($nights * $sameHotelPrice) : 0.0;
        $transportPrice = (float) ($booking->transport_price_snapshot ?? ($service ? (float) $service->transport_price_flat : 0.0));
        $transportLine = $booking->with_transport ? $transportPrice : 0.0;
        $baseTotal = (float) ($baseSubtotal + $addonLines->sum(fn ($a) => (float) $a->price) + $sameHotelLine + $transportLine);
        $split = PlatformFee::split($baseTotal, $booking->customer?->isCompanyCustomer() ?? false);

        return [
            'isSupport' => false,
            'nights' => $nights,
            'sleepNights' => max(0, $nights - 1),
            'daily' => $daily,
            'baseSubtotal' => $baseSubtotal,
            'addonLines' => $addonLines,
            'sameHotelLine' => $sameHotelLine,
            'transportLine' => $transportLine,
            'customerTotal' => (float) ($split['customer_gross'] ?? $baseTotal),
            'customerPlatformFee' => (float) ($split['customer_fee'] ?? 0.0),
            'packageName' => null,
        ];
    }
}
