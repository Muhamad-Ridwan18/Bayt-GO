<x-app-layout>
    <div
        class="relative min-h-[calc(100vh-4rem)] overflow-hidden bg-gradient-to-b from-slate-100 via-slate-50 to-white py-6 sm:py-8"
        x-data="customerBookingLive({
            userId: @js(auth()->id()),
            bookingId: @js($booking->getKey()),
            liveMode: 'muthowif_show',
            fragmentUrl: @js(route('muthowif.bookings.show.fragment', $booking)),
            showUrl: @js(route('muthowif.bookings.show', $booking)),
        })"
    >
        <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_80%_40%_at_50%_-10%,rgba(120,53,15,0.06),transparent)]"></div>
        <div x-ref="liveRoot">
            @include('muthowif.bookings.partials.show-live', [
                'booking' => $booking,
                'addonsById' => $addonsById,
            ])
        </div>
    </div>
</x-app-layout>
