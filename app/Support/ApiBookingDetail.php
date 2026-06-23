<?php

namespace App\Support;

use App\Models\BookingRefundRequest;
use App\Models\BookingRescheduleRequest;
use App\Models\MuthowifBooking;

final class ApiBookingDetail
{
    public static function format(MuthowifBooking $booking, bool $forMuthowif = false): array
    {
        $booking->loadMissing([
            'muthowifProfile.user',
            'customer',
            'review',
            'refundRequests' => fn ($q) => $q->orderByDesc('created_at'),
            'rescheduleRequests' => fn ($q) => $q->orderByDesc('created_at'),
        ]);

        $profile = $booking->muthowifProfile;

        $data = [
            'id' => $booking->id,
            'booking_code' => $booking->booking_code,
            'status' => $booking->status->value,
            'payment_status' => $booking->payment_status->value,
            'service_type' => $booking->service_type?->value,
            'pilgrim_count' => $booking->pilgrim_count,
            'starts_on' => $booking->starts_on->toDateString(),
            'ends_on' => $booking->ends_on->toDateString(),
            'with_same_hotel' => (bool) $booking->with_same_hotel,
            'with_transport' => (bool) $booking->with_transport,
            'total_amount' => (float) $booking->total_amount,
            'daily_price_snapshot' => (float) ($booking->daily_price_snapshot ?? 0),
            'same_hotel_price_snapshot' => (float) ($booking->same_hotel_price_snapshot ?? 0),
            'transport_price_snapshot' => (float) ($booking->transport_price_snapshot ?? 0),
            'add_ons_snapshot' => $booking->add_ons_snapshot ?? [],
            'paid_at' => $booking->paid_at?->toIso8601String(),
            'review' => $booking->review ? [
                'id' => $booking->review->id,
                'rating' => $booking->review->rating,
                'comment' => $booking->review->review,
            ] : null,
            'refund_requests' => $booking->refundRequests->map(fn (BookingRefundRequest $req) => [
                'id' => $req->id,
                'status' => $req->status->value,
                'reason' => $req->customer_note,
                'created_at' => $req->created_at?->toIso8601String(),
            ])->values()->all(),
            'reschedule_requests' => $booking->rescheduleRequests->map(fn (BookingRescheduleRequest $req) => [
                'id' => $req->id,
                'status' => $req->status->value,
                'starts_on' => $req->new_starts_on->toDateString(),
                'ends_on' => $req->new_ends_on->toDateString(),
                'reason' => $req->customer_note,
                'created_at' => $req->created_at?->toIso8601String(),
            ])->values()->all(),
        ];

        if ($forMuthowif) {
            $data['customer'] = $booking->customer ? [
                'id' => $booking->customer->id,
                'name' => $booking->customer->name,
                'email' => $booking->customer->email,
                'phone' => $booking->customer->phone,
            ] : null;
        } else {
            $data['muthowif_profile'] = $profile ? [
                'id' => $profile->id,
                'avatar' => $profile->photoUrl(),
                'user' => $profile->user ? [
                    'id' => $profile->user->id,
                    'name' => $profile->user->name,
                    'phone' => $profile->phone ?? $profile->user->phone,
                ] : null,
            ] : null;
        }

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    public static function invoice(MuthowifBooking $booking): array
    {
        $booking->loadMissing(['muthowifProfile.user', 'customer']);
        $payment = $booking->settledBookingPayment();
        $isCompany = $booking->customer?->isCompanyCustomer() ?? false;
        $split = PlatformFee::split((float) $booking->resolvedAmountDue(), $isCompany);

        return [
            'booking_code' => $booking->booking_code,
            'paid_at' => $booking->paid_at?->timezone(config('app.timezone'))?->toIso8601String(),
            'customer' => [
                'name' => $booking->customer?->name,
                'email' => $booking->customer?->email,
                'phone' => $booking->customer?->phone,
            ],
            'muthowif' => [
                'name' => $booking->muthowifProfile?->user?->name,
            ],
            'service_period' => [
                'starts_on' => $booking->starts_on->toDateString(),
                'ends_on' => $booking->ends_on->toDateString(),
            ],
            'pilgrim_count' => $booking->pilgrim_count,
            'service_type' => $booking->service_type?->value,
            'amounts' => [
                'base' => (float) ($split['base'] ?? 0),
                'platform_fee' => (float) ($split['customer_fee'] ?? 0),
                'total' => (float) ($split['customer_gross'] ?? 0),
            ],
            'payment' => $payment ? [
                'order_id' => $payment->order_id,
                'payment_type' => $payment->payment_type,
                'gross_amount' => (int) $payment->gross_amount,
                'settled_at' => $payment->settled_at?->toIso8601String(),
            ] : null,
        ];
    }
}
