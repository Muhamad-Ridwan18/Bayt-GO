<?php

namespace App\ViewModels\Booking;

use App\Enums\BookingStatus;
use App\Enums\MuthowifBookingMuthowifRejectionKind;
use App\Models\MuthowifBooking;
use App\Services\MuthowifNetworkReferralService;
use App\Support\BookingPaymentReturn;
use App\Support\BookingPricingViewData;
use App\Support\BookingSnapPaymentCatalog;
use App\Support\IndonesianNumber;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

final class BookingShowPageData
{
    /**
     * @param  array<string, mixed>  $addonsById
     */
    public function __construct(
        public readonly MuthowifBooking $booking,
        public readonly array $addonsById,
        public readonly mixed $refundEligibilityError,
        public readonly mixed $rescheduleEligibilityError,
        public readonly mixed $refundPreview,
        public readonly mixed $referralNetworkAlternatives,
        public readonly ?string $customerRecommendationSource,
        public readonly bool $showReferralNetworkPanel,
        public readonly mixed $activeEmergencyReport,
        public readonly mixed $selectableEmergencyOffers,
        public readonly bool $isSupport,
        public readonly int $nights,
        public readonly int $sleepNights,
        public readonly ?float $daily,
        public readonly float $baseSubtotal,
        public readonly Collection $addonLines,
        public readonly float $sameHotelLine,
        public readonly float $transportLine,
        public readonly float $customerTotal,
        public readonly float $customerPlatformFee,
        public readonly ?string $packageName,
        public readonly bool $isTopRated,
        public readonly bool $isJadwalFull,
        public readonly bool $paymentReturnPending,
        public readonly ?string $pendingRefundAmountFormatted,
        public readonly string $muthowifName,
        public readonly string $statusBadgeClass,
        public readonly bool $showsPaymentSection,
        public readonly bool $paymentWaitIsMoota,
    ) {}

    /**
     * @param  array<string, mixed>|Collection<string, mixed>  $addonsById
     */
    public static function make(
        Request $request,
        MuthowifBooking $booking,
        array|Collection $addonsById,
        mixed $refundEligibilityError,
        mixed $rescheduleEligibilityError,
        mixed $refundPreview,
        mixed $referralNetworkAlternatives,
        ?string $customerRecommendationSource,
        bool $showReferralNetworkPanel,
        mixed $activeEmergencyReport,
        mixed $selectableEmergencyOffers,
    ): self {
        if ($addonsById instanceof Collection) {
            $addonsById = $addonsById->all();
        }

        $pricing = BookingPricingViewData::forCustomer($booking, $addonsById);
        $st = $booking->status;
        $pend = $booking->pendingRefundRequest();

        return new self(
            booking: $booking,
            addonsById: $addonsById,
            refundEligibilityError: $refundEligibilityError,
            rescheduleEligibilityError: $rescheduleEligibilityError,
            refundPreview: $refundPreview,
            referralNetworkAlternatives: $referralNetworkAlternatives,
            customerRecommendationSource: $customerRecommendationSource,
            showReferralNetworkPanel: $showReferralNetworkPanel,
            activeEmergencyReport: $activeEmergencyReport,
            selectableEmergencyOffers: $selectableEmergencyOffers,
            isSupport: $pricing['isSupport'],
            nights: $pricing['nights'],
            sleepNights: $pricing['sleepNights'],
            daily: $pricing['daily'],
            baseSubtotal: $pricing['baseSubtotal'],
            addonLines: $pricing['addonLines'],
            sameHotelLine: $pricing['sameHotelLine'],
            transportLine: $pricing['transportLine'],
            customerTotal: $pricing['customerTotal'],
            customerPlatformFee: $pricing['customerPlatformFee'],
            packageName: $pricing['packageName'],
            isTopRated: $customerRecommendationSource === MuthowifNetworkReferralService::SOURCE_TOP_RATED,
            isJadwalFull: ($booking->muthowif_rejection_kind ?? null) === MuthowifBookingMuthowifRejectionKind::JadwalFull,
            paymentReturnPending: BookingPaymentReturn::isAwaitingGatewayConfirmation($request) && ! $booking->isPaid(),
            pendingRefundAmountFormatted: $pend
                ? self::formatMoneyStatic((float) $pend->net_refund_customer)
                : null,
            muthowifName: (string) ($booking->muthowifProfile?->user?->name ?? '—'),
            statusBadgeClass: self::statusBadgeClass($st),
            showsPaymentSection: in_array($st, [BookingStatus::Confirmed, BookingStatus::InProgress, BookingStatus::Completed], true)
                || ($st === BookingStatus::Cancelled && $booking->paid_at),
            paymentWaitIsMoota: BookingSnapPaymentCatalog::driver() === 'moota',
        );
    }

    public static function statusBadgeClass(BookingStatus $status): string
    {
        return match ($status) {
            BookingStatus::Cancelled => 'bg-red-50 text-red-800 ring-red-200/90',
            BookingStatus::Confirmed => 'bg-emerald-50 text-emerald-900 ring-emerald-200/80',
            BookingStatus::InProgress => 'bg-sky-50 text-sky-900 ring-sky-200/80',
            BookingStatus::Completed => 'bg-brand-50 text-brand-900 ring-brand-200/80',
            BookingStatus::Pending => 'bg-amber-50 text-amber-950 ring-amber-200/80',
            default => 'bg-slate-100 text-slate-800 ring-slate-200/80',
        };
    }

    public function formatMoney(float $amount): string
    {
        return self::formatMoneyStatic($amount);
    }

    public static function formatMoneyStatic(float $amount): string
    {
        return IndonesianNumber::formatThousands((string) (int) round($amount));
    }

    public function formatDate(mixed $date): string
    {
        $dateLocale = app()->getLocale() === 'id' ? 'id-ID' : 'en-GB';

        return Carbon::parse($date)->locale($dateLocale)->translatedFormat('d M Y');
    }

    /**
     * @return array<string, mixed>
     */
    public function liveAlpineConfig(): array
    {
        $b = $this->booking;

        return [
            'userId' => auth()->id(),
            'bookingId' => $b->getKey(),
            'liveMode' => 'customer_show',
            'fragmentUrl' => route('bookings.show.fragment', $b),
            'liveStateUrl' => route('bookings.show.live-state', $b),
            'showUrl' => route('bookings.show', $b),
            'paymentStatusUrl' => route('bookings.payment.status', $b),
            'initialStatus' => $b->status->value,
            'initialPaymentStatus' => $b->payment_status->value,
            'paymentReturnPending' => $this->paymentReturnPending,
        ];
    }
}
