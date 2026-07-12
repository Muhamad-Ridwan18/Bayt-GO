<?php

namespace App\Support;

use App\Models\BookingRefundRequest;
use App\Models\BookingRescheduleRequest;
use App\Models\MuthowifBooking;

final class ApiBookingDetail
{
    /**
     * @return array<string, mixed>
     */
    public static function pricing(MuthowifBooking $booking, bool $forMuthowif = false): array
    {
        $booking->loadMissing(['customer']);

        $base = (float) $booking->resolvedAmountDue();
        $isCompany = $booking->customer?->isCompanyCustomer() ?? false;
        $split = PlatformFee::split($base, $isCompany);
        $lines = self::pricingLines($booking);
        $rate = PlatformFee::getRate();

        if ($forMuthowif) {
            $referral = MuthowifReferralReward::paymentSnapshot(
                (float) $split['muthowif_net'],
                (string) $booking->muthowif_profile_id,
            );
            $referralAmount = (float) $referral['referral_reward_amount'];
            $netAfterReferral = round(max(0.0, $split['muthowif_net'] - $referralAmount), 2);

            return [
                'base' => (float) $split['base'],
                'platform_fee' => (float) $split['muthowif_fee'],
                'platform_fee_rate' => $rate,
                'platform_fee_percent' => round($rate * 100, 2),
                'net_earning' => (float) $split['muthowif_net'],
                'referral_deduction' => $referralAmount,
                'net_after_referral' => $netAfterReferral,
                'lines' => $lines,
            ];
        }

        return [
            'base' => (float) $split['base'],
            'platform_fee' => (float) $split['customer_fee'],
            'platform_fee_rate' => $rate,
            'platform_fee_percent' => round($rate * 100, 2),
            'total_payable' => (float) $split['customer_gross'],
            'is_company_customer' => $isCompany,
            'lines' => $lines,
        ];
    }

    /**
     * @return list<array{key: string, label: string, amount: float}>
     */
    private static function pricingLines(MuthowifBooking $booking): array
    {
        if ($booking->isSupport()) {
            $package = (float) ($booking->package_price_snapshot ?? $booking->resolvedAmountDue());
            if ($package <= 0) {
                return [];
            }

            return [
                ['key' => 'package', 'label' => 'Paket layanan', 'amount' => $package],
            ];
        }

        $nights = $booking->billingNightsInclusive();
        $lines = [];

        $daily = (float) ($booking->daily_price_snapshot ?? 0);
        if ($daily > 0 && $nights > 0) {
            $lines[] = [
                'key' => 'daily',
                'label' => "Tarif harian × {$nights} hari",
                'amount' => round($nights * $daily, 2),
            ];
        }

        foreach ($booking->add_ons_snapshot ?? [] as $addon) {
            $addon = (array) $addon;
            $lines[] = [
                'key' => 'addon',
                'label' => (string) ($addon['name'] ?? 'Add-on'),
                'amount' => (float) ($addon['price'] ?? 0),
            ];
        }

        if ($booking->with_same_hotel) {
            $perDay = (float) ($booking->same_hotel_price_snapshot ?? 0);
            $amount = round($nights * $perDay, 2);
            if ($amount > 0) {
                $lines[] = ['key' => 'same_hotel', 'label' => 'Hotel sama', 'amount' => $amount];
            }
        }

        if ($booking->with_transport) {
            $amount = (float) ($booking->transport_price_snapshot ?? 0);
            if ($amount > 0) {
                $lines[] = ['key' => 'transport', 'label' => 'Transport', 'amount' => $amount];
            }
        }

        return $lines;
    }

    public static function formatList(MuthowifBooking $booking): array
    {
        $booking->loadMissing(['muthowifProfile.user']);
        $profile = $booking->muthowifProfile;

        return [
            'id' => $booking->id,
            'booking_code' => $booking->booking_code,
            'status' => $booking->status->value,
            'payment_status' => $booking->payment_status->value,
            'service_type' => $booking->service_type?->value,
            'starts_on' => $booking->starts_on->toDateString(),
            'ends_on' => $booking->ends_on->toDateString(),
            'total_amount' => (float) $booking->total_amount,
            'pricing' => self::pricing($booking),
            'created_at' => $booking->created_at?->toIso8601String(),
            'muthowif_name' => $profile?->user?->name ?? 'Muthowif',
            'muthowif_avatar' => $profile ? ApiMediaUrl::muthowifAvatar($profile) : null,
        ];
    }

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
            'package_price_snapshot' => (float) ($booking->package_price_snapshot ?? 0),
            'pricing' => self::pricing($booking, $forMuthowif),
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
            'documents' => self::documents($booking),
            'is_support' => $booking->isSupport(),
            'completion_requested_at' => $booking->completion_requested_at?->toIso8601String(),
            'muthowif_rejection_kind' => $booking->muthowif_rejection_kind?->value,
            'muthowif_rejection_kind_label' => $booking->muthowif_rejection_kind?->label(),
            'muthowif_rejection_note' => $booking->muthowif_rejection_note,
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
                'avatar' => ApiMediaUrl::muthowifAvatar($profile),
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
     * @return list<array{type: string, label: string, available: bool}>
     */
    public static function documents(MuthowifBooking $booking): array
    {
        if ($booking->isSupport()) {
            return [];
        }

        $items = [
            ['type' => 'outbound', 'path' => $booking->ticket_outbound_path, 'label' => 'Tiket berangkat'],
            ['type' => 'return', 'path' => $booking->ticket_return_path, 'label' => 'Tiket pulang'],
            ['type' => 'passport', 'path' => $booking->passport_path, 'label' => 'Paspor'],
            ['type' => 'itinerary', 'path' => $booking->itinerary_path, 'label' => 'Itinerary'],
            ['type' => 'visa', 'path' => $booking->visa_path, 'label' => 'Visa'],
        ];

        return array_values(array_map(static fn (array $item): array => [
            'type' => $item['type'],
            'label' => $item['label'],
            'available' => filled($item['path']),
        ], array_filter($items, static fn (array $item): bool => filled($item['path']))));
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
