<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\MuthowifServiceType;
use App\Enums\PaymentStatus;
use App\Jobs\NotifyCustomerOfSupportCompletionApproved;
use App\Jobs\NotifyMuthowifOfSupportCompletionRequested;
use App\Jobs\NotifyMuthowifOfNewBooking;
use App\Models\MuthowifBooking;
use App\Models\MuthowifProfile;
use App\Models\MuthowifSupportPackage;
use App\Support\CustomerBookingBroadcast;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SupportBookingService
{
    public function __construct(
        private readonly BookingOrderCodeService $orderCodes,
        private readonly BookingCompletionService $completion,
    ) {}

    public function create(Request $request, MuthowifSupportPackage $package, int $pilgrimCount, Carbon $startsAt): MuthowifBooking
    {
        $package->loadMissing('muthowifProfile');
        $profile = $package->muthowifProfile;

        if (! $profile?->isApproved() || ! $package->is_active) {
            throw ValidationException::withMessages([
                'support_package_id' => [__('layanan_pendukung.validation.package_unavailable')],
            ]);
        }

        [$min, $max] = $package->pilgrimBounds();
        if ($pilgrimCount < $min || $pilgrimCount > $max) {
            throw ValidationException::withMessages([
                'pilgrim_count' => [__('bookings.validation.pilgrim_count_between', ['min' => $min, 'max' => $max])],
            ]);
        }

        $day = $startsAt->copy()->startOfDay();
        if ($day->lt(now()->startOfDay())) {
            throw ValidationException::withMessages([
                'starts_at' => [__('bookings.validation.start_past')],
            ]);
        }

        return DB::transaction(function () use ($request, $package, $profile, $pilgrimCount, $startsAt, $day): MuthowifBooking {
            /** @var MuthowifProfile $lockedProfile */
            $lockedProfile = MuthowifProfile::query()
                ->whereKey($profile->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (! $lockedProfile->isJadwalAvailableForRange($day, $day)) {
                throw ValidationException::withMessages([
                    'starts_at' => [__('bookings.validation.jadwal_tidak_tersedia')],
                ]);
            }

            $existing = MuthowifBooking::query()
                ->where('muthowif_profile_id', $lockedProfile->id)
                ->where('customer_id', $request->user()->id)
                ->where('support_package_id', $package->id)
                ->where('starts_at', $startsAt)
                ->where('status', BookingStatus::Pending)
                ->first();

            if ($existing) {
                return $existing;
            }

            $bookingCode = $this->orderCodes->allocateNextWithinTransaction();

            return MuthowifBooking::query()->create([
                'booking_code' => $bookingCode,
                'muthowif_profile_id' => $lockedProfile->id,
                'customer_id' => $request->user()->id,
                'service_type' => MuthowifServiceType::Support,
                'support_package_id' => $package->id,
                'pilgrim_count' => $pilgrimCount,
                'starts_at' => $startsAt,
                'starts_on' => $day->toDateString(),
                'ends_on' => $day->toDateString(),
                'status' => BookingStatus::Pending,
                'package_price_snapshot' => (string) $package->price,
                'package_name_snapshot' => $package->name,
            ]);
        });
    }

    public function dispatchCreated(MuthowifBooking $booking): void
    {
        NotifyMuthowifOfNewBooking::dispatchAfterResponse((string) $booking->getKey());
        CustomerBookingBroadcast::afterResponse($booking);
    }

    public function requestCompletion(MuthowifBooking $booking, string $customerId): void
    {
        if ($booking->status !== BookingStatus::InProgress || ! $booking->isPaid()) {
            throw ValidationException::withMessages([
                'booking' => [__('layanan_pendukung.validation.cannot_request_completion')],
            ]);
        }

        if ($booking->hasCompletionRequested()) {
            throw ValidationException::withMessages([
                'booking' => [__('layanan_pendukung.validation.completion_already_requested')],
            ]);
        }

        $booking->update([
            'completion_requested_at' => now(),
            'completion_requested_by' => $customerId,
        ]);

        NotifyMuthowifOfSupportCompletionRequested::dispatchAfterResponse((string) $booking->getKey());
    }

    public function rejectCompletionRequest(MuthowifBooking $booking): void
    {
        $booking->update([
            'completion_requested_at' => null,
            'completion_requested_by' => null,
        ]);
    }

    /**
     * @return array{completed: bool, credited: bool, error: string|null}
     */
    public function approveCompletion(MuthowifBooking $booking, string $muthowifUserId): array
    {
        if ($booking->status !== BookingStatus::InProgress || ! $booking->hasCompletionRequested()) {
            throw ValidationException::withMessages([
                'booking' => [__('layanan_pendukung.validation.cannot_approve_completion')],
            ]);
        }

        $today = now()->startOfDay()->toDateString();
        $booking->update([
            'ends_on' => $today,
            'completed_at' => now(),
            'completed_by' => $muthowifUserId,
        ]);

        $rating = (int) config('booking.auto_complete_default_rating', 5);

        $result = $this->completion->complete($booking->fresh(), $rating, null);

        if ($result['completed']) {
            NotifyCustomerOfSupportCompletionApproved::dispatchAfterResponse((string) $booking->getKey());
        }

        return $result;
    }

    public function processLifecycle(): array
    {
        $started = 0;
        $extended = 0;

        $toStart = MuthowifBooking::query()
            ->where('service_type', MuthowifServiceType::Support)
            ->where('status', BookingStatus::Confirmed)
            ->where('payment_status', PaymentStatus::Paid)
            ->whereNotNull('starts_at')
            ->where('starts_at', '<=', now())
            ->get();

        foreach ($toStart as $booking) {
            $booking->update(['status' => BookingStatus::InProgress]);
            $started++;
        }

        $today = now()->startOfDay()->toDateString();
        $toExtend = MuthowifBooking::query()
            ->where('service_type', MuthowifServiceType::Support)
            ->where('status', BookingStatus::InProgress)
            ->where(function ($q) use ($today): void {
                $q->whereNull('ends_on')->orWhereDate('ends_on', '<', $today);
            })
            ->get();

        foreach ($toExtend as $booking) {
            $booking->update(['ends_on' => $today]);
            $extended++;
        }

        return ['started' => $started, 'extended' => $extended];
    }
}
