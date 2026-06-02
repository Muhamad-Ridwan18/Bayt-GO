<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\MuthowifVerificationStatus;
use App\Models\MuthowifBooking;
use App\Models\MuthowifProfile;
use App\Models\MuthowifService;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Rekomendasi muthowif dari jaringan referral (yang mendaftar pakai kode muthowif penolak).
 */
class MuthowifNetworkReferralService
{
    /**
     * Muthowif alternatif dari jaringan referral muthowif pada booking (setelah dibatalkan).
     *
     * @return Collection<int, MuthowifProfile>
     */
    public function alternativesForCustomerAfterJadwalRejection(MuthowifBooking $booking): Collection
    {
        if ($booking->status !== BookingStatus::Cancelled) {
            return collect();
        }

        $declinerId = (string) $booking->muthowif_profile_id;
        if ($declinerId === '') {
            return collect();
        }

        $serviceType = $booking->service_type;
        if ($serviceType === null) {
            return collect();
        }

        $start = $booking->starts_on->copy()->startOfDay();
        $end = $booking->ends_on->copy()->startOfDay();

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
            ->filter(function (MuthowifProfile $p) use ($booking, $start, $end, $serviceType): bool {
                $svc = $p->services->firstWhere('type', $serviceType);
                if (! $svc instanceof MuthowifService) {
                    return false;
                }

                $pilgrim = (int) $booking->pilgrim_count;
                $min = $svc->min_pilgrims !== null ? (int) $svc->min_pilgrims : 1;
                $max = $svc->max_pilgrims !== null ? (int) $svc->max_pilgrims : 50;
                if ($pilgrim < $min || $pilgrim > $max) {
                    return false;
                }

                return $p->isJadwalAvailableForRange($start, $end);
            })
            ->values();
    }

    /**
     * Panel rekomendasi jaringan referral untuk jamaah (semua pembatalan, bukan hanya jadwal penuh).
     */
    public function shouldShowCustomerReferralPanel(MuthowifBooking $booking): bool
    {
        return $booking->status === BookingStatus::Cancelled
            && filled($booking->muthowif_profile_id);
    }
}
