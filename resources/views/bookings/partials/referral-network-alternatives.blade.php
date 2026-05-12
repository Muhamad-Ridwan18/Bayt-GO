@php
    use App\Enums\MuthowifBookingMuthowifRejectionKind;
    /** @var \Illuminate\Support\Collection<int,\App\Models\MuthowifProfile> $referralNetworkAlternatives */
    $referralNetworkAlternatives = $referralNetworkAlternatives ?? collect();
@endphp
@if (!empty($showReferralNetworkPanel) && $showReferralNetworkPanel)
    <div class="mt-8 overflow-hidden rounded-3xl border border-violet-200/90 bg-gradient-to-br from-violet-50/90 via-white to-slate-50/80 p-6 shadow-md shadow-violet-900/5 ring-1 ring-violet-100/80 sm:p-8">
        @if (($booking->muthowif_rejection_kind ?? null) === MuthowifBookingMuthowifRejectionKind::SlotFull)
            <p class="text-sm leading-relaxed text-slate-800">
                {{ __('bookings.show.muthowif_slot_full_apology', ['name' => $booking->muthowifProfile?->user?->name ?? '—']) }}
            </p>
            @if (filled($booking->muthowif_rejection_note))
                <p class="mt-3 rounded-xl border border-slate-100 bg-white/90 px-3 py-2 text-xs text-slate-600 ring-1 ring-slate-100/80">
                    <span class="font-semibold text-slate-700">{{ __('bookings.show.muthowif_rejection_note_label') }}</span>
                    {{ $booking->muthowif_rejection_note }}
                </p>
            @endif
        @endif

        <h2 class="mt-5 text-base font-bold text-slate-900">{{ __('bookings.show.referral_network_heading') }}</h2>
        <p class="mt-1 text-xs leading-relaxed text-slate-600 sm:text-sm">{{ __('bookings.show.referral_network_intro') }}</p>

        @if ($referralNetworkAlternatives->isEmpty())
            <p class="mt-4 rounded-2xl border border-dashed border-slate-200 bg-white/80 px-4 py-6 text-center text-sm text-slate-600">
                {{ __('bookings.show.referral_network_empty') }}
            </p>
        @else
            <ul class="mt-4 space-y-3">
                @foreach ($referralNetworkAlternatives as $profile)
                    <li class="flex flex-col gap-3 rounded-2xl border border-slate-200/90 bg-white/95 p-4 shadow-sm ring-1 ring-slate-100/80 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex min-w-0 items-center gap-3">
                            <img
                                src="{{ route('layanan.photo', $profile) }}"
                                alt=""
                                class="h-14 w-14 shrink-0 rounded-2xl object-cover shadow ring-1 ring-slate-100"
                                loading="lazy"
                            >
                            <div class="min-w-0">
                                <p class="font-semibold text-slate-900">{{ $profile->user?->name ?? '—' }}</p>
                                <p class="mt-0.5 text-xs text-slate-600">{{ $booking->service_type?->label() ?? '—' }} · {{ __('bookings.index.pilgrims_count', ['count' => $booking->pilgrim_count, 'pilgrims_word' => __('common.pilgrims')]) }}</p>
                            </div>
                        </div>
                        <a
                            href="{{ route('layanan.show', $profile) }}"
                            class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-md shadow-brand-600/20 transition hover:bg-brand-700"
                        >
                            {{ __('bookings.show.referral_network_view_profile') }}
                            <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M3 10a.75.75 0 01.75-.75h10.638L10.23 5.29a.75.75 0 111.04-1.08l5.5 5.25a.75.75 0 010 1.08l-5.5 5.25a.75.75 0 11-1.04-1.08l4.158-3.96H3.75A.75.75 0 013 10z" clip-rule="evenodd" /></svg>
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
@endif
