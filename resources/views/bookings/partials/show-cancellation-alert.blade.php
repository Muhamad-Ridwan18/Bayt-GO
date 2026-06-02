@php
    use App\Enums\BookingStatus;
    use App\Enums\MuthowifBookingMuthowifRejectionKind;

    $b = $booking;
    $showPanel = ! empty($showReferralNetworkPanel) && $showReferralNetworkPanel;
    $isJadwalFull = ($b->muthowif_rejection_kind ?? null) === MuthowifBookingMuthowifRejectionKind::JadwalFull;
@endphp

@if ($showPanel && $b->status === BookingStatus::Cancelled)
    <section class="rounded-2xl border border-rose-200 bg-rose-50 p-5 shadow-sm sm:p-6">
        <div class="flex gap-3">
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-rose-100 text-rose-700">
                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 6a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 6zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                </svg>
            </span>
            <div class="min-w-0 flex-1">
                <h3 class="text-base font-bold text-rose-950">
                    {{ $isJadwalFull ? __('bookings.show.cancellation_alert_jadwal_title') : __('bookings.show.cancellation_alert_title') }}
                </h3>
                <p class="mt-2 text-sm leading-relaxed text-rose-900/90">
                    @if ($isJadwalFull)
                        {{ __('bookings.show.muthowif_jadwal_full_apology', ['name' => $b->muthowifProfile?->user?->name ?? '—']) }}
                    @else
                        {{ __('bookings.show.referral_network_cancelled_intro', ['name' => $b->muthowifProfile?->user?->name ?? '—']) }}
                    @endif
                </p>
                @if (filled($b->muthowif_rejection_note))
                    <p class="mt-3 rounded-xl border border-rose-100 bg-white/80 px-3 py-2 text-xs text-rose-900/80">
                        <span class="font-semibold">{{ __('bookings.show.muthowif_rejection_note_label') }}</span>
                        {{ $b->muthowif_rejection_note }}
                    </p>
                @endif
                @if (($referralNetworkAlternatives ?? collect())->isNotEmpty())
                    <a
                        href="#booking-recommendations"
                        class="mt-4 inline-flex items-center justify-center rounded-xl border border-brand-400 bg-white px-4 py-2 text-sm font-semibold text-brand-800 transition hover:bg-brand-50"
                    >
                        {{ __('bookings.show.view_recommendations') }}
                    </a>
                @endif
            </div>
        </div>
    </section>
@endif
