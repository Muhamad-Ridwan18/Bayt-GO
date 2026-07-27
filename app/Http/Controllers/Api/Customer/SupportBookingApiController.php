<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Models\MuthowifBooking;
use App\Models\MuthowifSupportPackage;
use App\Services\SupportBookingService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SupportBookingApiController extends Controller
{
    public function store(Request $request, SupportBookingService $support): JsonResponse
    {
        $this->authorize('create', MuthowifBooking::class);

        $validated = $request->validate([
            'support_package_id' => ['required', 'uuid', 'exists:muthowif_support_packages,id'],
            'starts_at' => ['required', 'date'],
            'pilgrim_count' => ['required', 'integer', 'min:1', 'max:500'],
            'affiliate_code' => ['nullable', 'string', 'max:32'],
        ]);

        $package = MuthowifSupportPackage::query()
            ->whereKey($validated['support_package_id'])
            ->where('is_active', true)
            ->firstOrFail();

        $startsAt = Carbon::parse($validated['starts_at']);

        if ($startsAt->lt(now())) {
            return response()->json([
                'message' => __('bookings.validation.start_past'),
                'errors' => ['starts_at' => [__('bookings.validation.start_past')]],
            ], 422);
        }

        $booking = $support->create(
            $request,
            $package,
            (int) $validated['pilgrim_count'],
            $startsAt
        );

        $support->dispatchCreated($booking);
        Cache::forget('customer_booking_status_counts:'.$request->user()->id);

        return response()->json([
            'message' => __('layanan_pendukung.flash.booking_submitted'),
            'booking_id' => (string) $booking->getKey(),
            'booking_code' => $booking->booking_code,
        ], 201);
    }
}
