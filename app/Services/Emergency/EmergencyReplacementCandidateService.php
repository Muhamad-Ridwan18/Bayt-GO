<?php

namespace App\Services\Emergency;

use App\Enums\BookingStatus;
use App\Models\MuthowifBooking;
use App\Models\MuthowifProfile;
use App\Models\MuthowifService;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

final class EmergencyReplacementCandidateService
{
    /**
     * @return Collection<int, MuthowifProfile>
     */
    public function listEligible(
        MuthowifBooking $booking,
        ?string $excludeProfileId = null,
        array $excludeProfileIds = [],
    ): Collection {
        if ($booking->status !== BookingStatus::Confirmed || ! $booking->isPaid()) {
            return collect();
        }

        $excludeId = $excludeProfileId ?? (string) $booking->muthowif_profile_id;
        $excludeAll = array_unique(array_merge([$excludeId], $excludeProfileIds));

        return MuthowifProfile::query()
            ->with(['user', 'services'])
            ->approved()
            ->hasPublishedServices()
            ->withMarketplaceStats()
            ->whereKeyNot($excludeAll)
            ->whereHas('services', fn ($q) => $q->where('type', $booking->service_type->value))
            ->orderByMarketplaceRanking()
            ->limit(500)
            ->get()
            ->filter(fn (MuthowifProfile $p) => $this->profileMatchesBooking($booking, $p))
            ->values();
    }

    public function assertCanReplace(MuthowifBooking $booking, MuthowifProfile $target): void
    {
        if (! $target->isEligibleForEmergencyReplacement()) {
            throw new \RuntimeException(__('emergency.errors.target_not_eligible'));
        }

        if ((string) $target->getKey() === (string) $booking->muthowif_profile_id) {
            throw new \RuntimeException(__('emergency.errors.same_muthowif'));
        }

        if (! $this->profileMatchesBooking($booking, $target)) {
            throw new \RuntimeException(__('emergency.errors.incompatible_service'));
        }

        [$start, $end] = $this->replacementDateRange($booking);

        if (! $target->isJadwalAvailableForRange($start, $end, (string) $booking->getKey())) {
            throw new \RuntimeException(__('emergency.errors.jadwal_unavailable'));
        }
    }

    /**
     * @return array{0: CarbonInterface, 1: CarbonInterface}
     */
    public function replacementDateRange(MuthowifBooking $booking): array
    {
        $today = now()->startOfDay();
        $start = $booking->starts_on->copy()->startOfDay();
        $end = $booking->ends_on->copy()->startOfDay();

        if ($start->lt($today)) {
            $start = $today;
        }

        if ($start->gt($end)) {
            $start = $end;
        }

        return [$start, $end];
    }

    private function profileMatchesBooking(MuthowifBooking $booking, MuthowifProfile $profile): bool
    {
        $svc = $profile->services->firstWhere('type', $booking->service_type);
        if (! $svc instanceof MuthowifService) {
            return false;
        }

        $pilgrim = (int) $booking->pilgrim_count;
        $min = $svc->min_pilgrims !== null ? (int) $svc->min_pilgrims : 1;
        $max = $svc->max_pilgrims !== null ? (int) $svc->max_pilgrims : 50;

        if ($pilgrim < $min || $pilgrim > $max) {
            return false;
        }

        // Add-on tidak wajib cocok nama/ID: pengganti darurat dinilai tipe layanan, kapasitas jemaah, dan jadwal.

        if ($booking->with_same_hotel && (float) ($svc->same_hotel_price_per_day ?? 0) <= 0) {
            return false;
        }

        if ($booking->with_transport && (float) ($svc->transport_price_flat ?? 0) <= 0) {
            return false;
        }

        [$start, $end] = $this->replacementDateRange($booking);

        return $profile->isJadwalAvailableForRange($start, $end, (string) $booking->getKey());
    }
}
