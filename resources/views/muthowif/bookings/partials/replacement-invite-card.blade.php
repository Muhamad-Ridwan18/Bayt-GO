@php
    use App\Enums\BookingReplacementStatus;

    /** @var \App\Models\BookingReplacement $replacement */
    $booking = $replacement->incident?->muthowifBooking;
    $incident = $replacement->incident;
    $interactive = ($interactive ?? null) ?? $replacement->status === BookingReplacementStatus::AwaitingMuthowifConfirm;
@endphp

@if ($booking && $incident)
    @include('muthowif.bookings.partials.replacement-request-card', [
        'variant' => 'invite',
        'booking' => $booking,
        'incident' => $incident,
        'replacement' => $replacement,
        'addonsById' => $addonsById ?? collect(),
        'defaultOpen' => $defaultOpen ?? false,
        'interactive' => $interactive,
    ])
@endif
