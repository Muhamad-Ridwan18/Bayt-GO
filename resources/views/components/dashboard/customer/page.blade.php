@props(['page'])

<div class="scroll-smooth">
    <x-home.feed :page="$page->homePage" />

    <aside class="home-section-pad grid gap-5 border-t border-slate-100 pt-8 lg:grid-cols-2">
        <x-dashboard.customer.stats-panel :tiles="$page->statTiles" />
        <x-dashboard.customer.upcoming-trip
            :trip="$page->nextTrip"
            :bookings-url="$page->bookingsIndexUrl"
            :layanan-url="$page->layananIndexUrl"
        />
    </aside>
</div>
