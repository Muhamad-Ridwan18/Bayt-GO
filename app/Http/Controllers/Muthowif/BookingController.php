<?php

namespace App\Http\Controllers\Muthowif;

use App\Enums\BookingChangeRequestStatus;
use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Events\CustomerBookingUpdated;
use App\Http\Controllers\Controller;
use App\Jobs\NotifyCustomerOfApprovedBooking;
use App\Jobs\NotifyCustomerOfRescheduleApproved;
use App\Jobs\NotifyCustomerOfRescheduleRejected;
use App\Models\BookingRescheduleRequest;
use App\Models\MuthowifBooking;
use App\Models\MuthowifServiceAddOn;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

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
        ]);
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

        return view('muthowif.bookings.show', [
            'booking' => $booking,
            'addonsById' => $addonsById,
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

        return view('muthowif.bookings.partials.show-live', [
            'booking' => $booking,
            'addonsById' => $addonsById,
        ]);
    }

    public function confirm(Request $request, MuthowifBooking $booking): RedirectResponse
    {
        $this->authorize('confirm', $booking);

        $booking->loadMissing(['muthowifProfile.services.addOns']);
        $total = $booking->computeTotalAmount();

        $booking->update([
            'status' => BookingStatus::Confirmed,
            'payment_status' => PaymentStatus::Pending,
            'total_amount' => $total,
        ]);
        NotifyCustomerOfApprovedBooking::dispatchAfterResponse((string) $booking->getKey());
        broadcast(new CustomerBookingUpdated($booking->fresh()));

        return redirect()
            ->route('muthowif.bookings.index')
            ->with('status', 'Booking disetujui. Jamaah dapat melanjutkan pembayaran.');
    }

    public function cancel(Request $request, MuthowifBooking $booking): RedirectResponse
    {
        $this->authorize('cancelAsMuthowif', $booking);

        $booking->update(['status' => BookingStatus::Cancelled]);
        broadcast(new CustomerBookingUpdated($booking->fresh()));

        return redirect()
            ->route('muthowif.bookings.index')
            ->with('status', 'Booking ditolak atau dibatalkan.');
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

        if (! $profile->isSlotAvailableForRange($start, $end, (string) $booking->getKey())) {
            return redirect()
                ->route('muthowif.bookings.show', $booking)
                ->with('error', 'Slot tanggal yang diajukan tidak lagi tersedia. Minta jamaah mengajukan tanggal lain.');
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
                    throw new \RuntimeException('Pengajuan reschedule sudah diproses.');
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
