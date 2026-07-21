@php
    $page = $page;
    $b = $page->booking;
@endphp

<div data-live-part="main" class="min-w-0 ui-stack-compact lg:col-start-1 lg:row-start-1">
    @include('bookings.partials.show-detail-card', ['page' => $page])

    @include('bookings.partials.show-cancellation-alert', ['page' => $page])

    @include('bookings.partials.emergency-panel', [
        'booking' => $b,
        'activeEmergencyReport' => $page->activeEmergencyReport,
        'selectableEmergencyOffers' => $page->selectableEmergencyOffers,
    ])

    @include('bookings.partials.show-live-extended-main', ['page' => $page])
</div>

<div data-live-part="aside" class="min-w-0 lg:col-start-2 lg:row-start-1 lg:sticky lg:top-24 lg:self-start">
    @include('bookings.partials.show-sidebar', ['page' => $page])
</div>
