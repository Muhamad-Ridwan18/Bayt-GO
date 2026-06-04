<?php

namespace App\Http\Controllers\Muthowif;

use App\Http\Controllers\Controller;
use App\Models\BookingReplacementOffer;
use App\Services\Emergency\EmergencyReplacementService;
use App\Support\MuthowifEmergencyOfferCounts;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmergencyOfferController extends Controller
{
    public function pendingOfferCount(Request $request): JsonResponse
    {
        abort_unless($request->user()?->muthowifProfile, 403);

        return response()->json([
            'pending_count' => MuthowifEmergencyOfferCounts::pendingOfferedCountForUser($request->user()),
        ]);
    }

    public function index(Request $request): View
    {
        $profile = $request->user()->muthowifProfile;
        abort_unless($profile, 403);

        $offers = BookingReplacementOffer::query()
            ->with([
                'report.muthowifBooking.customer',
                'report.muthowifBooking.muthowifProfile.user',
                'muthowifProfile.user',
            ])
            ->where('muthowif_profile_id', $profile->getKey())
            ->whereIn('status', ['offered', 'accepted'])
            ->orderByDesc('offered_at')
            ->paginate(20);

        return view('muthowif.emergency-offers.index', [
            'offers' => $offers,
        ]);
    }

    public function accept(Request $request, BookingReplacementOffer $offer): RedirectResponse
    {
        $this->authorize('respondToOffer', $offer);

        try {
            app(EmergencyReplacementService::class)->muthowifAccept($offer, $request->user());
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', __('emergency.flash.offer_accepted'));
    }

    public function decline(Request $request, BookingReplacementOffer $offer): RedirectResponse
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
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', __('emergency.flash.offer_declined'));
    }

    public function indexLiveFragment(Request $request): View
    {
        $profile = $request->user()->muthowifProfile;
        abort_unless($profile, 403);

        $offers = BookingReplacementOffer::query()
            ->with([
                'report.muthowifBooking.customer',
                'report.muthowifBooking.muthowifProfile.user',
                'muthowifProfile.user',
            ])
            ->where('muthowif_profile_id', $profile->getKey())
            ->whereIn('status', ['offered', 'accepted'])
            ->orderByDesc('offered_at')
            ->paginate(20);

        return view('muthowif.emergency-offers.partials.index-live', [
            'offers' => $offers,
        ]);
    }
}
