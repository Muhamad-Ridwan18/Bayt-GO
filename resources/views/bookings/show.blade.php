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
            showUrl: @js(route('bookings.show', $b)),
        })"
    >
        <x-page-container class="pb-16 pt-6 sm:pt-8">
            <a href="{{ route('bookings.index') }}" class="mb-5 inline-flex items-center gap-2 text-sm font-semibold text-brand-700 transition hover:text-brand-800 sm:mb-6">
                <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M17 10a.75.75 0 01-.75.75H5.612l4.158 3.96a.75.75 0 11-1.04 1.08l-5.5-5.25a.75.75 0 010-1.08l5.5-5.25a.75.75 0 111.04 1.08L5.612 9.25H16.25A.75.75 0 0117 10z" clip-rule="evenodd" />
                </svg>
                {{ __('bookings.show.back_to_bookings') }}
            </a>

            <div x-ref="liveRoot">
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
