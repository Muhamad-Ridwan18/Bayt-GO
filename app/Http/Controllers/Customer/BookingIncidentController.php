<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\BookingReplacement;
use App\Models\MuthowifBooking;
use App\Services\Incident\BookingIncidentService;
use App\Services\Incident\BookingReplacementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BookingIncidentController extends Controller
{
    public function emergency(Request $request, MuthowifBooking $booking): RedirectResponse
    {
        $this->authorize('reportEmergency', $booking);

        $validated = $request->validate([
            'statement' => ['nullable', 'string', 'max:5000'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
        ]);

        $geo = null;
        if (isset($validated['latitude'], $validated['longitude'])) {
            $geo = [
                'lat' => (float) $validated['latitude'],
                'lng' => (float) $validated['longitude'],
            ];
        }

        $booking->syncServicePhase();

        app(BookingIncidentService::class)->openFromEmergency(
            $booking,
            $request->user(),
            $validated['statement'] ?? null,
            $geo,
        );

        return redirect()
            ->route('bookings.show', $booking)
            ->with('status', __('incidents.flash.emergency_reported'));
    }

    public function selectReplacement(Request $request, MuthowifBooking $booking, BookingReplacement $replacement): RedirectResponse
    {
        $replacement->loadMissing('incident.muthowifBooking');
        $this->authorize('acceptReplacement', $replacement);
        abort_unless((string) $replacement->incident->muthowif_booking_id === (string) $booking->getKey(), 404);

        try {
            app(BookingReplacementService::class)->customerSelect($replacement, $request->user());
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('bookings.show', $booking)
            ->with('status', __('incidents.flash.replacement_selected'));
    }
}
