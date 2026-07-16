<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\MuthowifBooking;
use App\Models\MuthowifSupportPackage;
use App\Services\SupportBookingService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

class SupportBookingController extends Controller
{
    public function store(Request $request, SupportBookingService $support): RedirectResponse
    {
        $this->authorize('create', MuthowifBooking::class);

        $validated = $request->validate([
            'support_package_id' => ['required', 'uuid', 'exists:muthowif_support_packages,id'],
            'starts_at' => ['required', 'date'],
            'pilgrim_count' => ['required', 'integer', 'min:1', 'max:500'],
        ]);

        $package = MuthowifSupportPackage::query()
            ->whereKey($validated['support_package_id'])
            ->where('is_active', true)
            ->firstOrFail();

        $startsAt = Carbon::parse($validated['starts_at']);

        if ($startsAt->lt(now())) {
            throw ValidationException::withMessages([
                'starts_at' => [__('bookings.validation.start_past')],
            ]);
        }

        $booking = $support->create(
            $request,
            $package,
            (int) $validated['pilgrim_count'],
            $startsAt
        );

        $support->dispatchCreated($booking);
        Cache::forget('customer_booking_status_counts:'.$request->user()->id);

        return redirect()
            ->route('bookings.show', $booking)
            ->with('status', __('layanan_pendukung.flash.booking_submitted'));
    }

    public function resendCompletionCode(Request $request, MuthowifBooking $booking, SupportBookingService $support): RedirectResponse
    {
        $this->authorize('resendSupportCompletionCode', $booking);

        $support->issueCompletionCode($booking, true);

        return redirect()
            ->route('bookings.show', $booking)
            ->with('status', __('layanan_pendukung.flash.completion_code_sent'));
    }
}
