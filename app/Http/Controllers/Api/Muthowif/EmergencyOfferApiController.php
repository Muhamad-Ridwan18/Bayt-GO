<?php

namespace App\Http\Controllers\Api\Muthowif;

use App\Http\Controllers\Controller;
use App\Models\BookingReplacementOffer;
use App\Services\Emergency\EmergencyReplacementService;
use App\Support\MuthowifEmergencyOfferCounts;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmergencyOfferApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $profile = $request->user()->muthowifProfile;
        abort_unless($profile, 403);

        $offers = BookingReplacementOffer::query()
            ->with([
                'report.muthowifBooking.customer',
                'report.muthowifBooking.muthowifProfile.user',
            ])
            ->where('muthowif_profile_id', $profile->getKey())
            ->whereIn('status', ['offered', 'accepted'])
            ->orderByDesc('offered_at')
            ->paginate(20);

        return response()->json([
            'data' => $offers->getCollection()->map(fn (BookingReplacementOffer $o) => $this->formatOffer($o))->values(),
            'meta' => [
                'current_page' => $offers->currentPage(),
                'last_page' => $offers->lastPage(),
                'total' => $offers->total(),
            ],
        ]);
    }

    public function pendingCount(Request $request): JsonResponse
    {
        abort_unless($request->user()?->muthowifProfile, 403);

        return response()->json([
            'pending_count' => MuthowifEmergencyOfferCounts::pendingOfferedCountForUser($request->user()),
        ]);
    }

    public function accept(Request $request, BookingReplacementOffer $offer): JsonResponse
    {
        $this->authorize('respondToOffer', $offer);

        try {
            app(EmergencyReplacementService::class)->muthowifAccept($offer, $request->user());
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => __('emergency.flash.offer_accepted')]);
    }

    public function decline(Request $request, BookingReplacementOffer $offer): JsonResponse
    {
        $this->authorize('respondToOffer', $offer);

        $validated = $request->validate([
            'decline_note' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            app(EmergencyReplacementService::class)->muthowifDecline(
                $offer,
                $request->user(),
                $validated['decline_note'] ?? null,
            );
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => __('emergency.flash.offer_declined')]);
    }

    /**
     * @return array<string, mixed>
     */
    private function formatOffer(BookingReplacementOffer $offer): array
    {
        $booking = $offer->report?->muthowifBooking;

        return [
            'id' => (string) $offer->getKey(),
            'status' => $offer->status,
            'offered_at' => $offer->offered_at?->toIso8601String(),
            'booking_id' => $booking ? (string) $booking->getKey() : null,
            'booking_code' => $booking?->booking_code,
            'customer_name' => $booking?->customer?->name,
            'starts_on' => $booking?->starts_on?->toDateString(),
            'ends_on' => $booking?->ends_on?->toDateString(),
            'original_muthowif' => $booking?->muthowifProfile?->user?->name,
        ];
    }
}
