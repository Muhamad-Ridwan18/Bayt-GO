<?php

namespace App\Services\Incident;

use App\Enums\BookingStatus;
use App\Enums\MuthowifServiceType;
use App\Enums\MuthowifVerificationStatus;
use App\Enums\PaymentStatus;
use App\Models\MuthowifBooking;
use App\Models\MuthowifProfile;
use App\Models\MuthowifService;
use App\Models\MuthowifServiceAddOn;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Kandidat pengganti untuk booking confirmed+paid (validasi mirip peer referral).
 */
final class BookingReplacementCandidateService
{
    /**
     * @return Collection<int, MuthowifProfile>
     */
    public function listCandidates(MuthowifBooking $booking, ?MuthowifProfile $excludeProfile = null): Collection
    {
        if ($booking->status !== BookingStatus::Confirmed || $booking->payment_status !== PaymentStatus::Paid) {
            return collect();
        }

        $excludeId = $excludeProfile?->getKey() ?? $booking->muthowif_profile_id;

        return MuthowifProfile::query()
            ->with(['user', 'services.addOns'])
            ->where('verification_status', MuthowifVerificationStatus::Approved)
            ->whereKeyNot($excludeId)
            ->whereHas('services', fn ($q) => $q->where('type', $booking->service_type->value))
            ->orderBy(
                User::query()
                    ->select('name')
                    ->whereColumn('users.id', 'muthowif_profiles.user_id')
                    ->limit(1)
            )
            ->limit(500)
            ->get()
            ->filter(fn (MuthowifProfile $p) => $this->profileMatchesBooking($booking, $p))
            ->values();
    }

    public function assertCanReplace(MuthowifBooking $booking, MuthowifProfile $target): void
    {
        if (! $target->isApproved()) {
            throw new \RuntimeException(__('incidents.errors.target_unverified'));
        }

        if ((string) $target->getKey() === (string) $booking->muthowif_profile_id) {
            throw new \RuntimeException(__('incidents.errors.same_muthowif'));
        }

        if (! $this->profileMatchesBooking($booking, $target)) {
            throw new \RuntimeException(__('incidents.errors.incompatible_service'));
        }

        $start = $booking->starts_on->copy()->startOfDay();
        $end = $booking->ends_on->copy()->startOfDay();

        if (! $target->isJadwalAvailableForRange($start, $end, (string) $booking->getKey())) {
            throw new \RuntimeException(__('incidents.errors.jadwal_unavailable'));
        }
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

        if ($booking->service_type === MuthowifServiceType::PrivateJamaah) {
            $addonNames = $this->collectAddonNames($booking);
            if ($addonNames !== [] && ! $this->addOnsMatch($addonNames, $svc)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return list<string>
     */
    private function collectAddonNames(MuthowifBooking $booking): array
    {
        $snapshot = $booking->add_ons_snapshot;
        if (is_array($snapshot) && $snapshot !== []) {
            $out = [];
            foreach ($snapshot as $row) {
                if (! is_array($row)) {
                    continue;
                }
                $n = trim((string) ($row['name'] ?? ''));
                if ($n !== '') {
                    $out[] = $n;
                }
            }

            return $out;
        }

        return $booking->resolvedAddOns()->map(fn ($a) => (string) $a->name)->all();
    }

    /**
     * @param  list<string>  $names
     */
    private function addOnsMatch(array $names, MuthowifService $service): bool
    {
        foreach ($names as $name) {
            $trim = trim($name);
            if ($trim === '') {
                continue;
            }
            $match = $service->addOns->first(
                fn (MuthowifServiceAddOn $a) => strcasecmp(trim((string) $a->name), $trim) === 0
            );
            if ($match === null) {
                return false;
            }
        }

        return true;
    }
}
