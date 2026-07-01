<?php

namespace App\Http\Controllers\Muthowif;

use App\Enums\BookingChangeRequestStatus;
use App\Enums\BookingStatus;
use App\Enums\MuthowifBookingMuthowifRejectionKind;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Jobs\NotifyCustomerOfApprovedBooking;
use App\Jobs\NotifyCustomerOfBookingRejectedJadwalFull;
use App\Jobs\NotifyCustomerOfRescheduleApproved;
use App\Jobs\NotifyCustomerOfRescheduleRejected;
use App\Jobs\NotifyMuthowifOfNewBooking;
use App\Models\BookingPayment;
use App\Models\BookingRescheduleRequest;
use App\Models\MuthowifBooking;
use App\Models\MuthowifProfile;
use App\Models\MuthowifServiceAddOn;
use App\Services\BookingPendingPaymentEnsurer;
use App\Services\SupportBookingService;
use App\Support\BookingWebLive;
use App\Support\CustomerBookingBroadcast;
use App\Support\PlatformFee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

class BookingController extends Controller
{
    public function index(Request $request): View
    {
        $profile = $request->user()->muthowifProfile;
        abort_unless($profile, 403);

        $statusFilter = $this->resolveBookingStatusFilter($request);
        $bookings = $this->muthowifBookingsIndexQuery($profile, $statusFilter)->paginate(20);
        $bookingStatusCounts = $this->muthowifBookingStatusCounts((string) $profile->getKey());

        return view('muthowif.bookings.index', [
            'bookings' => $bookings,
            'addonsById' => $this->addOnsKeyById($bookings),
            'bookingStatusCounts' => $bookingStatusCounts,
            'statusFilter' => $statusFilter,
        ]);
    }

    public function pendingIncomingCount(Request $request): JsonResponse
    {
        $profile = $request->user()->muthowifProfile;
        abort_unless($profile, 403);

        $n = MuthowifBooking::query()
            ->where('muthowif_profile_id', $profile->id)
            ->where('status', BookingStatus::Pending)
            ->count();

        return response()->json(['pending_count' => $n]);
    }

    public function indexLiveFragment(Request $request): View
    {
        $profile = $request->user()->muthowifProfile;
        abort_unless($profile, 403);

        $statusFilter = $this->resolveBookingStatusFilter($request);
        $bookings = $this->muthowifBookingsIndexQuery($profile, $statusFilter)->paginate(20);
        $bookingStatusCounts = $this->muthowifBookingStatusCounts((string) $profile->getKey());

        return view('muthowif.bookings.partials.index-live', [
            'bookings' => $bookings,
            'addonsById' => $this->addOnsKeyById($bookings),
            'bookingStatusCounts' => $bookingStatusCounts,
            'statusFilter' => $statusFilter,
        ]);
    }

    public function show(Request $request, MuthowifBooking $booking): View
    {
        $profile = $request->user()->muthowifProfile;
        abort_unless($profile, 403);
        abort_unless((string) $booking->muthowif_profile_id === (string) $profile->getKey(), 403);

        $booking->load([
            'customer',
            'supportPackage',
            'muthowifProfile.services.addOns',
            'refundRequests' => fn ($q) => $q->orderByDesc('created_at'),
            'rescheduleRequests' => fn ($q) => $q->orderByDesc('created_at'),
        ]);

        $addonsById = collect();
        $ids = collect($booking->selected_add_on_ids ?? [])->unique()->filter()->values();
        if ($ids->isNotEmpty()) {
            $addonsById = MuthowifServiceAddOn::query()->whereIn('id', $ids)->get()->keyBy('id');
        }

        $earnings = $this->muthowifBookingEarningsViewData($booking, $addonsById, true);

        return view('muthowif.bookings.show', array_merge([
            'booking' => $booking,
            'addonsById' => $addonsById,
        ], $earnings));
    }

    public function showLiveState(Request $request, MuthowifBooking $booking): JsonResponse
    {
        $profile = $request->user()->muthowifProfile;
        abort_unless($profile, 403);
        abort_unless((string) $booking->muthowif_profile_id === (string) $profile->getKey(), 403);

        $booking->refresh();

        return response()->json(BookingWebLive::muthowifShowState($booking, [
            'status' => $request->query('status'),
            'payment_status' => $request->query('payment_status'),
            'emergency_event' => $request->boolean('emergency_event'),
        ]));
    }

    public function showLiveFragment(Request $request, MuthowifBooking $booking): View
    {
        $profile = $request->user()->muthowifProfile;
        abort_unless($profile, 403);
        abort_unless((string) $booking->muthowif_profile_id === (string) $profile->getKey(), 403);

        $tier = (string) $request->query('tier', BookingWebLive::TIER_FULL);

        $addonsById = collect();
        $ids = collect($booking->selected_add_on_ids ?? [])->unique()->filter()->values();
        if ($ids->isNotEmpty()) {
            $addonsById = MuthowifServiceAddOn::query()->whereIn('id', $ids)->get()->keyBy('id');
        }

        if ($tier === BookingWebLive::TIER_DYNAMIC) {
            $booking->load(['customer', 'muthowifProfile.services']);
            $earnings = $this->muthowifBookingEarningsViewData($booking, $addonsById, false);

            return view('muthowif.bookings.partials.show-live-dynamic', array_merge([
                'booking' => $booking,
            ], $earnings));
        }

        $booking->load([
            'customer',
            'supportPackage',
            'muthowifProfile.services.addOns',
            'refundRequests' => fn ($q) => $q->orderByDesc('created_at'),
            'rescheduleRequests' => fn ($q) => $q->orderByDesc('created_at'),
        ]);

        $earnings = $this->muthowifBookingEarningsViewData($booking, $addonsById, true);

        return view('muthowif.bookings.partials.show-grid', array_merge([
            'booking' => $booking,
            'addonsById' => $addonsById,
        ], $earnings));
    }

    public function confirm(Request $request, MuthowifBooking $booking): RedirectResponse
    {
        $this->authorize('confirm', $booking);

        $booking->loadMissing(['muthowifProfile.services.addOns']);
        $profile = $booking->muthowifProfile;
        if ($profile === null) {
            return redirect()
                ->route('muthowif.bookings.index')
                ->with('error', 'Profil muthowif tidak ditemukan.');
        }

        $start = $booking->starts_on->copy()->startOfDay();
        $end = ($booking->ends_on ?? $booking->starts_on)->copy()->startOfDay();
        if (! $profile->isJadwalAvailableForRange($start, $end, (string) $booking->getKey())) {
            return redirect()
                ->route('muthowif.bookings.show', $booking)
                ->with(
                    'error',
                    'Jadwal tanggal ini tidak bisa disetujui karena sudah dipakai booking lain yang disetujui. Batalkan atau tolak pesanan yang bentrok, atau minta jamaah mengubah tanggal.'
                );
        }

        $total = $booking->computeTotalAmount();
        $bookingIdStr = (string) $booking->getKey();
        $broadcastIds = [$bookingIdStr];
        $customerIdsToInvalidate = [(string) $booking->customer_id];
        $rejectedBookingIds = [];

        DB::transaction(function () use ($booking, $profile, $start, $end, $total, $bookingIdStr, &$broadcastIds, &$customerIdsToInvalidate, &$rejectedBookingIds): void {
            $confirmPayload = [
                'status' => BookingStatus::Confirmed,
                'payment_status' => PaymentStatus::Pending,
                'total_amount' => $total,
            ];

            if ($booking->isSupport()) {
                $confirmPayload['ends_on'] = $start->toDateString();
            }

            $booking->update($confirmPayload);

            // Otomatis tolak pesanan lain yang pending dan bentrok pada jadwal ini
            $overlappingPending = MuthowifBooking::query()
                ->where('muthowif_profile_id', $profile->id)
                ->where('status', BookingStatus::Pending)
                ->whereKeyNot($bookingIdStr)
                ->where('starts_on', '<=', $end->toDateString())
                ->where('ends_on', '>=', $start->toDateString())
                ->get();

            foreach ($overlappingPending as $other) {
                $other->update([
                    'status' => BookingStatus::Cancelled,
                    'muthowif_rejection_kind' => MuthowifBookingMuthowifRejectionKind::JadwalFull,
                    'muthowif_rejection_note' => __('bookings.show.muthowif_rejection_auto_collision'),
                ]);
                $rejectedBookingIds[] = (string) $other->getKey();
                $broadcastIds[] = (string) $other->getKey();
                $customerIdsToInvalidate[] = (string) $other->customer_id;
            }
        });

        app(BookingPendingPaymentEnsurer::class)->ensure($booking->fresh());

        NotifyCustomerOfApprovedBooking::dispatchAfterResponse((string) $booking->getKey());
        foreach ($rejectedBookingIds as $otherId) {
            NotifyCustomerOfBookingRejectedJadwalFull::dispatchAfterResponse($otherId);
        }
        CustomerBookingBroadcast::afterResponseMany($broadcastIds);
        foreach (array_unique($customerIdsToInvalidate) as $customerId) {
            Cache::forget('customer_booking_status_counts:'.$customerId);
        }

        return redirect()
            ->route('muthowif.bookings.show', $booking)
            ->with('status', 'Booking disetujui. Jamaah dapat melanjutkan pembayaran.');
    }

    public function cancel(Request $request, MuthowifBooking $booking): RedirectResponse
    {
        $this->authorize('cancelAsMuthowif', $booking);

        $rules = [
            'muthowif_rejection_note' => ['nullable', 'string', 'max:2000'],
        ];

        if ($booking->status === BookingStatus::Pending) {
            $rules['muthowif_rejection_kind'] = [
                'required',
                Rule::enum(MuthowifBookingMuthowifRejectionKind::class),
            ];
        }

        $validated = $request->validate($rules);

        try {
            $kind = null;
            $note = null;

            if ($booking->status === BookingStatus::Pending) {
                $rawKind = $validated['muthowif_rejection_kind'];

                $kind = $rawKind instanceof MuthowifBookingMuthowifRejectionKind
                    ? $rawKind
                    : MuthowifBookingMuthowifRejectionKind::from((string) $rawKind);

                $note = filled($validated['muthowif_rejection_note'] ?? null)
                    ? trim((string) $validated['muthowif_rejection_note'])
                    : null;
            }

            $booking->update([
                'status' => BookingStatus::Cancelled,
                'muthowif_rejection_kind' => $kind,
                'muthowif_rejection_note' => $note,
            ]);

            NotifyCustomerOfBookingRejectedJadwalFull::dispatchAfterResponse(
                (string) $booking->getKey()
            );

            CustomerBookingBroadcast::afterResponse($booking->fresh());

            return redirect()
                ->route('muthowif.bookings.index')
                ->with('status', 'Booking ditolak atau dibatalkan.');
        } catch (Throwable $e) {
            Log::error('Failed to cancel booking', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->withErrors([
                'error' => 'Terjadi kesalahan saat membatalkan booking. Silakan coba lagi.',
            ]);
        }
    }

    public function approveReschedule(Request $request, MuthowifBooking $booking, BookingRescheduleRequest $rescheduleRequest): RedirectResponse
    {
        $this->authorize('decidePostPayChange', $booking);
        abort_unless((string) $rescheduleRequest->muthowif_booking_id === (string) $booking->getKey(), 404);

        $validated = $request->validate([
            'muthowif_note' => ['nullable', 'string', 'max:2000'],
        ]);

        if (! $rescheduleRequest->isPending()) {
            return redirect()
                ->route('muthowif.bookings.show', $booking)
                ->with('error', 'Pengajuan reschedule ini sudah diproses.');
        }

        $profile = $booking->muthowifProfile;
        if ($profile === null) {
            return redirect()
                ->route('muthowif.bookings.show', $booking)
                ->with('error', 'Profil muthowif tidak ditemukan.');
        }

        $start = $rescheduleRequest->new_starts_on->copy()->startOfDay();
        $end = $rescheduleRequest->new_ends_on->copy()->startOfDay();

        if (! $profile->isJadwalAvailableForRange($start, $end, (string) $booking->getKey())) {
            return redirect()
                ->route('muthowif.bookings.show', $booking)
                ->with('error', 'Jadwal tanggal yang diajukan tidak lagi tersedia. Minta jamaah mengajukan tanggal lain.');
        }

        $oldNights = $booking->billingNightsInclusive();
        $newNights = MuthowifBooking::inclusiveSpanDays($start, $end);
        if ($oldNights !== $newNights) {
            return redirect()
                ->route('muthowif.bookings.show', $booking)
                ->with('error', 'Jumlah hari pada pengajuan tidak sama dengan booking.');
        }

        $broadcastIds = [(string) $booking->getKey()];
        $rejectedBookingIds = [];

        try {
            DB::transaction(function () use ($booking, $profile, $rescheduleRequest, $request, $validated, $start, $end, &$broadcastIds, &$rejectedBookingIds): void {
                $rescheduleRequest->refresh()->lockForUpdate();
                $booking->refresh()->lockForUpdate();

                if (! $rescheduleRequest->isPending()) {
                    throw new RuntimeException('Pengajuan reschedule sudah diproses.');
                }

                $rescheduleRequest->update([
                    'status' => BookingChangeRequestStatus::Approved,
                    'decided_at' => now(),
                    'decided_by' => $request->user()->id,
                    'muthowif_note' => filled($validated['muthowif_note'] ?? null) ? trim((string) $validated['muthowif_note']) : null,
                ]);

                $booking->update([
                    'starts_on' => $start->toDateString(),
                    'ends_on' => $end->toDateString(),
                ]);

                // Otomatis tolak pesanan lain yang pending dan bentrok pada jadwal baru ini
                $overlappingPending = MuthowifBooking::query()
                    ->where('muthowif_profile_id', $profile->id)
                    ->where('status', BookingStatus::Pending)
                    ->whereKeyNot($booking->getKey())
                    ->where('starts_on', '<=', $end->toDateString())
                    ->where('ends_on', '>=', $start->toDateString())
                    ->get();

                foreach ($overlappingPending as $other) {
                    $other->update([
                        'status' => BookingStatus::Cancelled,
                        'muthowif_rejection_kind' => MuthowifBookingMuthowifRejectionKind::JadwalFull,
                        'muthowif_rejection_note' => __('bookings.show.muthowif_rejection_auto_collision'),
                    ]);
                    $rejectedBookingIds[] = (string) $other->getKey();
                    $broadcastIds[] = (string) $other->getKey();
                }
            });
        } catch (Throwable $e) {
            return redirect()
                ->route('muthowif.bookings.show', $booking)
                ->with('error', $e->getMessage());
        }

        $booking = $booking->fresh();
        $rescheduleRequest = $rescheduleRequest->fresh();
        NotifyCustomerOfRescheduleApproved::dispatchAfterResponse(
            (string) $booking->getKey(),
            (string) $rescheduleRequest->getKey(),
        );
        foreach ($rejectedBookingIds as $otherId) {
            NotifyCustomerOfBookingRejectedJadwalFull::dispatchAfterResponse($otherId);
        }
        CustomerBookingBroadcast::afterResponseMany($broadcastIds);

        return redirect()
            ->route('muthowif.bookings.show', $booking)
            ->with('status', 'Reschedule disetujui. Tanggal booking telah diperbarui.');
    }

    public function rejectReschedule(Request $request, MuthowifBooking $booking, BookingRescheduleRequest $rescheduleRequest): RedirectResponse
    {
        $this->authorize('decidePostPayChange', $booking);
        abort_unless((string) $rescheduleRequest->muthowif_booking_id === (string) $booking->getKey(), 404);

        $validated = $request->validate([
            'muthowif_note' => ['nullable', 'string', 'max:2000'],
        ]);

        if (! $rescheduleRequest->isPending()) {
            return redirect()
                ->route('muthowif.bookings.show', $booking)
                ->with('error', 'Pengajuan reschedule ini sudah diproses.');
        }

        $rescheduleRequest->update([
            'status' => BookingChangeRequestStatus::Rejected,
            'decided_at' => now(),
            'decided_by' => $request->user()->id,
            'muthowif_note' => filled($validated['muthowif_note'] ?? null) ? trim((string) $validated['muthowif_note']) : null,
        ]);

        NotifyCustomerOfRescheduleRejected::dispatchAfterResponse(
            (string) $booking->getKey(),
            (string) $rescheduleRequest->getKey(),
        );
        CustomerBookingBroadcast::afterResponse($booking->fresh());

        return redirect()
            ->route('muthowif.bookings.show', $booking)
            ->with('status', 'Pengajuan reschedule ditolak.');
    }

    /**
     * @param  LengthAwarePaginator<int, MuthowifBooking>  $bookings
     * @return Collection<string, MuthowifServiceAddOn>
     */
    private function addOnsKeyById(LengthAwarePaginator $bookings): Collection
    {
        $ids = $bookings->getCollection()->flatMap(fn (MuthowifBooking $b) => $b->selected_add_on_ids ?? [])->unique()->filter()->values();
        if ($ids->isEmpty()) {
            return collect();
        }

        return MuthowifServiceAddOn::query()->whereIn('id', $ids)->get()->keyBy('id');
    }

    /**
     * @param  Collection<string, MuthowifServiceAddOn>  $addonsById
     * @return array{
     *   referralRewardFromPay: float,
     *   daily: float,
     *   nights: int,
     *   serviceSubtotal: float,
     *   addonLines: Collection,
     *   sameHotelLine: float,
     *   transportLine: float,
     *   muthowifFee: float,
     *   muthowifNetAfterReferral: float
     * }
     */
    private function muthowifBookingEarningsViewData(
        MuthowifBooking $booking,
        Collection $addonsById,
        bool $loadReferralFromPayments,
    ): array {
        $booking->loadMissing(['muthowifProfile.services']);
        $service = $booking->muthowifProfile?->services->firstWhere('type', $booking->service_type);
        $nights = $booking->billingNightsInclusive();
        $daily = (float) ($booking->daily_price_snapshot ?? ($service ? $service->daily_price : 0.0));
        $serviceSubtotal = (float) ($nights * $daily);

        $addonLines = collect();
        if (! empty($booking->add_ons_snapshot)) {
            $addonLines = collect($booking->add_ons_snapshot)->map(fn ($a) => (object) $a);
        } elseif (! empty($booking->selected_add_on_ids)) {
            foreach ($booking->selected_add_on_ids as $aid) {
                if (isset($addonsById[$aid])) {
                    $addonLines->push($addonsById[$aid]);
                }
            }
        }

        $addonsSum = $addonLines->sum(fn ($a) => (float) $a->price);
        $sameHotelPrice = (float) ($booking->same_hotel_price_snapshot ?? ($service ? $service->same_hotel_price_per_day : 0.0));
        $sameHotelLine = $booking->with_same_hotel ? ($nights * $sameHotelPrice) : 0.0;
        $transportPrice = (float) ($booking->transport_price_snapshot ?? ($service ? (float) $service->transport_price_flat : 0.0));
        $transportLine = $booking->with_transport ? $transportPrice : 0.0;
        $totalGross = (float) ($serviceSubtotal + $addonsSum + $sameHotelLine + $transportLine);
        $split = PlatformFee::split($totalGross);
        $muthowifNet = (float) ($split['muthowif_net'] ?? 0.0);
        $muthowifFee = (float) ($split['muthowif_fee'] ?? 0.0);

        $referralRewardFromPay = 0.0;
        if ($loadReferralFromPayments) {
            $settledPaymentForReferral = BookingPayment::query()
                ->where('muthowif_booking_id', $booking->getKey())
                ->whereIn('status', ['settlement', 'capture'])
                ->orderByDesc('settled_at')
                ->first();
            $pendingPaymentForReferral = $settledPaymentForReferral === null
                ? BookingPayment::query()
                    ->where('muthowif_booking_id', $booking->getKey())
                    ->where('status', 'pending')
                    ->orderByDesc('created_at')
                    ->first()
                : null;
            $payForReferral = $settledPaymentForReferral ?? $pendingPaymentForReferral;
            $referralRewardFromPay = $payForReferral !== null
                ? round((float) ($payForReferral->referral_reward_amount ?? 0), 2)
                : 0.0;
        }

        $muthowifNetAfterReferral = round(max(0.0, $muthowifNet - $referralRewardFromPay), 2);

        return [
            'referralRewardFromPay' => $referralRewardFromPay,
            'daily' => $daily,
            'nights' => $nights,
            'serviceSubtotal' => $serviceSubtotal,
            'addonLines' => $addonLines,
            'sameHotelLine' => $sameHotelLine,
            'transportLine' => $transportLine,
            'muthowifFee' => $muthowifFee,
            'muthowifNetAfterReferral' => $muthowifNetAfterReferral,
        ];
    }

    private function resolveBookingStatusFilter(Request $request): ?string
    {
        $status = $request->query('status');
        if (! is_string($status) || $status === '') {
            return null;
        }

        return in_array($status, array_map(
            static fn (BookingStatus $case) => $case->value,
            BookingStatus::cases(),
        ), true) ? $status : null;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder<MuthowifBooking>
     */
    private function muthowifBookingsIndexQuery(MuthowifProfile $profile, ?string $statusFilter)
    {
        $query = MuthowifBooking::query()
            ->where('muthowif_profile_id', $profile->id)
            ->with(['customer'])
            ->withCount([
                'rescheduleRequests as pending_reschedule_requests_count' => fn ($q) => $q->where('status', BookingChangeRequestStatus::Pending),
            ])
            ->orderByRaw("CASE status WHEN 'pending' THEN 0 WHEN 'confirmed' THEN 1 ELSE 2 END")
            ->orderByDesc('starts_on')
            ->orderByDesc('created_at');

        if ($statusFilter !== null) {
            $query->where('status', $statusFilter);
        }

        return $query;
    }

    /**
     * @return array<string, int>
     */
    private function muthowifBookingStatusCounts(string $profileId): array
    {
        $statusAggregates = MuthowifBooking::query()
            ->where('muthowif_profile_id', $profileId)
            ->toBase()
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        return collect(BookingStatus::cases())->mapWithKeys(
            fn (BookingStatus $status) => [$status->value => (int) ($statusAggregates[$status->value] ?? 0)]
        )->all();
    }

    public function approveSupportCompletion(Request $request, MuthowifBooking $booking, SupportBookingService $support): RedirectResponse
    {
        $this->authorize('approveSupportCompletion', $booking);

        $result = $support->approveCompletion($booking, (string) $request->user()->id);

        if (! $result['completed']) {
            return redirect()
                ->route('muthowif.bookings.show', $booking)
                ->with('error', $result['error'] ?? __('layanan_pendukung.flash.completion_approve_failed'));
        }

        CustomerBookingBroadcast::afterResponse($booking->fresh());
        Cache::forget('customer_booking_status_counts:'.$booking->customer_id);

        return redirect()
            ->route('muthowif.bookings.show', $booking)
            ->with('status', __('layanan_pendukung.flash.completion_approved'));
    }

    public function rejectSupportCompletion(Request $request, MuthowifBooking $booking, SupportBookingService $support): RedirectResponse
    {
        $this->authorize('rejectSupportCompletion', $booking);

        $validated = $request->validate([
            'rejection_note' => ['nullable', 'string', 'max:2000'],
        ]);

        $support->rejectCompletionRequest($booking);

        return redirect()
            ->route('muthowif.bookings.show', $booking)
            ->with('status', __('layanan_pendukung.flash.completion_rejected'));
    }
}
