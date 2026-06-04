<?php

namespace App\Http\Controllers\Customer;

use App\Enums\EmergencyReportCaseType;
use App\Enums\ReplacementOfferStatus;
use App\Http\Controllers\Controller;
use App\Models\BookingReplacementOffer;
use App\Models\MuthowifBooking;
use App\Services\Emergency\EmergencyReplacementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BookingEmergencyController extends Controller
{
    public function store(Request $request, MuthowifBooking $booking): RedirectResponse
    {
        $this->authorize('reportEmergency', $booking);

        $validated = $request->validate([
            'case_type' => ['required', Rule::enum(EmergencyReportCaseType::class)],
            'description' => ['nullable', 'string', 'max:5000'],
            'evidence' => ['nullable', 'array', 'max:5'],
            'evidence.*' => ['file', 'max:10240', 'mimes:jpg,jpeg,png,pdf,webp'],
        ]);

        $caseType = $validated['case_type'] instanceof EmergencyReportCaseType
            ? $validated['case_type']
            : EmergencyReportCaseType::from((string) $validated['case_type']);

        $files = $request->file('evidence', []);
        if (! is_array($files)) {
            $files = [];
        }

        try {
            app(EmergencyReplacementService::class)->submitReport(
                $booking,
                $request->user(),
                $caseType,
                $validated['description'] ?? null,
                $files,
            );
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('bookings.show', $booking)
            ->with('status', __('emergency.flash.report_submitted'));
    }

    public function selectReplacement(
        Request $request,
        MuthowifBooking $booking,
        BookingReplacementOffer $offer,
    ): RedirectResponse {
        $this->authorize('selectReplacement', [$booking, $offer]);

        try {
            app(EmergencyReplacementService::class)->customerSelect($offer, $request->user());
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('bookings.show', $booking)
            ->with('status', __('emergency.flash.replacement_selected'));
    }
}
