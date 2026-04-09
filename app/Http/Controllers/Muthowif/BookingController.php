<?php

namespace App\Http\Controllers\Muthowif;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Jobs\NotifyCustomerOfApprovedBooking;
use App\Models\MuthowifBooking;
use App\Models\MuthowifServiceAddOn;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
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
            ->orderByRaw("CASE status WHEN 'pending' THEN 0 WHEN 'confirmed' THEN 1 ELSE 2 END")
            ->orderByDesc('starts_on')
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('muthowif.bookings.index', [
            'bookings' => $bookings,
            'addonsById' => $this->addOnsKeyById($bookings),
        ]);
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

        return redirect()
            ->route('muthowif.bookings.index')
            ->with('status', 'Booking disetujui. Jamaah dapat melanjutkan pembayaran.');
    }

    public function cancel(Request $request, MuthowifBooking $booking): RedirectResponse
    {
        $this->authorize('cancelAsMuthowif', $booking);

        $booking->update(['status' => BookingStatus::Cancelled]);

        return redirect()
            ->route('muthowif.bookings.index')
            ->with('status', 'Booking ditolak atau dibatalkan.');
    }
}
