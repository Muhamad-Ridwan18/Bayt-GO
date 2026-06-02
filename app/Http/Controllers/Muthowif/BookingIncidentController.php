<?php

namespace App\Http\Controllers\Muthowif;

use App\Enums\BookingIncidentCaseType;
use App\Enums\BookingIncidentStatus;
use App\Http\Controllers\Controller;
use App\Models\BookingIncident;
use App\Models\BookingReplacement;
use App\Models\MuthowifBooking;
use App\Services\Incident\BookingReplacementCandidateService;
use App\Services\Incident\BookingIncidentService;
use App\Services\Incident\BookingReplacementService;
use App\Enums\BookingReplacementStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BookingIncidentController extends Controller
{
    public function pendingReplacementConfirmCount(Request $request): JsonResponse
    {
        $profile = $request->user()->muthowifProfile;
        abort_unless($profile, 403);

        $n = BookingReplacement::query()
            ->where('replacement_muthowif_profile_id', $profile->getKey())
            ->where('status', BookingReplacementStatus::AwaitingMuthowifConfirm)
            ->whereColumn('replacement_muthowif_profile_id', '!=', 'original_muthowif_profile_id')
            ->count();

        return response()->json(['pending_count' => $n]);
    }

    public function pendingReplacements(Request $request): View
    {
        $profile = $request->user()->muthowifProfile;
        abort_unless($profile, 403);

        $replacements = BookingReplacement::query()
            ->with([
                'incident.muthowifBooking.customer',
                'incident.muthowifBooking.muthowifProfile.user',
                'incident.muthowifBooking.muthowifProfile.services.addOns',
                'originalProfile.user',
                'replacementProfile.user',
            ])
            ->where('replacement_muthowif_profile_id', $profile->getKey())
            ->where('status', BookingReplacementStatus::AwaitingMuthowifConfirm)
            ->whereColumn('replacement_muthowif_profile_id', '!=', 'original_muthowif_profile_id')
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('muthowif.replacements.pending', [
            'replacements' => $replacements,
        ]);
    }

    public function recruitmentOpportunities(Request $request): View
    {
        $profile = $request->user()->muthowifProfile;
        abort_unless($profile, 403);

        $candidateService = app(BookingReplacementCandidateService::class);

        $incidents = BookingIncident::query()
            ->with(['muthowifBooking.customer', 'muthowifBooking.muthowifProfile.user', 'muthowifBooking.muthowifProfile.services.addOns'])
            ->where('replacement_recruitment_open', true)
            ->whereNotIn('status', [
                BookingIncidentStatus::Resolved->value,
                BookingIncidentStatus::Cancelled->value,
            ])
            ->orderByDesc('replacement_recruitment_opened_at')
            ->limit(50)
            ->get()
            ->filter(function (BookingIncident $incident) use ($profile, $candidateService) {
                $booking = $incident->muthowifBooking;
                if ($booking === null) {
                    return false;
                }

                if ((string) $booking->muthowif_profile_id === (string) $profile->getKey()) {
                    return false;
                }

                try {
                    $candidateService->assertCanReplace($booking, $profile);

                    return true;
                } catch (\RuntimeException) {
                    return false;
                }
            })
            ->filter(function (BookingIncident $incident) use ($profile) {
                return ! BookingReplacement::query()
                    ->where('booking_incident_id', $incident->getKey())
                    ->where('replacement_muthowif_profile_id', $profile->getKey())
                    ->whereNotIn('status', [
                        BookingReplacementStatus::RejectedByAdmin->value,
                        BookingReplacementStatus::DeclinedByMuthowif->value,
                        BookingReplacementStatus::Cancelled->value,
                        BookingReplacementStatus::NotSelected->value,
                    ])
                    ->exists();
            })
            ->values();

        $bookings = $incidents->map(fn ($i) => $i->muthowifBooking)->filter();
        $addonIds = $bookings->flatMap(fn ($b) => $b->selected_add_on_ids ?? [])->unique()->filter()->values();
        $addonsById = $addonIds->isEmpty()
            ? collect()
            : \App\Models\MuthowifServiceAddOn::query()->whereIn('id', $addonIds)->get()->keyBy('id');

        return view('muthowif.replacements.opportunities', [
            'incidents' => $incidents,
            'profile' => $profile,
            'addonsById' => $addonsById,
        ]);
    }

    public function declineOpportunity(Request $request, BookingIncident $incident): RedirectResponse
    {
        $profile = $request->user()->muthowifProfile;
        abort_unless($profile, 403);

        $validated = $request->validate(['note' => ['nullable', 'string', 'max:2000']]);

        try {
            app(BookingReplacementService::class)->muthowifDeclineOpportunity(
                $incident,
                $profile,
                $validated['note'] ?? null,
            );
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', __('incidents.flash.opportunity_declined'));
    }

    public function volunteer(Request $request, BookingIncident $incident): RedirectResponse
    {
        $profile = $request->user()->muthowifProfile;
        abort_unless($profile, 403);

        $validated = $request->validate([
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            app(BookingReplacementService::class)->muthowifVolunteer(
                $incident,
                $profile,
                $validated['note'] ?? null,
            );
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('muthowif.replacements.opportunities')
            ->with('status', __('incidents.flash.volunteered'));
    }

    public function report(Request $request, MuthowifBooking $booking): RedirectResponse
    {
        $this->authorize('reportAsMuthowif', $booking);

        $validated = $request->validate([
            'case_type' => [
                'required',
                Rule::in([
                    BookingIncidentCaseType::MuthowifUnavailable->value,
                    BookingIncidentCaseType::ForceMajeure->value,
                ]),
            ],
            'statement' => ['required', 'string', 'max:5000'],
            'evidence' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:8192'],
        ]);

        try {
            app(BookingIncidentService::class)->openFromMuthowifReport(
                $booking,
                $request->user(),
                BookingIncidentCaseType::from($validated['case_type']),
                $validated['statement'],
                $request->file('evidence'),
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        }

        return redirect()
            ->route('muthowif.bookings.show', $booking)
            ->with('status', __('incidents.flash.muthowif_reported'));
    }

    public function confirmReplacement(Request $request, MuthowifBooking $booking, BookingReplacement $replacement): RedirectResponse
    {
        $this->authorize('confirmReplacement', $replacement);
        abort_unless(
            (string) $replacement->incident?->muthowif_booking_id === (string) $booking->getKey(),
            404,
        );

        return $this->performConfirmReplacement($request, $replacement);
    }

    public function confirmReplacementById(Request $request, BookingReplacement $replacement): RedirectResponse
    {
        $this->authorize('confirmReplacement', $replacement);

        return $this->performConfirmReplacement($request, $replacement);
    }

    public function declineReplacement(Request $request, MuthowifBooking $booking, BookingReplacement $replacement): RedirectResponse
    {
        $this->authorize('confirmReplacement', $replacement);
        abort_unless(
            (string) $replacement->incident?->muthowif_booking_id === (string) $booking->getKey(),
            404,
        );

        return $this->performDeclineReplacement($request, $replacement);
    }

    public function declineReplacementById(Request $request, BookingReplacement $replacement): RedirectResponse
    {
        $this->authorize('confirmReplacement', $replacement);

        return $this->performDeclineReplacement($request, $replacement);
    }

    private function performConfirmReplacement(Request $request, BookingReplacement $replacement): RedirectResponse
    {
        try {
            app(BookingReplacementService::class)->replacementMuthowifConfirm($replacement, $request->user());
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        $booking = $replacement->incident?->muthowifBooking;
        $profile = $request->user()->muthowifProfile;
        $ownsBooking = $booking !== null
            && $profile !== null
            && (string) $booking->muthowif_profile_id === (string) $profile->getKey();

        if ($ownsBooking) {
            return redirect()
                ->route('muthowif.bookings.show', $booking)
                ->with('status', __('incidents.flash.replacement_confirmed'));
        }

        return redirect()
            ->route('muthowif.replacements.pending')
            ->with('status', __('incidents.flash.replacement_confirmed'));
    }

    private function performDeclineReplacement(Request $request, BookingReplacement $replacement): RedirectResponse
    {
        $validated = $request->validate([
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            app(BookingReplacementService::class)->replacementMuthowifDecline(
                $replacement,
                $request->user(),
                $validated['note'] ?? null,
            );
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('muthowif.replacements.pending')
            ->with('status', __('incidents.flash.replacement_declined'));
    }
}
