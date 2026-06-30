<?php

namespace App\Http\Controllers\Api\Customer;

use App\Enums\EmergencyReportCaseType;
use App\Http\Controllers\Controller;
use App\Models\BookingReplacementOffer;
use App\Models\MuthowifBooking;
use App\Services\Emergency\EmergencyReplacementService;
use App\Support\ApiBookingDetail;
use App\Support\ApiEmergencyDetail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BookingEmergencyApiController extends Controller
{
    public function store(Request $request, MuthowifBooking $booking): JsonResponse
    {
        if ($booking->customer_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

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
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $booking = $booking->fresh();

        return response()->json([
            'message' => 'Laporan insiden darurat berhasil dikirim.',
            'booking' => ApiBookingDetail::format($booking),
            'emergency' => ApiEmergencyDetail::for($booking),
        ], 201);
    }

    public function selectReplacement(
        Request $request,
        MuthowifBooking $booking,
        BookingReplacementOffer $offer,
    ): JsonResponse {
        if ($booking->customer_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $this->authorize('selectReplacement', [$booking, $offer]);

        try {
            app(EmergencyReplacementService::class)->customerSelect($offer, $request->user());
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $booking = $booking->fresh();

        return response()->json([
            'message' => 'Muthowif pengganti berhasil dipilih.',
            'booking' => ApiBookingDetail::format($booking),
            'emergency' => ApiEmergencyDetail::for($booking),
        ]);
    }
}
