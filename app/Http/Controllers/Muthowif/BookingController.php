<?php

namespace App\Http\Controllers\Muthowif;

use App\Enums\BookingChangeRequestStatus;
use App\Enums\BookingStatus;
use App\Enums\MuthowifBookingMuthowifRejectionKind;
use App\Enums\PaymentStatus;
use App\Events\CustomerBookingUpdated;
use App\Http\Controllers\Controller;
use App\Jobs\NotifyCustomerOfApprovedBooking;
use App\Jobs\NotifyCustomerOfBookingReferredToPeer;
use App\Jobs\NotifyCustomerOfBookingRejectedJadwalFull;
use App\Jobs\NotifyCustomerOfRescheduleApproved;
use App\Jobs\NotifyCustomerOfRescheduleRejected;
use App\Jobs\NotifyMuthowifOfNewBooking;
use App\Enums\BookingReplacementStatus;
use App\Models\BookingIncident;
use App\Models\BookingReplacement;
use App\Models\BookingRescheduleRequest;
use App\Models\MuthowifBooking;
use App\Models\MuthowifProfile;
use App\Models\MuthowifServiceAddOn;
use App\Services\BookingPeerReferralService;
use App\Services\BookingPendingPaymentEnsurer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;
use Illuminate\Support\Facades\Log;
use Throwable;

class BookingController extends Controller
{
    public function index(Request $request): View
    {
        $profile = $request->user()->muthowifProfile;
        abort_unless($profile, 403);

        $bookings = MuthowifBooking::query()
            ->where('muthowif_profile_id', $profile->id)
            ->with(['customer', 'muthowifProfile.services'])
            ->withCount([
                'rescheduleRequests as pending_reschedule_requests_count' => fn ($q) => $q->where('status', BookingChangeRequestStatus::Pending),
            ])
            ->orderByRaw("CASE status WHEN 'pending' THEN 0 WHEN 'confirmed' THEN 1 ELSE 2 END")
            ->orderByDesc('starts_on')
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('muthowif.bookings.index', [
            'bookings' => $bookings,
            'addonsById' => $this->addOnsKeyById($bookings),
            'peerRecommendByBooking' => $this->peerRecommendTargetsByBookingId($profile, $bookings),
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

        $bookings = MuthowifBooking::query()
            ->where('muthowif_profile_id', $profile->id)
            ->with(['customer', 'muthowifProfile.services'])
            ->withCount([
                'rescheduleRequests as pending_reschedule_requests_count' => fn ($q) => $q->where('status', BookingChangeRequestStatus::Pending),
            ])
            ->orderByRaw("CASE status WHEN 'pending' THEN 0 WHEN 'confirmed' THEN 1 ELSE 2 END")
            ->orderByDesc('starts_on')
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('muthowif.bookings.partials.index-live', [
            'bookings' => $bookings,
            'addonsById' => $this->addOnsKeyById($bookings),
            'peerRecommendByBooking' => $this->peerRecommendTargetsByBookingId($profile, $bookings),
        ]);
    }

    public function show(Request $request, MuthowifBooking $booking): View
    {
        $profile = $request->user()->muthowifProfile;
        abort_unless($profile && (string) $booking->muthowif_profile_id === (string) $profile->id, 403);

        $booking->load([
            'customer',
            'muthowifProfile.services.addOns',
            'refundRequests' => fn ($q) => $q->orderByDesc('created_at'),
            'rescheduleRequests' => fn ($q) => $q->orderByDesc('created_at'),
        ]);

        $addonsById = collect();
        $ids = collect($booking->selected_add_on_ids ?? [])->unique()->filter()->values();
        if ($ids->isNotEmpty()) {
            $addonsById = MuthowifServiceAddOn::query()->whereIn('id', $ids)->get()->keyBy('id');
        }

        $peerRecommendTargets = app(BookingPeerReferralService::class)->listCandidates($booking, $profile);
        $booking->syncServicePhase();
        $openIncident = $booking->openIncident();
        $incomingReplacement = BookingReplacement::query()
            ->where('booking_incident_id', $openIncident?->getKey())
            ->where('replacement_muthowif_profile_id', $profile->getKey())
            ->where('status', BookingReplacementStatus::AwaitingMuthowifConfirm)
            ->first();

        return view('muthowif.bookings.show', [
            'booking' => $booking,
            'addonsById' => $addonsById,
            'peerRecommendTargets' => $peerRecommendTargets,
            'openIncident' => $openIncident,
            'incomingReplacement' => $incomingReplacement,
        ]);
    }

    public function showLiveFragment(Request $request, MuthowifBooking $booking): View
    {
        $profile = $request->user()->muthowifProfile;
        abort_unless($profile && (string) $booking->muthowif_profile_id === (string) $profile->id, 403);

        $booking->load([
            'customer',
            'muthowifProfile.services.addOns',
            'refundRequests' => fn ($q) => $q->orderByDesc('created_at'),
            'rescheduleRequests' => fn ($q) => $q->orderByDesc('created_at'),
        ]);

        $addonsById = collect();
        $ids = collect($booking->selected_add_on_ids ?? [])->unique()->filter()->values();
        if ($ids->isNotEmpty()) {
            $addonsById = MuthowifServiceAddOn::query()->whereIn('id', $ids)->get()->keyBy('id');
        }

        $peerRecommendTargets = app(BookingPeerReferralService::class)->listCandidates($booking, $profile);

        $booking->syncServicePhase();
        $openIncident = $booking->openIncident();
        $incomingReplacement = BookingReplacement::query()
            ->where('booking_incident_id', $openIncident?->getKey())
            ->where('replacement_muthowif_profile_id', $profile->getKey())
            ->where('status', BookingReplacementStatus::AwaitingMuthowifConfirm)
            ->first();

        return view('muthowif.bookings.partials.show-live', [
            'booking' => $booking,
            'addonsById' => $addonsById,
            'peerRecommendTargets' => $peerRecommendTargets,
            'openIncident' => $openIncident,
            'incomingReplacement' => $incomingReplacement,
        ]);
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
        $end = $booking->ends_on->copy()->startOfDay();
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

        DB::transaction(function () use ($booking, $profile, $start, $end, $total, $bookingIdStr): void {
            $booking->update([
                'status' => BookingStatus::Confirmed,
                'payment_status' => PaymentStatus::Pending,
                'total_amount' => $total,
            ]);

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
                NotifyCustomerOfBookingRejectedJadwalFull::dispatchAfterResponse((string) $other->getKey());
                broadcast(new CustomerBookingUpdated($other->fresh()));
            }
        });

        app(BookingPendingPaymentEnsurer::class)->ensure($booking->fresh());

        NotifyCustomerOfApprovedBooking::dispatchAfterResponse($bookingIdStr);
        broadcast(new CustomerBookingUpdated($booking->fresh()));

        return redirect()
            ->route('muthowif.bookings.index')
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

            if ($kind === MuthowifBookingMuthowifRejectionKind::JadwalFull) {
                NotifyCustomerOfBookingRejectedJadwalFull::dispatchAfterResponse(
                    (string) $booking->getKey()
                );
            }

            broadcast(new CustomerBookingUpdated($booking->fresh()));

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

    public function recommendToPeer(Request $request, MuthowifBooking $booking): RedirectResponse
    {
        $profile = $request->user()->muthowifProfile;
        abort_unless($profile && (string) $booking->muthowif_profile_id === (string) $profile->id, 403);

        $this->authorize('recommendToPeer', $booking);

        $validated = $request->validate([
            'target_muthowif_profile_id' => ['required', 'uuid', 'exists:muthowif_profiles,id'],
        ]);

        /** @var MuthowifProfile $target */
        $target = MuthowifProfile::query()->findOrFail($validated['target_muthowif_profile_id']);

        $booking->loadMissing('muthowifProfile.user');
        $previousName = (string) ($booking->muthowifProfile?->user?->name ?? '');

        try {
            app(BookingPeerReferralService::class)->transfer($booking, $target, $profile);
        } catch (RuntimeException $e) {
            return redirect()
                ->route('muthowif.bookings.show', $booking)
                ->with('error', $e->getMessage());
        }

        $freshId = (string) $booking->fresh()->getKey();

        NotifyMuthowifOfNewBooking::dispatchAfterResponse($freshId);
        NotifyCustomerOfBookingReferredToPeer::dispatchAfterResponse($freshId, $previousName);
        broadcast(new CustomerBookingUpdated($booking->fresh()));

        return redirect()
            ->route('muthowif.bookings.index')
            ->with('status', __('muthowif.bookings.refer_flash_success'));
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

        try {
            DB::transaction(function () use ($booking, $rescheduleRequest, $request, $validated, $start, $end): void {
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
                    NotifyCustomerOfBookingRejectedJadwalFull::dispatchAfterResponse((string) $other->getKey());
                    broadcast(new CustomerBookingUpdated($other->fresh()));
                }
            });
        } catch (\Throwable $e) {
            return redirect()
                ->route('muthowif.bookings.show', $booking)
                ->with('error', $e->getMessage());
        }

        NotifyCustomerOfRescheduleApproved::dispatchAfterResponse((string) $booking->getKey(), (string) $rescheduleRequest->getKey());
        broadcast(new CustomerBookingUpdated($booking->fresh()));

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

        NotifyCustomerOfRescheduleRejected::dispatchAfterResponse((string) $booking->getKey(), (string) $rescheduleRequest->getKey());
        broadcast(new CustomerBookingUpdated($booking->fresh()));

        return redirect()
            ->route('muthowif.bookings.show', $booking)
            ->with('status', 'Pengajuan reschedule ditolak.');
    }

    /**
     * @param  LengthAwarePaginator<int, MuthowifBooking>  $bookings
     * @return array<string, Collection<int, MuthowifProfile>>
     */
    private function peerRecommendTargetsByBookingId(MuthowifProfile $profile, LengthAwarePaginator $bookings): array
    {
        $referral = app(BookingPeerReferralService::class);
        $map = [];
        foreach ($bookings as $booking) {
            if ($booking->status !== BookingStatus::Pending) {
                continue;
            }
            $map[(string) $booking->getKey()] = $referral->listCandidates($booking, $profile);
        }

        return $map;
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
}
