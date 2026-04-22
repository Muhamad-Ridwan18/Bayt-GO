<x-app-layout>
    <div
        class="relative min-h-[calc(100vh-4rem)] overflow-hidden bg-slate-50"
        x-data="customerBookingLive({
            userId: @js(auth()->id()),
            bookingId: null,
            liveMode: 'customer_index',
            fragmentUrl: @js(route('bookings.index.live-fragment')),
            showUrl: null,
        })"
    >
        <div class="pointer-events-none absolute inset-0 overflow-hidden" aria-hidden="true">
            <div class="absolute -right-24 -top-24 h-96 w-96 rounded-full bg-brand-400/15 blur-3xl"></div>
            <div class="absolute -left-20 top-40 h-80 w-80 rounded-full bg-emerald-400/10 blur-3xl"></div>
            <div class="absolute bottom-0 left-1/2 h-64 w-[120%] -translate-x-1/2 bg-gradient-to-t from-white to-transparent"></div>
        </div>

        <div x-ref="liveRoot">
            @include('bookings.partials.index-body', [
                'bookings' => $bookings,
                'addonsById' => $addonsById,
                'bookingStatusCounts' => $bookingStatusCounts,
            ])
        </div>
    </div>
</x-app-layout>
