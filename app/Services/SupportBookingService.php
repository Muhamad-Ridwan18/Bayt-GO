<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\MuthowifServiceType;
use App\Enums\PaymentStatus;
use App\Jobs\NotifyCustomerOfSupportCompletionApproved;
use App\Models\MuthowifBooking;
use App\Models\MuthowifProfile;
use App\Models\MuthowifSupportPackage;
use App\Support\CustomerBookingBroadcast;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class SupportBookingService
{
    private const SEND_COOLDOWN_SECONDS = 60;

    private const MAX_VERIFY_ATTEMPTS = 5;

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

        $bounds = $package->pilgrimBounds();
        $min = $bounds['min'];
        $max = $bounds['max'];
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

            $attributes = [
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
            ];

            $affiliateSnapshot = app(AffiliateAttributionService::class)->snapshotForBooking(
                new MuthowifBooking($attributes),
                \App\Support\AffiliateReferralCapture::resolveForBooking($request, $request->input('affiliate_code')),
                (string) $request->user()->id,
                $request->user()->isCompanyCustomer(),
            );

            return MuthowifBooking::query()->create(array_merge($attributes, $affiliateSnapshot));
        });
    }

    public function dispatchCreated(MuthowifBooking $booking): void
    {
        if ($booking->affiliate_id !== null) {
            app(AffiliateReferralService::class)->markConverted($booking);
            app(AffiliateNotifier::class)->referralBooked($booking);
        }

        app(BookingNotificationDispatcher::class)->dispatchCreated($booking);
        CustomerBookingBroadcast::afterResponse($booking);
    }

    /**
     * Generate (or regenerate) completion code, persist hash + plaintext for customer UI, send WA.
     *
     * @throws ValidationException
     */
    public function issueCompletionCode(MuthowifBooking $booking, bool $forceResend = false, bool $notify = true): void
    {
        if (! $booking->isSupport() || ! $booking->isPaid()) {
            throw ValidationException::withMessages([
                'booking' => [__('layanan_pendukung.validation.cannot_issue_completion_code')],
            ]);
        }

        if (! in_array($booking->status, [BookingStatus::Confirmed, BookingStatus::InProgress], true)) {
            throw ValidationException::withMessages([
                'booking' => [__('layanan_pendukung.validation.cannot_issue_completion_code')],
            ]);
        }

        $bookingId = (string) $booking->getKey();

        if ($forceResend || $booking->completion_code_hash !== null) {
            if (Cache::has($this->sendCooldownKey($bookingId))) {
                throw ValidationException::withMessages([
                    'code' => [__('layanan_pendukung.validation.completion_code_resend_cooldown')],
                ]);
            }
        }

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $booking->update([
            'completion_code' => $code,
            'completion_code_hash' => hash('sha256', $code),
            'completion_code_sent_at' => now(),
            'completion_requested_at' => null,
            'completion_requested_by' => null,
        ]);

        Cache::forget($this->verifyAttemptsKey($bookingId));
        Cache::put($this->sendCooldownKey($bookingId), true, now()->addSeconds(self::SEND_COOLDOWN_SECONDS));

        if ($notify) {
            app(MuthowifBookingWhatsAppNotifier::class)
                ->notifyCustomerSupportCompletionCode($booking->fresh(), $code);
        }
    }

    /**
     * Issue code once after payment settles (idempotent if hash already set).
     */
    public function issueCompletionCodeAfterPayment(MuthowifBooking $booking): void
    {
        if (! $booking->isSupport() || ! $booking->isPaid()) {
            return;
        }

        if (! in_array($booking->status, [BookingStatus::Confirmed, BookingStatus::InProgress], true)) {
            return;
        }

        if (filled($booking->completion_code_hash)) {
            return;
        }

        try {
            $this->issueCompletionCode($booking->fresh(), false, false);
        } catch (ValidationException) {
            // ignore cooldown / race during payment hooks
        }
    }

    /**
     * @return array{completed: bool, credited: bool, error: string|null}
     *
     * @throws ValidationException
     */
    public function completeWithCode(MuthowifBooking $booking, string $code, string $muthowifUserId): array
    {
        if (! $booking->isSupport()
            || ! $booking->isPaid()
            || ! in_array($booking->status, [BookingStatus::Confirmed, BookingStatus::InProgress], true)
            || blank($booking->completion_code_hash)) {
            throw ValidationException::withMessages([
                'code' => [__('layanan_pendukung.validation.cannot_complete_with_code')],
            ]);
        }

        $bookingId = (string) $booking->getKey();
        $attemptsKey = $this->verifyAttemptsKey($bookingId);

        if (RateLimiter::tooManyAttempts($attemptsKey, self::MAX_VERIFY_ATTEMPTS)) {
            throw ValidationException::withMessages([
                'code' => [__('layanan_pendukung.validation.completion_code_too_many_attempts')],
            ]);
        }

        $normalized = preg_replace('/\D+/', '', $code) ?? '';
        if (strlen($normalized) !== 6) {
            RateLimiter::hit($attemptsKey, 3600);
            throw ValidationException::withMessages([
                'code' => [__('layanan_pendukung.validation.completion_code_invalid_format')],
            ]);
        }

        if (! hash_equals((string) $booking->completion_code_hash, hash('sha256', $normalized))) {
            RateLimiter::hit($attemptsKey, 3600);
            throw ValidationException::withMessages([
                'code' => [__('layanan_pendukung.validation.completion_code_mismatch')],
            ]);
        }

        $today = now()->startOfDay()->toDateString();
        $booking->update([
            'ends_on' => $today,
            'completed_at' => now(),
            'completed_by' => $muthowifUserId,
            'completion_code' => null,
            'completion_code_hash' => null,
            'completion_code_sent_at' => null,
            'completion_requested_at' => null,
            'completion_requested_by' => null,
        ]);

        Cache::forget($attemptsKey);
        Cache::forget($this->sendCooldownKey($bookingId));

        $rating = (int) config('booking.auto_complete_default_rating', 5);
        $result = $this->completion->complete($booking->fresh(), $rating, null);

        if ($result['completed']) {
            NotifyCustomerOfSupportCompletionApproved::dispatchAfterResponse($bookingId);
        }

        return $result;
    }

    /**
     * @return array{started: int, extended: int, codes_issued: int}
     */
    public function processLifecycle(): array
    {
        $started = 0;
        $extended = 0;
        $codesIssued = 0;

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

        $missingCode = MuthowifBooking::query()
            ->where('service_type', MuthowifServiceType::Support)
            ->whereIn('status', [BookingStatus::Confirmed, BookingStatus::InProgress])
            ->where('payment_status', PaymentStatus::Paid)
            ->whereNull('completion_code_hash')
            ->get();

        foreach ($missingCode as $booking) {
            try {
                $this->issueCompletionCode($booking, false);
                $codesIssued++;
            } catch (ValidationException) {
                // ignore
            }
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

        return ['started' => $started, 'extended' => $extended, 'codes_issued' => $codesIssued];
    }

    private function sendCooldownKey(string $bookingId): string
    {
        return 'support_completion_code_cooldown:'.$bookingId;
    }

    private function verifyAttemptsKey(string $bookingId): string
    {
        return 'support_completion_code_attempts:'.$bookingId;
    }
}
