<x-app-layout>
    <div
        class="min-h-[calc(100vh-4rem)] bg-slate-100/80 py-6 sm:py-8"
        x-data="customerBookingLive({
            userId: @js(auth()->id()),
            bookingId: @js($booking->getKey()),
            liveMode: 'muthowif_show',
            fragmentUrl: @js(route('muthowif.bookings.show.fragment', $booking)),
            showUrl: @js(route('muthowif.bookings.show', $booking)),
        })"
    >
        <div x-ref="liveRoot">
            @include('muthowif.bookings.partials.show-live', [
                'booking' => $booking,
                'addonsById' => $addonsById,
                'peerRecommendTargets' => $peerRecommendTargets,
                'openIncident' => $openIncident ?? null,
                'incomingReplacement' => $incomingReplacement ?? null,
                'peerReplacementsAwaitingConfirm' => $peerReplacementsAwaitingConfirm ?? collect(),
            ])
        </div>
    </div>
</x-app-layout>
