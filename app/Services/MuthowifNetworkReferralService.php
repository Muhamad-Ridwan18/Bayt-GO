<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\MuthowifServiceType;
use App\Enums\MuthowifVerificationStatus;
use App\Models\MuthowifBooking;
use App\Models\MuthowifProfile;
use App\Models\MuthowifService;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Rekomendasi muthowif untuk jamaah setelah pesanan dibatalkan muthowif:
 * prioritas jaringan referral penolak, fallback rating marketplace terbaik.
 */
class MuthowifNetworkReferralService
{
    public const SOURCE_REFERRAL_NETWORK = 'referral_network';

    public const SOURCE_TOP_RATED = 'top_rated';

    /**
     * @return array{profiles: Collection<int, MuthowifProfile>, source: self::SOURCE_*}
     */
    public function resolveCustomerAlternatives(MuthowifBooking $booking): array
    {
        $referralProfiles = $this->referralNetworkAlternatives($booking);
        if ($referralProfiles->isNotEmpty()) {
            return [
                'profiles' => $referralProfiles,
                'source' => self::SOURCE_REFERRAL_NETWORK,
            ];
        }

        return [
            'profiles' => $this->topRatedAlternatives($booking),
            'source' => self::SOURCE_TOP_RATED,
        ];
    }

    /**
     * @return Collection<int, MuthowifProfile>
     */
    public function alternativesForCustomerAfterJadwalRejection(MuthowifBooking $booking): Collection
    {
        return $this->resolveCustomerAlternatives($booking)['profiles'];
    }

    /**
     * Panel rekomendasi untuk jamaah (semua pembatalan muthowif).
     */
    public function shouldShowCustomerReferralPanel(MuthowifBooking $booking): bool
    {
        return $booking->status === BookingStatus::Cancelled
            && filled($booking->muthowif_profile_id);
    }

    /**
     * Muthowif dari jaringan referral penolak (daftar pakai kode referral-nya).
     *
     * @return Collection<int, MuthowifProfile>
     */
    private function referralNetworkAlternatives(MuthowifBooking $booking): Collection
    {
        $context = $this->bookingContext($booking);
        if ($context === null) {
            return collect();
        }

        ['decliner_id' => $declinerId, 'service_type' => $serviceType, 'start' => $start, 'end' => $end] = $context;

        return MuthowifProfile::query()
            ->with(['user', 'services'])
            ->where('verification_status', MuthowifVerificationStatus::Approved)
            ->where('referred_by_muthowif_profile_id', $declinerId)
            ->whereKeyNot($declinerId)
            ->whereHas('services', fn ($q) => $q->where('type', $serviceType->value))
            ->orderBy(
                User::query()
                    ->select('name')
                    ->whereColumn('users.id', 'muthowif_profiles.user_id')
                    ->limit(1)
            )
            ->limit(80)
            ->get()
            ->pipe(fn (Collection $profiles) => $this->filterEligibleForBooking($profiles, $booking, $start, $end, $serviceType));
    }

    /**
     * Fallback: muthowif terverifikasi dengan rating terbaik & jadwal masih kosong.
     *
     * @return Collection<int, MuthowifProfile>
     */
    private function topRatedAlternatives(MuthowifBooking $booking): Collection
    {
        $context = $this->bookingContext($booking);
        if ($context === null) {
            return collect();
        }

        ['decliner_id' => $declinerId, 'service_type' => $serviceType, 'start' => $start, 'end' => $end] = $context;

        return MuthowifProfile::query()
            ->approved()
            ->hasPublishedServices()
            ->with(['user', 'services'])
            ->withMarketplaceStats()
            ->whereKeyNot($declinerId)
            ->whereHas('services', fn ($q) => $q->where('type', $serviceType->value))
            ->orderByMarketplaceRanking()
            ->limit(80)
            ->get()
            ->pipe(fn (Collection $profiles) => $this->filterEligibleForBooking($profiles, $booking, $start, $end, $serviceType));
    }

    /**
     * @return array{decliner_id: string, service_type: \App\Enums\MuthowifServiceType, start: Carbon, end: Carbon}|null
     */
    private function bookingContext(MuthowifBooking $booking): ?array
    {
        if ($booking->status !== BookingStatus::Cancelled) {
            return null;
        }

        $declinerId = (string) $booking->muthowif_profile_id;
        if ($declinerId === '') {
            return null;
        }

        $serviceType = $booking->service_type;
        if ($serviceType === null) {
            return null;
        }

        return [
            'decliner_id' => $declinerId,
            'service_type' => $serviceType,
            'start' => $booking->starts_on->copy()->startOfDay(),
            'end' => $booking->ends_on->copy()->startOfDay(),
        ];
    }

    /**
     * @param  Collection<int, MuthowifProfile>  $profiles
     * @return Collection<int, MuthowifProfile>
     */
    private function filterEligibleForBooking(
        Collection $profiles,
        MuthowifBooking $booking,
        Carbon $start,
        Carbon $end,
        MuthowifServiceType $serviceType,
    ): Collection {
        return $profiles
            ->filter(function (MuthowifProfile $profile) use ($booking, $start, $end, $serviceType): bool {
                $service = $profile->services->firstWhere('type', $serviceType);
                if (! $service instanceof MuthowifService) {
                    return false;
                }

                $pilgrim = (int) $booking->pilgrim_count;
                $min = $service->min_pilgrims !== null ? (int) $service->min_pilgrims : 1;
                $max = $service->max_pilgrims !== null ? (int) $service->max_pilgrims : 50;
                if ($pilgrim < $min || $pilgrim > $max) {
                    return false;
                }

                return $profile->isJadwalAvailableForRange($start, $end);
            })
            ->values();
    }
}
