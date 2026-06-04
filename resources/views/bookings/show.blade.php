@php
    $b = $booking;
@endphp
<x-app-layout>
    <div
        class="min-h-[calc(100vh-4rem)] bg-slate-100/80"
        x-data="customerBookingLive({
            userId: @js(auth()->id()),
            bookingId: @js($b->getKey()),
            liveMode: 'customer_show',
            fragmentUrl: @js(route('bookings.show.fragment', $b)),
            liveStateUrl: @js(route('bookings.show.live-state', $b)),
            showUrl: @js(route('bookings.show', $b)),
            paymentStatusUrl: @js(route('bookings.payment.status', $b)),
            initialStatus: @js($b->status->value),
            initialPaymentStatus: @js($b->payment_status->value),
            paymentReturnPending: @js(\App\Support\BookingPaymentReturn::isAwaitingGatewayConfirmation(request()) && ! $b->isPaid()),
        })"
    >
        <x-page-container class="pb-16 pt-6 sm:pt-8">
            <a href="{{ route('bookings.index') }}" class="mb-5 inline-flex items-center gap-2 text-sm font-semibold text-brand-700 transition hover:text-brand-800 sm:mb-6">
                <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M17 10a.75.75 0 01-.75.75H5.612l4.158 3.96a.75.75 0 11-1.04 1.08l-5.5-5.25a.75.75 0 010-1.08l5.5-5.25a.75.75 0 111.04 1.08L5.612 9.25H16.25A.75.75 0 0117 10z" clip-rule="evenodd" />
                </svg>
                {{ __('bookings.show.back_to_bookings') }}
            </a>

            <div
                x-ref="liveGrid"
                class="grid grid-cols-1 gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(280px,360px)] lg:items-start lg:gap-8"
            >
                @include('bookings.partials.show-body', [
                    'booking' => $booking,
                    'addonsById' => $addonsById,
                    'refundEligibilityError' => $refundEligibilityError,
                    'rescheduleEligibilityError' => $rescheduleEligibilityError,
                    'refundPreview' => $refundPreview,
                    'referralNetworkAlternatives' => $referralNetworkAlternatives,
                    'showReferralNetworkPanel' => $showReferralNetworkPanel,
                    'activeEmergencyReport' => $activeEmergencyReport ?? null,
                    'selectableEmergencyOffers' => $selectableEmergencyOffers ?? collect(),
                ])
            </div>
        </x-page-container>
    </div>
</x-app-layout>
