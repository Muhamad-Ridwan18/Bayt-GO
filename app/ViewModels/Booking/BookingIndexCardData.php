<?php

namespace App\ViewModels\Booking;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Models\MuthowifBooking;
use App\Support\BookingPricingViewData;
use App\Support\IndonesianNumber;
use Illuminate\Support\Collection;

final class BookingIndexCardData
{
    /**
     * @param  array{bar: string, glow: string, badge: string}  $cardStyle
     */
    public function __construct(
        public readonly MuthowifBooking $booking,
        public readonly array $cardStyle,
        public readonly int $nights,
        public readonly float $serviceSubtotal,
        public readonly Collection $addonLines,
        public readonly float $sameHotelLine,
        public readonly float $transportLine,
        public readonly float $customerGross,
        public readonly float $customerFee,
        public readonly string $customerGrossFormatted,
        public readonly bool $showPayButton,
        public readonly bool $showInvoiceButton,
        public readonly bool $showRefundButton,
        public readonly bool $showRescheduleButton,
        public readonly bool $showCancelForm,
        public readonly string $reviewButtonLabel,
        public readonly string $paymentStatusClass,
    ) {}

    /**
     * @param  array<string, mixed>|Collection<string, mixed>  $addonsById
     */
    public static function forCustomer(MuthowifBooking $booking, array|Collection $addonsById): self
    {
        $pricing = BookingPricingViewData::forCustomer($booking, $addonsById);
        $st = $booking->status;
        $cardStyles = self::statusCardStyles();
        $cardStyle = $cardStyles[$st->value] ?? $cardStyles[BookingStatus::Pending->value];

        return new self(
            booking: $booking,
            cardStyle: $cardStyle,
            nights: $pricing['nights'],
            serviceSubtotal: $pricing['baseSubtotal'],
            addonLines: $pricing['addonLines'],
            sameHotelLine: $pricing['sameHotelLine'],
            transportLine: $pricing['transportLine'],
            customerGross: $pricing['customerTotal'],
            customerFee: $pricing['customerPlatformFee'],
            customerGrossFormatted: IndonesianNumber::formatThousands((string) (int) round($pricing['customerTotal'])),
            showPayButton: $st === BookingStatus::Confirmed && $booking->payment_status === PaymentStatus::Pending,
            showInvoiceButton: in_array($booking->payment_status, [PaymentStatus::Paid, PaymentStatus::RefundPending, PaymentStatus::Refunded], true),
            showRefundButton: $st === BookingStatus::Confirmed && $booking->isPaid(),
            showRescheduleButton: $st === BookingStatus::Confirmed && $booking->isPaid(),
            showCancelForm: $st === BookingStatus::Pending,
            reviewButtonLabel: $booking->review
                ? (string) __('bookings.index.view_review')
                : (string) __('bookings.index.give_review'),
            paymentStatusClass: self::paymentStatusClass($booking->payment_status),
        );
    }

    /**
     * @return array<string, array{bar: string, glow: string, badge: string}>
     */
    public static function statusCardStyles(): array
    {
        return [
            BookingStatus::Pending->value => [
                'bar' => 'from-amber-400 via-amber-500 to-orange-500',
                'glow' => 'shadow-amber-500/10',
                'badge' => 'bg-amber-100 text-amber-950 ring-amber-200/80',
            ],
            BookingStatus::Confirmed->value => [
                'bar' => 'from-emerald-400 via-emerald-500 to-teal-600',
                'glow' => 'shadow-emerald-500/10',
                'badge' => 'bg-emerald-100 text-emerald-950 ring-emerald-200/80',
            ],
            BookingStatus::Completed->value => [
                'bar' => 'from-brand-400 via-brand-500 to-brand-700',
                'glow' => 'shadow-brand-500/15',
                'badge' => 'bg-brand-100 text-brand-950 ring-brand-200/80',
            ],
            BookingStatus::Cancelled->value => [
                'bar' => 'from-red-300 via-red-400 to-red-500',
                'glow' => 'shadow-red-500/10',
                'badge' => 'bg-red-100 text-red-800 ring-red-200/80',
            ],
        ];
    }

    public static function paymentStatusClass(PaymentStatus $status): string
    {
        return match ($status) {
            PaymentStatus::Paid => 'text-emerald-600',
            PaymentStatus::RefundPending => 'text-amber-600',
            PaymentStatus::Refunded => 'text-red-600',
            PaymentStatus::Pending => 'text-slate-500',
            default => 'text-slate-600',
        };
    }
}
