<x-app-layout>
    <div
        class="min-h-[calc(100vh-4rem)] bg-slate-100 py-6 sm:py-8"
        x-data="customerBookingLive({
            userId: @js(auth()->id()),
            bookingId: null,
            liveMode: 'muthowif_index',
            fragmentUrl: @js(route('muthowif.bookings.index.live-fragment')),
            showUrl: null,
        })"
    >
        <div x-ref="liveRoot">
            @include('muthowif.bookings.partials.index-live', [
                'bookings' => $bookings,
                'addonsById' => $addonsById,
            ])
        </div>
    </div>
</x-app-layout>
