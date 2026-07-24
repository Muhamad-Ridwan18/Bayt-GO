<?php

namespace App\Http\Controllers\Api\Muthowif;

use App\Enums\BookingChangeRequestStatus;
use App\Enums\BookingStatus;
use App\Enums\MuthowifBookingMuthowifRejectionKind;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Jobs\NotifyCustomerOfApprovedBooking;
use App\Jobs\NotifyCustomerOfBookingRejectedJadwalFull;
use App\Jobs\NotifyCustomerOfRescheduleApproved;
use App\Jobs\NotifyCustomerOfRescheduleRejected;
use App\Models\BookingRescheduleRequest;
use App\Models\MuthowifBooking;
use App\Services\BookingPendingPaymentEnsurer;
use App\Support\ApiBookingDetail;
use App\Support\CustomerBookingBroadcast;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use RuntimeException;
use Throwable;

class MuthowifBookingController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $bookings = MuthowifBooking::where('muthowif_profile_id', $user->muthowifProfile->id)
            ->with(['customer', 'muthowifProfile.services'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($b) {
                $pricing = ApiBookingDetail::pricing($b, forMuthowif: true);

                return [
                    'id' => $b->id,
                    'booking_code' => $b->booking_code,
                    'customer_name' => $b->customer->name,
                    'customer_email' => $b->customer->email,
                    'status' => $b->status->value,
                    'status_label' => $b->status->label(),
                    'payment_status' => $b->payment_status->value,
                    'payment_label' => $b->payment_status->label(),
                    'starts_on' => Carbon::parse($b->starts_on)->format('d M Y'),
                    'ends_on' => Carbon::parse($b->ends_on)->format('d M Y'),
                    'service_type' => $b->service_type?->label() ?? '—',
                    'pilgrim_count' => $b->pilgrim_count,
                    'total_amount' => (float) $b->total_amount,
                    'total_price' => 'Rp '.number_format($b->total_amount, 0, ',', '.'),
                    'pricing' => $pricing,
                    'net_earning' => $pricing['net_after_referral'],
                ];
            });

        return response()->json([
            'bookings' => $bookings,
        ]);
    }

    public function show(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        $booking = MuthowifBooking::where('muthowif_profile_id', $user->muthowifProfile->id)
            ->findOrFail($id);

        return response()->json(ApiBookingDetail::format($booking, forMuthowif: true));
    }

    public function confirm(Request $request, $id)
    {
        $user = $request->user();
        $booking = MuthowifBooking::where('muthowif_profile_id', $user->muthowifProfile->id)
            ->findOrFail($id);

        if ($booking->status !== BookingStatus::Pending) {
            return response()->json(['message' => 'Hanya pesanan pending yang bisa disetujui.'], 422);
        }

        $booking->loadMissing(['muthowifProfile.services.addOns']);
        $profile = $booking->muthowifProfile;
        if ($profile === null) {
            return response()->json(['message' => 'Profil muthowif tidak ditemukan.'], 422);
        }

        $start = $booking->starts_on->copy()->startOfDay();
        $end = ($booking->ends_on ?? $booking->starts_on)->copy()->startOfDay();
        if (! $profile->isJadwalAvailableForRange($start, $end, (string) $booking->getKey())) {
            return response()->json([
                'message' => 'Jadwal tanggal bentrok dengan booking lain yang sudah disetujui.',
            ], 422);
        }

        $total = $booking->computeTotalAmount();
        $bookingIdStr = (string) $booking->getKey();
        $broadcastIds = [$bookingIdStr];
        $customerIdsToInvalidate = [(string) $booking->customer_id];
        $rejectedBookingIds = [];

        DB::transaction(function () use ($booking, $profile, $start, $end, $total, $bookingIdStr, &$broadcastIds, &$customerIdsToInvalidate, &$rejectedBookingIds): void {
            $confirmPayload = [
                'status' => BookingStatus::Confirmed,
                'payment_status' => PaymentStatus::Pending,
                'total_amount' => $total,
            ];

            if ($booking->isSupport()) {
                $confirmPayload['ends_on'] = $start->toDateString();
            }

            $booking->update($confirmPayload);

            $overlappingPending = MuthowifBooking::query()
                ->where('muthowif_profile_id', $profile->id)
                ->where('status', BookingStatus::Pending)
                ->whereKeyNot($bookingIdStr)
                ->where('starts_on', '<=', $end->toDateString())
                ->where('ends_on', '>=', $start->toDateString())
                ->get();

            foreach ($overlappingPending as $other) {
                $other->update([
                    'status' => BookingStatus::Cancelled,
                    'muthowif_rejection_kind' => MuthowifBookingMuthowifRejectionKind::JadwalFull,
                    'muthowif_rejection_note' => __('bookings.show.muthowif_rejection_auto_collision'),
                ]);
                $rejectedBookingIds[] = (string) $other->getKey();
                $broadcastIds[] = (string) $other->getKey();
                $customerIdsToInvalidate[] = (string) $other->customer_id;
            }
        });

        app(BookingPendingPaymentEnsurer::class)->ensure($booking->fresh());

        NotifyCustomerOfApprovedBooking::dispatchAfterResponse((string) $booking->getKey());
        foreach ($rejectedBookingIds as $otherId) {
            NotifyCustomerOfBookingRejectedJadwalFull::dispatchAfterResponse($otherId);
        }
        CustomerBookingBroadcast::afterResponseMany($broadcastIds);
        foreach (array_unique($customerIdsToInvalidate) as $customerId) {
            Cache::forget('customer_booking_status_counts:'.$customerId);
        }

        return response()->json([
            'message' => 'Pesanan berhasil disetujui',
            'status' => 'confirmed',
        ]);
    }

    public function cancel(Request $request, $id)
    {
        $user = $request->user();
        $booking = MuthowifBooking::where('muthowif_profile_id', $user->muthowifProfile->id)
            ->findOrFail($id);

        if (! in_array($booking->status, [BookingStatus::Pending, BookingStatus::Confirmed])) {
            return response()->json(['message' => 'Pesanan ini tidak dapat dibatalkan.'], 422);
        }

        $rules = [
            'muthowif_rejection_note' => ['nullable', 'string', 'max:2000'],
        ];
        if ($booking->status === BookingStatus::Pending) {
            $rules['muthowif_rejection_kind'] = ['required', Rule::enum(MuthowifBookingMuthowifRejectionKind::class)];
        }

        $validated = $request->validate($rules);

        $kind = null;
        $note = null;
        if ($booking->status === BookingStatus::Pending) {
            $rawKind = $validated['muthowif_rejection_kind'];
            $kind = $rawKind instanceof MuthowifBookingMuthowifRejectionKind
                ? $rawKind
                : MuthowifBookingMuthowifRejectionKind::from((string) $rawKind);
            $note = filled($validated['muthowif_rejection_note'] ?? null)
                ? trim((string) $validated['muthowif_rejection_note'])
                : null;
        }

        $booking->update([
            'status' => BookingStatus::Cancelled,
            'muthowif_rejection_kind' => $kind,
            'muthowif_rejection_note' => $note,
        ]);

        app(\App\Services\AffiliateCommissionService::class)->voidForBooking($booking, 'cancelled_by_muthowif');

        NotifyCustomerOfBookingRejectedJadwalFull::dispatchAfterResponse((string) $booking->getKey());

        CustomerBookingBroadcast::afterResponse($booking);

        return response()->json([
            'message' => 'Pesanan berhasil dibatalkan',
            'status' => 'cancelled',
        ]);
    }

    public function approveReschedule(Request $request, $booking, $rescheduleRequest): JsonResponse
    {
        $user = $request->user();
        $bookingModel = MuthowifBooking::where('muthowif_profile_id', $user->muthowifProfile->id)
            ->findOrFail($booking);

        $this->authorize('decidePostPayChange', $bookingModel);

        $rescheduleRequestModel = BookingRescheduleRequest::query()->findOrFail($rescheduleRequest);
        abort_unless((string) $rescheduleRequestModel->muthowif_booking_id === (string) $bookingModel->getKey(), 404);

        $validated = $request->validate([
            'muthowif_note' => ['nullable', 'string', 'max:2000'],
        ]);

        if (! $rescheduleRequestModel->isPending()) {
            return response()->json(['message' => 'Pengajuan reschedule ini sudah diproses.'], 422);
        }

        $profile = $bookingModel->muthowifProfile;
        if ($profile === null) {
            return response()->json(['message' => 'Profil muthowif tidak ditemukan.'], 422);
        }

        $start = $rescheduleRequestModel->new_starts_on->copy()->startOfDay();
        $end = $rescheduleRequestModel->new_ends_on->copy()->startOfDay();

        if (! $profile->isJadwalAvailableForRange($start, $end, (string) $bookingModel->getKey())) {
            return response()->json(['message' => 'Jadwal tanggal yang diajukan tidak lagi tersedia.'], 422);
        }

        $oldNights = $bookingModel->billingNightsInclusive();
        $newNights = MuthowifBooking::inclusiveSpanDays($start, $end);
        if ($oldNights !== $newNights) {
            return response()->json(['message' => 'Jumlah hari pada pengajuan tidak sama dengan booking.'], 422);
        }

        $broadcastIds = [(string) $bookingModel->getKey()];
        $rejectedBookingIds = [];

        try {
            DB::transaction(function () use ($bookingModel, $profile, $rescheduleRequestModel, $request, $validated, $start, $end, &$broadcastIds, &$rejectedBookingIds): void {
                $rescheduleRequestModel->refresh()->lockForUpdate();
                $bookingModel->refresh()->lockForUpdate();

                if (! $rescheduleRequestModel->isPending()) {
                    throw new RuntimeException('Pengajuan reschedule sudah diproses.');
                }

                $rescheduleRequestModel->update([
                    'status' => BookingChangeRequestStatus::Approved,
                    'decided_at' => now(),
                    'decided_by' => $request->user()->id,
                    'muthowif_note' => filled($validated['muthowif_note'] ?? null) ? trim((string) $validated['muthowif_note']) : null,
                ]);

                $bookingModel->update([
                    'starts_on' => $start->toDateString(),
                    'ends_on' => $end->toDateString(),
                ]);

                $overlappingPending = MuthowifBooking::query()
                    ->where('muthowif_profile_id', $profile->id)
                    ->where('status', BookingStatus::Pending)
                    ->whereKeyNot($bookingModel->getKey())
                    ->where('starts_on', '<=', $end->toDateString())
                    ->where('ends_on', '>=', $start->toDateString())
                    ->get();

                foreach ($overlappingPending as $other) {
                    $other->update([
                        'status' => BookingStatus::Cancelled,
                        'muthowif_rejection_kind' => MuthowifBookingMuthowifRejectionKind::JadwalFull,
                        'muthowif_rejection_note' => __('bookings.show.muthowif_rejection_auto_collision'),
                    ]);
                    $rejectedBookingIds[] = (string) $other->getKey();
                    $broadcastIds[] = (string) $other->getKey();
                }
            });
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $bookingModel = $bookingModel->fresh();
        $rescheduleRequestModel = $rescheduleRequestModel->fresh();
        NotifyCustomerOfRescheduleApproved::dispatchAfterResponse(
            (string) $bookingModel->getKey(),
            (string) $rescheduleRequestModel->getKey(),
        );
        foreach ($rejectedBookingIds as $otherId) {
            NotifyCustomerOfBookingRejectedJadwalFull::dispatchAfterResponse($otherId);
        }
        CustomerBookingBroadcast::afterResponseMany($broadcastIds);

        return response()->json(['message' => 'Reschedule disetujui. Tanggal booking telah diperbarui.']);
    }

    public function rejectReschedule(Request $request, $booking, $rescheduleRequest): JsonResponse
    {
        $user = $request->user();
        $bookingModel = MuthowifBooking::where('muthowif_profile_id', $user->muthowifProfile->id)
            ->findOrFail($booking);

        $this->authorize('decidePostPayChange', $bookingModel);

        $rescheduleRequestModel = BookingRescheduleRequest::query()->findOrFail($rescheduleRequest);
        abort_unless((string) $rescheduleRequestModel->muthowif_booking_id === (string) $bookingModel->getKey(), 404);

        if ($request->filled('reason') && ! $request->filled('muthowif_note')) {
            $request->merge(['muthowif_note' => $request->input('reason')]);
        }

        $validated = $request->validate([
            'muthowif_note' => ['nullable', 'string', 'max:2000'],
        ]);

        if (! $rescheduleRequestModel->isPending()) {
            return response()->json(['message' => 'Pengajuan reschedule ini sudah diproses.'], 422);
        }

        $rescheduleRequestModel->update([
            'status' => BookingChangeRequestStatus::Rejected,
            'decided_at' => now(),
            'decided_by' => $request->user()->id,
            'muthowif_note' => filled($validated['muthowif_note'] ?? null) ? trim((string) $validated['muthowif_note']) : null,
        ]);

        NotifyCustomerOfRescheduleRejected::dispatchAfterResponse(
            (string) $bookingModel->getKey(),
            (string) $rescheduleRequestModel->getKey(),
        );
        CustomerBookingBroadcast::afterResponse($bookingModel->fresh());

        return response()->json(['message' => 'Pengajuan reschedule ditolak.']);
    }

    public function completeSupportWithCode(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        $booking = MuthowifBooking::where('muthowif_profile_id', $user->muthowifProfile->id)->findOrFail($id);
        $this->authorize('completeSupportWithCode', $booking);

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:12'],
        ]);

        $result = app(\App\Services\SupportBookingService::class)->completeWithCode(
            $booking,
            $validated['code'],
            (string) $user->id,
        );
        if (! ($result['completed'] ?? false)) {
            return response()->json(['message' => $result['error'] ?? __('layanan_pendukung.flash.completion_approve_failed')], 422);
        }

        CustomerBookingBroadcast::afterResponse($booking->fresh());

        return response()->json(['message' => __('layanan_pendukung.flash.completion_approved')]);
    }

    public function resendSupportCompletionCode(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        $booking = MuthowifBooking::where('muthowif_profile_id', $user->muthowifProfile->id)->findOrFail($id);
        $this->authorize('resendSupportCompletionCode', $booking);

        app(\App\Services\SupportBookingService::class)->issueCompletionCode($booking, true);

        return response()->json(['message' => __('layanan_pendukung.flash.completion_code_sent')]);
    }
}
