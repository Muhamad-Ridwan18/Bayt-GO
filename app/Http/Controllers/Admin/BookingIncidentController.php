<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BookingIncidentResolution;
use App\Enums\BookingIncidentStatus;
use App\Enums\BookingReplacementStatus;
use App\Http\Controllers\Controller;
use App\Models\BookingIncident;
use App\Models\BookingReplacement;
use App\Models\BookingSettlement;
use App\Models\MuthowifProfile;
use App\Services\Incident\BookingIncidentService;
use App\Services\Incident\BookingReplacementCandidateService;
use App\Services\Incident\BookingReplacementService;
use App\Services\Incident\IncidentCompensationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BookingIncidentController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('manage', BookingIncident::class);

        $status = $request->query('status', 'open');

        $query = BookingIncident::query()
            ->with(['muthowifBooking.customer', 'muthowifBooking.muthowifProfile.user', 'assignedAdmin'])
            ->orderByDesc('opened_at');

        if ($status === 'open') {
            $query->whereNotIn('status', [
                BookingIncidentStatus::Resolved->value,
                BookingIncidentStatus::Cancelled->value,
            ]);
        } elseif ($status !== 'all') {
            $query->where('status', $status);
        }

        $incidents = $query->paginate(25)->withQueryString();

        return view('admin.incidents.index', [
            'incidents' => $incidents,
            'status' => $status,
        ]);
    }

    public function show(BookingIncident $incident): View
    {
        $this->authorize('view', $incident);

        $incident->load([
            'muthowifBooking.customer',
            'muthowifBooking.muthowifProfile.user',
            'events',
            'replacements.replacementProfile.user',
            'replacements.originalProfile.user',
            'assignedAdmin',
        ]);

        $booking = $incident->muthowifBooking;
        $candidates = app(BookingReplacementCandidateService::class)
            ->listCandidates($booking, $booking->muthowifProfile);

        $draftSettlement = BookingSettlement::query()
            ->where('booking_incident_id', $incident->getKey())
            ->with('payoutAllocations.muthowifProfile.user')
            ->latest()
            ->first();

        $pendingApprovals = $incident->replacements
            ->where('status', BookingReplacementStatus::PendingAdminApproval);
        $awaitingConfirm = $incident->replacements
            ->where('status', BookingReplacementStatus::AwaitingMuthowifConfirm);
        $approvedPool = $incident->replacements->filter(
            fn ($r) => in_array($r->status, BookingReplacementStatus::customerSelectable(), true)
        );

        return view('admin.incidents.show', [
            'incident' => $incident,
            'booking' => $booking,
            'candidates' => $candidates,
            'draftSettlement' => $draftSettlement,
            'pendingApprovals' => $pendingApprovals,
            'awaitingConfirm' => $awaitingConfirm,
            'approvedPool' => $approvedPool,
        ]);
    }

    public function assignSelf(Request $request, BookingIncident $incident): RedirectResponse
    {
        $this->authorize('manage', BookingIncident::class);

        app(BookingIncidentService::class)->assignAdmin($incident, $request->user());

        return back()->with('status', __('incidents.flash.assigned'));
    }

    public function investigate(Request $request, BookingIncident $incident): RedirectResponse
    {
        $this->authorize('manage', BookingIncident::class);

        app(BookingIncidentService::class)->moveToInvestigating($incident, $request->user());

        return back()->with('status', __('incidents.flash.investigating'));
    }

    public function openRecruitment(Request $request, BookingIncident $incident): RedirectResponse
    {
        $this->authorize('manage', BookingIncident::class);

        try {
            app(BookingReplacementService::class)->openRecruitment($incident, $request->user());
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', __('incidents.flash.recruitment_opened'));
    }

    public function inviteReplacement(Request $request, BookingIncident $incident): RedirectResponse
    {
        $this->authorize('manage', BookingIncident::class);

        $validated = $request->validate([
            'replacement_muthowif_profile_id' => ['required', 'uuid', 'exists:muthowif_profiles,id'],
            'admin_note' => ['nullable', 'string', 'max:2000'],
        ]);

        $target = MuthowifProfile::query()->findOrFail($validated['replacement_muthowif_profile_id']);

        try {
            app(BookingReplacementService::class)->adminInvite(
                $incident,
                $target,
                $request->user(),
                $validated['admin_note'] ?? null,
            );
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', __('incidents.flash.replacement_invited'));
    }

    public function approveReplacement(Request $request, BookingIncident $incident, BookingReplacement $replacement): RedirectResponse
    {
        $this->authorize('manage', BookingIncident::class);
        abort_unless((string) $replacement->booking_incident_id === (string) $incident->getKey(), 404);

        try {
            app(BookingReplacementService::class)->adminApproveCandidate($replacement, $request->user());
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', __('incidents.flash.candidate_approved'));
    }

    public function rejectReplacement(Request $request, BookingIncident $incident, BookingReplacement $replacement): RedirectResponse
    {
        $this->authorize('manage', BookingIncident::class);
        abort_unless((string) $replacement->booking_incident_id === (string) $incident->getKey(), 404);

        $validated = $request->validate(['note' => ['nullable', 'string', 'max:2000']]);

        try {
            app(BookingReplacementService::class)->adminRejectCandidate(
                $replacement,
                $request->user(),
                $validated['note'] ?? null,
            );
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', __('incidents.flash.candidate_rejected'));
    }

    public function openCustomerChoice(Request $request, BookingIncident $incident): RedirectResponse
    {
        $this->authorize('manage', BookingIncident::class);

        try {
            app(BookingReplacementService::class)->openCustomerChoice($incident, $request->user());
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', __('incidents.flash.customer_choice_opened'));
    }

    public function proposeSettlement(Request $request, BookingIncident $incident): RedirectResponse
    {
        $this->authorize('manage', BookingIncident::class);

        $validated = $request->validate([
            'days_primary' => ['nullable', 'integer', 'min:0', 'max:365'],
            'days_replacement' => ['nullable', 'integer', 'min:0', 'max:365'],
        ]);

        try {
            app(IncidentCompensationService::class)->proposeSettlement(
                $incident,
                $validated['days_primary'] ?? null,
                $validated['days_replacement'] ?? null,
            );
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', __('incidents.flash.settlement_proposed'));
    }

    public function releaseSettlement(Request $request, BookingIncident $incident, BookingSettlement $settlement): RedirectResponse
    {
        $this->authorize('manage', BookingIncident::class);
        abort_unless((string) $settlement->booking_incident_id === (string) $incident->getKey(), 404);

        app(IncidentCompensationService::class)->approveAndReleaseSettlement($settlement, $request->user());

        return back()->with('status', __('incidents.flash.settlement_released'));
    }

    public function resolve(Request $request, BookingIncident $incident): RedirectResponse
    {
        $this->authorize('manage', BookingIncident::class);

        $validated = $request->validate([
            'resolution_type' => ['required', 'string'],
            'admin_resolution_note' => ['nullable', 'string', 'max:5000'],
        ]);

        app(BookingIncidentService::class)->resolve(
            $incident,
            $request->user(),
            BookingIncidentResolution::from($validated['resolution_type']),
            $validated['admin_resolution_note'] ?? null,
        );

        return redirect()
            ->route('admin.incidents.index')
            ->with('status', __('incidents.flash.resolved'));
    }

    public function falseAlarm(Request $request, BookingIncident $incident): RedirectResponse
    {
        $this->authorize('manage', BookingIncident::class);

        $validated = $request->validate([
            'admin_resolution_note' => ['nullable', 'string', 'max:5000'],
        ]);

        app(BookingIncidentService::class)->cancelFalseAlarm(
            $incident,
            $request->user(),
            $validated['admin_resolution_note'] ?? null,
        );

        return redirect()
            ->route('admin.incidents.index')
            ->with('status', __('incidents.flash.false_alarm'));
    }
}
