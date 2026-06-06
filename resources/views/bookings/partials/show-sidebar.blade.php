@php
    use App\Enums\BookingStatus;
    use App\Enums\MuthowifBookingMuthowifRejectionKind;

    $b = $booking;
    $st = $b->status;
    $showPanel = ! empty($showReferralNetworkPanel) && $showReferralNetworkPanel;
    $isJadwalFull = ($b->muthowif_rejection_kind ?? null) === MuthowifBookingMuthowifRejectionKind::JadwalFull;

    $statusBadge = match ($st) {
        BookingStatus::Cancelled => 'bg-red-50 text-red-800 ring-red-200/90',
        BookingStatus::Confirmed => 'bg-emerald-50 text-emerald-900 ring-emerald-200/80',
        BookingStatus::Completed => 'bg-brand-50 text-brand-900 ring-brand-200/80',
        BookingStatus::Pending => 'bg-amber-50 text-amber-950 ring-amber-200/80',
        default => 'bg-slate-100 text-slate-800 ring-slate-200/80',
    };
@endphp

<aside class="flex flex-col gap-6 lg:sticky lg:top-24 lg:self-start">
    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
        <div class="flex items-start justify-between gap-3">
            <h2 class="text-sm font-bold text-slate-900">{{ __('bookings.show.status_card_title') }}</h2>
            <span class="inline-flex shrink-0 items-center rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 {{ $statusBadge }}">
                {{ $st->label() }}
            </span>
        </div>

        @if ($st === BookingStatus::Cancelled)
            <p class="mt-3 text-sm leading-relaxed text-slate-600">
                @if ($isJadwalFull)
                    {{ __('bookings.show.status_cancelled_jadwal_body', ['name' => $b->muthowifProfile?->user?->name ?? '—']) }}
                @else
                    {{ __('bookings.show.status_cancelled_body') }}
                @endif
            </p>
        @elseif ($st === BookingStatus::Pending)
            <p class="mt-3 text-sm leading-relaxed text-slate-600">{{ __('bookings.show.status_pending_body') }}</p>
        @elseif ($st === BookingStatus::Confirmed)
            <p class="mt-3 text-sm leading-relaxed text-slate-600">{{ __('bookings.show.status_confirmed_body') }}</p>
        @elseif ($st === BookingStatus::Completed)
            <p class="mt-3 text-sm leading-relaxed text-slate-600">{{ __('bookings.show.status_completed_body') }}</p>
        @endif

        <a
            href="{{ route('support.create') }}"
            class="mt-4 inline-flex items-center gap-1.5 text-sm font-semibold text-brand-700 hover:text-brand-800"
        >
            <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path fill-rule="evenodd" d="M10 2a6 6 0 00-6 6v3.586l-.707.707A1 1 0 004 13h12a1 1 0 00.707-1.707L16 10.586V8a6 6 0 00-6-6zm0 16a3 3 0 01-3-3h6a3 3 0 01-3 3z" clip-rule="evenodd" />
            </svg>
            {{ __('bookings.show.contact_support') }}
        </a>
    </section>

    @include('bookings.partials.show-sidebar-actions', ['booking' => $b])

    @if ($showPanel)
        <section id="booking-recommendations" class="scroll-mt-24 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <h2 class="text-sm font-bold text-slate-900">{{ __('bookings.show.recommendations_title') }}</h2>
            <p class="mt-1 text-xs leading-relaxed text-slate-600">{{ __('bookings.show.recommendations_subtitle') }}</p>
            <div class="mt-4">
                @include('bookings.partials.show-recommendations-list', [
                    'booking' => $b,
                    'referralNetworkAlternatives' => $referralNetworkAlternatives ?? collect(),
                ])
            </div>
        </section>
    @endif
</aside>
