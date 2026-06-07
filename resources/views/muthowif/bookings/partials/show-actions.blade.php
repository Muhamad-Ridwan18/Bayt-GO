@php
    use App\Enums\BookingStatus;
    use App\Enums\PaymentStatus;

    $b = $booking;
    $st = $b->status;
@endphp

@if ($st === BookingStatus::Pending)
    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
        <h2 class="text-sm font-bold text-slate-900">{{ __('muthowif.booking_show.actions_heading') }}</h2>
        <p class="mt-1 text-xs text-slate-600">{{ __('muthowif.booking_show.actions_pending_hint') }}</p>
        @include('muthowif.bookings.partials.pending-booking-actions', ['booking' => $b, 'variant' => 'inline'])
    </section>
@elseif ($st === BookingStatus::Confirmed && $b->payment_status === PaymentStatus::Pending)
    <section class="rounded-2xl border border-amber-200 bg-amber-50 p-5 shadow-sm sm:p-6">
        <h2 class="text-sm font-bold text-amber-950">{{ __('muthowif.booking_show.actions_heading') }}</h2>
        <p class="mt-1 text-xs text-amber-900/80">{{ __('muthowif.booking_show.awaiting_payment_hint') }}</p>
        <form method="POST" action="{{ route('muthowif.bookings.cancel', $b) }}" class="mt-4" onsubmit="return confirm(@json(__('muthowif.bookings.cancel_unpaid_confirm')));">
            @csrf
            <x-submit-button class="h-9 w-full rounded-lg border border-red-200 bg-white px-3.5 text-xs font-semibold text-red-800 transition hover:bg-red-50 sm:w-auto">
                {{ __('muthowif.bookings.cancel_unpaid') }}
            </x-submit-button>
        </form>
    </section>
@endif

@if ($st === BookingStatus::Completed && $b->payment_status === PaymentStatus::Paid)
    <p class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
        {{ __('muthowif.bookings.completed_notice') }}
    </p>
@endif
