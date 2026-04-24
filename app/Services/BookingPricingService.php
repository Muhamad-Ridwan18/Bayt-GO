<?php

namespace App\Services;

use App\Enums\MuthowifServiceType;
use App\Models\MuthowifBooking;
use App\Models\MuthowifService;
use App\Models\MuthowifServiceAddOn;
use Illuminate\Support\Collection;

class BookingPricingService
{
    /**
     * Menghitung total harga pemesanan.
     * Prioritas: Kolom snapshot -> Kalkulasi live.
     */
    public function calculateTotal(MuthowifBooking $booking): float
    {
        // Jika total_amount sudah di-snapshot, kembalikan langsung
        if ($booking->total_amount !== null) {
            return (float) $booking->total_amount;
        }

        $nights = $booking->billingNightsInclusive();
        
        // 1. Base Price (Daily Price)
        $dailyPrice = $this->getDailyPrice($booking);
        $base = $nights * $dailyPrice;

        // 2. Add-ons Price
        $addons = $this->getAddOnsTotal($booking);

        // 3. Optional Services
        $sameHotel = 0.0;
        if ($booking->with_same_hotel) {
            $sameHotel = $nights * $this->getSameHotelPrice($booking);
        }

        $transport = 0.0;
        if ($booking->with_transport) {
            $transport = $this->getTransportPrice($booking);
        }

        return round($base + $addons + $sameHotel + $transport, 2);
    }

    /**
     * Mengambil snapshot harga dari layanan untuk disimpan di booking.
     * @return array<string, mixed>
     */
    public function getPricingSnapshots(MuthowifBooking $booking): array
    {
        $booking->loadMissing(['muthowifProfile.services.addOns']);
        $service = $booking->muthowifProfile->services->firstWhere('type', $booking->service_type);
        
        if (!$service) {
            return [];
        }

        $addOnsSnapshot = [];
        if ($booking->service_type === MuthowifServiceType::PrivateJamaah) {
            $addOns = $this->getResolvedAddOns($booking);
            foreach ($addOns as $addon) {
                $addOnsSnapshot[] = [
                    'id' => $addon->id,
                    'name' => $addon->name,
                    'price' => (float) $addon->price,
                ];
            }
        }

        return [
            'daily_price_snapshot' => (float) $service->daily_price,
            'same_hotel_price_snapshot' => (float) ($service->same_hotel_price_per_day ?? 0),
            'transport_price_snapshot' => (float) ($service->transport_price_flat ?? 0),
            'add_ons_snapshot' => $addOnsSnapshot,
        ];
    }

    private function getDailyPrice(MuthowifBooking $booking): float
    {
        if ($booking->daily_price_snapshot !== null) {
            return (float) $booking->daily_price_snapshot;
        }

        $service = $this->getService($booking);
        return $service && $service->daily_price !== null ? (float) $service->daily_price : 0.0;
    }

    private function getSameHotelPrice(MuthowifBooking $booking): float
    {
        if ($booking->same_hotel_price_snapshot !== null) {
            return (float) $booking->same_hotel_price_snapshot;
        }

        $service = $this->getService($booking);
        return $service && $service->same_hotel_price_per_day !== null ? (float) $service->same_hotel_price_per_day : 0.0;
    }

    private function getTransportPrice(MuthowifBooking $booking): float
    {
        if ($booking->transport_price_snapshot !== null) {
            return (float) $booking->transport_price_snapshot;
        }

        $service = $this->getService($booking);
        return $service && $service->transport_price_flat !== null ? (float) $service->transport_price_flat : 0.0;
    }

    private function getAddOnsTotal(MuthowifBooking $booking): float
    {
        if ($booking->add_ons_snapshot !== null) {
            $total = 0.0;
            foreach ($booking->add_ons_snapshot as $addon) {
                $total += (float) ($addon['price'] ?? 0);
            }
            return $total;
        }

        $total = 0.0;
        foreach ($this->getResolvedAddOns($booking) as $addon) {
            $total += (float) $addon->price;
        }
        return $total;
    }

    private function getService(MuthowifBooking $booking): ?MuthowifService
    {
        $booking->loadMissing(['muthowifProfile.services']);
        return $booking->muthowifProfile?->services->firstWhere('type', $booking->service_type);
    }

    /**
     * @return Collection<int, MuthowifServiceAddOn>
     */
    private function getResolvedAddOns(MuthowifBooking $booking): Collection
    {
        $ids = $booking->selected_add_on_ids;
        if (! is_array($ids) || $ids === [] || $booking->service_type !== MuthowifServiceType::PrivateJamaah) {
            return collect();
        }

        return MuthowifServiceAddOn::query()
            ->whereIn('id', $ids)
            ->get();
    }
}
