<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BookingEmergencyReport;
use App\Models\MuthowifProfile;
use App\Services\Emergency\EmergencyReplacementCandidateService;
use App\Services\Emergency\EmergencyReplacementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BookingEmergencyController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', BookingEmergencyReport::class);

        $status = $request->query('status');

        $reports = BookingEmergencyReport::query()
            ->with(['muthowifBooking.customer', 'muthowifBooking.muthowifProfile.user', 'reportedBy'])
            ->when(filled($status), fn ($q) => $q->where('status', $status))
            ->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();

        return view('admin.emergency.index', [
            'reports' => $reports,
            'statusFilter' => $status,
        ]);
    }

    public function show(BookingEmergencyReport $report): View
    {
        $this->authorize('view', $report);

        $report->load([
            'muthowifBooking.customer',
            'muthowifBooking.muthowifProfile.user',
            'muthowifBooking.originalMuthowifProfile.user',
            'reportedBy',
            'verifiedByAdmin',
            'offers.muthowifProfile.user',
        ]);

        $booking = $report->muthowifBooking;
        $manualCandidates = app(EmergencyReplacementCandidateService::class)
            ->listEligible(
                $booking,
                excludeProfileIds: $report->offers->pluck('muthowif_profile_id')->all(),
            )
            ->take(30);

        return view('admin.emergency.show', [
            'report' => $report,
            'manualCandidates' => $manualCandidates,
        ]);
    }

    public function markUnderReview(Request $request, BookingEmergencyReport $report): RedirectResponse
    {
        $this->authorize('update', $report);

        try {
            app(EmergencyReplacementService::class)->markUnderReview($report, $request->user());
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', __('emergency.flash.under_review'));
    }

    public function verify(Request $request, BookingEmergencyReport $report): RedirectResponse
    {
        $this->authorize('update', $report);

        $validated = $request->validate([
            'admin_note' => ['nullable', 'string', 'max:5000'],
        ]);

        try {
            app(EmergencyReplacementService::class)->verifyAndStartRecruitment(
                $report,
                $request->user(),
                $validated['admin_note'] ?? null,
            );
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', __('emergency.flash.verified'));
    }

    public function reject(Request $request, BookingEmergencyReport $report): RedirectResponse
    {
        $this->authorize('update', $report);

        $validated = $request->validate([
            'admin_note' => ['nullable', 'string', 'max:5000'],
        ]);

        try {
            app(EmergencyReplacementService::class)->rejectReport(
                $report,
                $request->user(),
                $validated['admin_note'] ?? null,
            );
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', __('emergency.flash.rejected'));
    }

    public function broadcastBatch(Request $request, BookingEmergencyReport $report): RedirectResponse
    {
        $this->authorize('update', $report);

        try {
            $count = app(EmergencyReplacementService::class)->broadcastNextBatch($report);
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', __('emergency.flash.batch_sent', ['count' => $count]));
    }

    public function invite(Request $request, BookingEmergencyReport $report): RedirectResponse
    {
        $this->authorize('update', $report);

        $validated = $request->validate([
            'muthowif_profile_id' => ['required', 'uuid', 'exists:muthowif_profiles,id'],
        ]);

        $target = MuthowifProfile::query()->findOrFail($validated['muthowif_profile_id']);

        try {
            app(EmergencyReplacementService::class)->adminInvite($report, $target, $request->user());
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', __('emergency.flash.invited'));
    }

    public function indexLiveFragment(Request $request): View
    {
        $this->authorize('viewAny', BookingEmergencyReport::class);

        $status = $request->query('status');

        $reports = BookingEmergencyReport::query()
            ->with(['muthowifBooking.customer', 'muthowifBooking.muthowifProfile.user', 'reportedBy'])
            ->when(filled($status), fn ($q) => $q->where('status', $status))
            ->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();

        return view('admin.emergency.partials.index-live', [
            'reports' => $reports,
        ]);
    }

    public function showLiveFragment(BookingEmergencyReport $report): View
    {
        $this->authorize('view', $report);

        $report->load([
            'muthowifBooking.customer',
            'muthowifBooking.muthowifProfile.user',
            'offers.muthowifProfile.user',
        ]);

        $booking = $report->muthowifBooking;
        $manualCandidates = app(EmergencyReplacementCandidateService::class)
            ->listEligible(
                $booking,
                excludeProfileIds: $report->offers->pluck('muthowif_profile_id')->all(),
            )
            ->take(30);

        return view('admin.emergency.partials.show-live', [
            'report' => $report,
            'manualCandidates' => $manualCandidates,
        ]);
    }
}
