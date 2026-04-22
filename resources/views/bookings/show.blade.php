@php
    $b = $booking;
@endphp
<x-app-layout>
    <div
        class="relative min-h-[calc(100vh-4rem)] overflow-hidden bg-slate-50"
        x-data="customerBookingLive({
            userId: @js(auth()->id()),
            bookingId: @js($b->getKey()),
            liveMode: 'customer_show',
            fragmentUrl: @js(route('bookings.show.fragment', $b)),
            showUrl: @js(route('bookings.show', $b)),
        })"
    >
        <div class="pointer-events-none absolute inset-0 overflow-hidden" aria-hidden="true">
            <div class="absolute -right-24 -top-24 h-96 w-96 rounded-full bg-brand-400/15 blur-3xl"></div>
            <div class="absolute -left-20 top-32 h-80 w-80 rounded-full bg-emerald-400/10 blur-3xl"></div>
            <div class="absolute bottom-0 left-1/2 h-64 w-[120%] -translate-x-1/2 bg-gradient-to-t from-white to-transparent"></div>
        </div>

        <div class="relative z-10 mx-auto max-w-3xl px-4 pb-16 pt-8 sm:px-6 lg:px-8">
            <a href="{{ route('bookings.index') }}" class="mb-6 inline-flex items-center gap-2 text-sm font-semibold text-brand-700 transition hover:text-brand-800">
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
                ])
            </div>
        </div>
    </div>
</x-app-layout>
