@php
    /** @var \App\Models\BookingReplacement $replacement */
    $booking = $replacement->incident?->muthowifBooking;
    $incident = $replacement->incident;
@endphp

<article class="overflow-hidden rounded-2xl border-2 border-violet-200 bg-white shadow-sm ring-1 ring-violet-100/80">
    <div class="border-b border-violet-100 bg-violet-50/90 px-4 py-3 sm:px-5">
        <p class="text-[11px] font-semibold uppercase tracking-wide text-violet-800">{{ __('incidents.replacement_offer_title') }}</p>
        <p class="mt-1 font-semibold text-slate-900">{{ $booking?->customer?->name ?? '—' }}</p>
        @if (filled($booking?->booking_code))
            <p class="font-mono text-xs text-slate-600">{{ $booking->booking_code }}</p>
        @endif
        @if ($incident)
            <p class="mt-1 text-xs font-medium text-violet-900">
                {{ $incident->case_type->label() }}
                <span class="text-violet-600">·</span>
                {{ $incident->status->label() }}
            </p>
        @endif
    </div>
    <div class="p-4 sm:p-5">
        @if (filled($replacement->admin_note))
            <p class="mb-3 rounded-lg bg-slate-50 px-3 py-2 text-sm text-slate-700">{{ $replacement->admin_note }}</p>
        @endif
        <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap">
            <form method="POST" action="{{ route('muthowif.replacements.confirm', $replacement) }}">
                @csrf
                <button type="submit" class="inline-flex w-full items-center justify-center rounded-xl bg-emerald-700 px-5 py-2.5 text-sm font-semibold text-white hover:bg-emerald-800 sm:w-auto">
                    {{ __('incidents.muthowif_confirm_replacement') }}
                </button>
            </form>
            <form method="POST" action="{{ route('muthowif.replacements.decline', $replacement) }}" class="flex min-w-0 flex-1 flex-col gap-2 sm:max-w-xs">
                @csrf
                <input type="text" name="note" class="w-full rounded-xl border-slate-200 text-sm" placeholder="{{ __('incidents.muthowif.decline_note_placeholder') }}">
                <button type="submit" class="inline-flex w-full items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-800 hover:bg-slate-50">
                    {{ __('incidents.muthowif_decline_replacement') }}
                </button>
            </form>
        </div>
        @if ($booking)
            <a href="{{ route('muthowif.bookings.show', $booking) }}" class="mt-3 inline-flex text-xs font-semibold text-brand-700 hover:text-brand-800">
                {{ __('incidents.muthowif.view_booking_detail') }} →
            </a>
        @endif
    </div>
</article>
