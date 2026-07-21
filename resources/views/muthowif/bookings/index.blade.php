<x-app-layout>
    <div
        class="ui-page-y-compact min-h-[calc(100vh-4rem)] bg-slate-100"
        x-data="customerBookingLive({
            userId: @js(auth()->id()),
            bookingId: null,
            liveMode: 'muthowif_index',
            fragmentUrl: @js(route('muthowif.bookings.index.live-fragment')),
            showUrl: null,
        })"
    >
        <div x-ref="liveRoot">
            @include('muthowif.bookings.partials.index-live', ['page' => $page])
        </div>
    </div>
</x-app-layout>
