<?php

namespace App\ViewModels\Booking;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Models\MuthowifBooking;
use App\Support\BookingPricingViewData;
use App\Support\IndonesianNumber;
use App\Support\PlatformFee;
use Illuminate\Support\Collection;

final class MuthowifBookingIndexCardData
{
    public function __construct(
        public readonly MuthowifBooking $booking,
        public readonly int $nights,
        public readonly float $serviceSubtotal,
        public readonly Collection $addonLines,
        public readonly float $sameHotelLine,
        public readonly float $transportLine,
        public readonly float $muthowifNet,
        public readonly float $muthowifFee,
        public readonly string $muthowifNetFormatted,
        public readonly string $badgeClass,
        public readonly string $accentClass,
        public readonly bool $isCompany,
        public readonly bool $canCancelUnpaid,
        public readonly bool $isPending,
        public readonly bool $hasPendingReschedule,
        public readonly int $documentCount,
        public readonly bool $hasDocuments,
        public readonly string $platformPct,
        public readonly string $rejectNoteOld,
        public readonly string $customerName,
        public readonly string $dateRangeLabel,
    ) {}

    /**
     * @param  array<string, mixed>|Collection<string, mixed>  $addonsById
     */
    public static function make(MuthowifBooking $booking, array|Collection $addonsById): self
    {
        $pricing = BookingPricingViewData::forMuthowif($booking, $addonsById);
        $st = $booking->status;
        $customer = $booking->customer;

        $documentCount = collect([
            $booking->ticket_outbound_path,
            $booking->ticket_return_path,
            $booking->passport_path,
            $booking->itinerary_path,
            $booking->visa_path,
        ])->filter(fn ($p) => filled($p))->count();

        $rejectNoteOld = (string) old('muthowif_rejection_note', '');

        return new self(
            booking: $booking,
            nights: $pricing['nights'],
            serviceSubtotal: $pricing['serviceSubtotal'],
            addonLines: $pricing['addonLines'],
            sameHotelLine: $pricing['sameHotelLine'],
            transportLine: $pricing['transportLine'],
            muthowifNet: $pricing['muthowifNet'],
            muthowifFee: $pricing['muthowifFee'],
            muthowifNetFormatted: IndonesianNumber::formatThousands((string) (int) round($pricing['muthowifNet'])),
            badgeClass: self::badgeClass($st),
            accentClass: self::accentClass($st, $booking->payment_status),
            isCompany: $customer?->isCompanyCustomer() ?? false,
            canCancelUnpaid: $st === BookingStatus::Confirmed && $booking->payment_status === PaymentStatus::Pending,
            isPending: $st === BookingStatus::Pending,
            hasPendingReschedule: ((int) ($booking->pending_reschedule_requests_count ?? 0)) > 0,
            documentCount: $documentCount,
            hasDocuments: $documentCount > 0,
            platformPct: rtrim(rtrim(number_format(PlatformFee::getRate() * 100, 1, ',', ''), '0'), ','),
            rejectNoteOld: $rejectNoteOld,
            customerName: (string) ($customer?->name ?? '—'),
            dateRangeLabel: $booking->starts_on->format('d/m/Y').' – '.$booking->ends_on->format('d/m/Y'),
        );
    }

    public static function badgeClass(BookingStatus $status): string
    {
        return match ($status) {
            BookingStatus::Pending => 'bg-amber-100 text-amber-900 ring-amber-200/90',
            BookingStatus::Confirmed => 'bg-emerald-100 text-emerald-900 ring-emerald-200/90',
            BookingStatus::Completed => 'bg-slate-100 text-slate-800 ring-slate-200/90',
            BookingStatus::Cancelled => 'bg-red-100 text-red-800 ring-red-200/80',
            default => 'bg-slate-100 text-slate-700 ring-slate-200/80',
        };
    }

    public static function accentClass(BookingStatus $status, PaymentStatus $paymentStatus): string
    {
        return match ($status) {
            BookingStatus::Pending => 'bg-amber-500',
            BookingStatus::Confirmed => $paymentStatus === PaymentStatus::Paid ? 'bg-emerald-500' : 'bg-amber-500',
            BookingStatus::Completed => 'bg-emerald-500',
            BookingStatus::Cancelled => 'bg-red-400',
            default => 'bg-slate-400',
        };
    }
}
