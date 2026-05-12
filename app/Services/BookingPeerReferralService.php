<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\MuthowifServiceType;
use App\Enums\MuthowifVerificationStatus;
use App\Enums\PaymentStatus;
use App\Models\MuthowifBooking;
use App\Models\MuthowifProfile;
use App\Models\MuthowifService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class BookingPeerReferralService
{
    /**
     * Alihkan booking pending ke profil muthowif lain (jenis layanan & kapasitas cocok, slot tersedia).
     *
     * @throws RuntimeException
     */
    public function transfer(MuthowifBooking $booking, MuthowifProfile $targetProfile, MuthowifProfile $fromProfile): void
    {
        if ((string) $booking->muthowif_profile_id !== (string) $fromProfile->getKey()) {
            throw new RuntimeException(__('muthowif.bookings.refer_err_not_owner'));
        }

        if ($booking->status !== BookingStatus::Pending || $booking->payment_status !== PaymentStatus::Pending) {
            throw new RuntimeException(__('muthowif.bookings.refer_err_status'));
        }

        if ((string) $targetProfile->getKey() === (string) $fromProfile->getKey()) {
            throw new RuntimeException(__('muthowif.bookings.refer_err_self'));
        }

        if (! $targetProfile->isApproved()) {
            throw new RuntimeException(__('muthowif.bookings.refer_err_target_unverified'));
        }

        $booking->loadMissing(['muthowifProfile.services.addOns']);
        $targetProfile->load(['services.addOns']);

        $newService = $targetProfile->services->firstWhere('type', $booking->service_type);
        if (! $newService instanceof MuthowifService) {
            throw new RuntimeException(__('muthowif.bookings.refer_err_no_service'));
        }

        $pilgrim = (int) $booking->pilgrim_count;
        $min = $newService->min_pilgrims !== null ? (int) $newService->min_pilgrims : 1;
        $max = $newService->max_pilgrims !== null ? (int) $newService->max_pilgrims : 50;
        if ($pilgrim < $min || $pilgrim > $max) {
            throw new RuntimeException(__('muthowif.bookings.refer_err_pilgrim_capacity'));
        }

        $start = $booking->starts_on->copy()->startOfDay();
        $end = $booking->ends_on->copy()->startOfDay();
        if (! $targetProfile->isSlotAvailableForRange($start, $end)) {
            throw new RuntimeException(__('muthowif.bookings.refer_err_slot'));
        }

        $addonNames = $this->collectAddonNames($booking);
        $matchedIds = $this->matchAddOnIdsByName($addonNames, $newService);

        DB::transaction(function () use ($booking, $targetProfile, $matchedIds): void {
            $booking->refresh()->lockForUpdate();

            if ($booking->status !== BookingStatus::Pending || $booking->payment_status !== PaymentStatus::Pending) {
                throw new RuntimeException(__('muthowif.bookings.refer_err_status'));
            }

            $booking->forceFill([
                'muthowif_profile_id' => $targetProfile->getKey(),
                'selected_add_on_ids' => $matchedIds,
                'daily_price_snapshot' => null,
                'same_hotel_price_snapshot' => null,
                'transport_price_snapshot' => null,
                'add_ons_snapshot' => null,
                'total_amount' => null,
            ])->save();
        });
    }

    /**
     * Kandidat rekomendasi: disetujui, bukan diri sendiri, punya layanan sama, kapasitas & slot ok.
     *
     * @return Collection<int, MuthowifProfile>
     */
    public function listCandidates(MuthowifBooking $booking, MuthowifProfile $fromProfile): Collection
    {
        if ($booking->status !== BookingStatus::Pending) {
            return collect();
        }

        $start = $booking->starts_on->copy()->startOfDay();
        $end = $booking->ends_on->copy()->startOfDay();

        return MuthowifProfile::query()
            ->with(['user', 'services.addOns'])
            ->where('verification_status', MuthowifVerificationStatus::Approved)
            ->whereKeyNot($fromProfile->getKey())
            ->whereHas('services', fn ($q) => $q->where('type', $booking->service_type->value))
            ->orderByDesc('verified_at')
            ->limit(100)
            ->get()
            ->filter(function (MuthowifProfile $p) use ($booking, $start, $end): bool {
                if (! $p->isSlotAvailableForRange($start, $end)) {
                    return false;
                }

                $svc = $p->services->firstWhere('type', $booking->service_type);
                if (! $svc instanceof MuthowifService) {
                    return false;
                }

                $pilgrim = (int) $booking->pilgrim_count;
                $min = $svc->min_pilgrims !== null ? (int) $svc->min_pilgrims : 1;
                $max = $svc->max_pilgrims !== null ? (int) $svc->max_pilgrims : 50;

                return $pilgrim >= $min && $pilgrim <= $max;
            })
            ->values();
    }

    /**
     * @return list<string>
     */
    private function collectAddonNames(MuthowifBooking $booking): array
    {
        if ($booking->service_type !== MuthowifServiceType::PrivateJamaah) {
            return [];
        }

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

        $names = [];
        foreach ($booking->resolvedAddOns() as $addon) {
            $names[] = (string) $addon->name;
        }

        return $names;
    }

    /**
     * Cocokkan nama add-on (case-insensitive) ke add-on layanan target.
     *
     * @param  list<string>  $names
     * @return list<string>
     */
    private function matchAddOnIdsByName(array $names, MuthowifService $newService): array
    {
        if ($names === []) {
            return [];
        }

        $ids = [];
        foreach ($names as $name) {
            $trim = trim($name);
            if ($trim === '') {
                continue;
            }
            $match = $newService->addOns->first(
                fn ($a) => strcasecmp(trim((string) $a->name), $trim) === 0
            );
            if ($match !== null) {
                $ids[] = (string) $match->getKey();
            }
        }

        return array_values(array_unique($ids));
    }
}
