@php
    use App\Enums\MuthowifServiceType;
    use App\Enums\ReplacementOfferStatus;
    use App\Support\IndonesianNumber;
    use App\Support\PlatformFee;

    /** @var \App\Models\BookingReplacementOffer $offer */
    $report = $offer->report;
    $booking = $report?->muthowifBooking;
    $previousMuthowif = $booking?->muthowifProfile?->user;
    $nights = $booking?->billingNightsInclusive() ?? 0;

    $addonLines = collect();
    if ($booking && $booking->service_type === MuthowifServiceType::PrivateJamaah && ! empty($booking->add_ons_snapshot)) {
        $addonLines = collect($booking->add_ons_snapshot)->map(fn ($a) => (object) $a);
    }

    $daily = (float) ($booking?->daily_price_snapshot ?? 0);
    $serviceSubtotal = (float) ($nights * $daily);
    $addonsSum = $addonLines->sum(fn ($a) => (float) ($a->price ?? 0));
    $sameHotelPrice = (float) ($booking?->same_hotel_price_snapshot ?? 0);
    $sameHotelLine = $booking?->with_same_hotel ? ($nights * $sameHotelPrice) : 0.0;
    $transportLine = (float) ($booking?->transport_price_snapshot ?? 0);
    $totalGross = (float) ($serviceSubtotal + $addonsSum + $sameHotelLine + $transportLine);
    $muthowifNetIdr = (float) (PlatformFee::split($totalGross)['muthowif_net'] ?? 0);

    $statusBadgeClass = match ($offer->status) {
        ReplacementOfferStatus::Offered => 'bg-amber-100 text-amber-900 ring-amber-200/80',
        ReplacementOfferStatus::Accepted => 'bg-emerald-100 text-emerald-900 ring-emerald-200/80',
        default => 'bg-slate-100 text-slate-700 ring-slate-200/80',
    };
@endphp

<article class="overflow-hidden rounded-2xl border border-amber-200/90 bg-white shadow-sm ring-1 ring-amber-100/60">
    <div class="border-b border-amber-100/80 bg-gradient-to-r from-amber-50/90 to-white px-5 py-4 sm:px-6">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div class="min-w-0">
                <p class="text-[11px] font-bold uppercase tracking-wide text-amber-800">{{ __('emergency.muthowif.booking') }}</p>
                <p class="mt-0.5 font-mono text-lg font-bold text-slate-900">{{ $booking?->booking_code ?? '—' }}</p>
            </div>
            <span class="inline-flex shrink-0 items-center rounded-full px-3 py-1 text-xs font-semibold ring-1 {{ $statusBadgeClass }}">
                {{ $offer->status->label() }}
            </span>
        </div>
        @if ($offer->offered_at)
            <p class="mt-2 text-xs text-slate-500">
                {{ __('emergency.muthowif.offered_at') }}: {{ $offer->offered_at->timezone(config('app.timezone'))->format('d/m/Y H:i') }} WIB
                @if ($offer->batch_number)
                    · {{ __('emergency.muthowif.batch') }} {{ $offer->batch_number }}
                @endif
            </p>
        @endif
    </div>

    <div class="space-y-5 p-5 sm:p-6">
        <div class="rounded-xl border border-slate-200/80 bg-slate-50/50 px-4 py-3">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('emergency.muthowif.incident') }}</p>
            <p class="mt-1 text-sm font-semibold text-slate-900">{{ $report?->case_type?->label() ?? '—' }}</p>
            @if (filled($report?->description))
                <p class="mt-2 text-sm leading-relaxed text-slate-700 whitespace-pre-wrap">{{ $report->description }}</p>
            @else
                <p class="mt-1 text-sm text-slate-500">{{ __('emergency.muthowif.no_description') }}</p>
            @endif
        </div>

        @if ($booking)
            <dl class="grid gap-4 sm:grid-cols-2">
                <div>
                    <dt class="text-xs font-semibold text-slate-500">{{ __('emergency.muthowif.service_type') }}</dt>
                    <dd class="mt-1 text-sm font-medium text-slate-900">{{ $booking->service_type?->label() ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold text-slate-500">{{ __('emergency.muthowif.pilgrims') }}</dt>
                    <dd class="mt-1 text-sm font-medium text-slate-900">
                        {{ __('muthowif.bookings.pilgrim_count', ['count' => $booking->pilgrim_count]) }}
                    </dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-xs font-semibold text-slate-500">{{ __('emergency.muthowif.dates') }}</dt>
                    <dd class="mt-1 text-sm font-medium text-slate-900">
                        {{ $booking->starts_on?->format('d/m/Y') }} – {{ $booking->ends_on?->format('d/m/Y') }}
                        <span class="text-slate-500">({{ __('emergency.muthowif.nights', ['count' => $nights]) }})</span>
                    </dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-xs font-semibold text-slate-500">{{ __('emergency.muthowif.previous_muthowif') }}</dt>
                    <dd class="mt-1 text-sm font-medium text-slate-900">{{ $previousMuthowif?->name ?? '—' }}</dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-xs font-semibold text-slate-500">{{ __('emergency.muthowif.options') }}</dt>
                    <dd class="mt-2 flex flex-wrap gap-2">
                        @if ($booking->with_same_hotel)
                            <span class="inline-flex rounded-lg bg-brand-50 px-2.5 py-1 text-xs font-semibold text-brand-800 ring-1 ring-brand-200/60">{{ __('emergency.muthowif.same_hotel') }}</span>
                        @endif
                        @if ($booking->with_transport)
                            <span class="inline-flex rounded-lg bg-brand-50 px-2.5 py-1 text-xs font-semibold text-brand-800 ring-1 ring-brand-200/60">{{ __('emergency.muthowif.transport') }}</span>
                        @endif
                        @if (! $booking->with_same_hotel && ! $booking->with_transport)
                            <span class="text-sm text-slate-500">{{ __('emergency.muthowif.no_extra_options') }}</span>
                        @endif
                    </dd>
                </div>
                @if ($addonLines->isNotEmpty())
                    <div class="sm:col-span-2">
                        <dt class="text-xs font-semibold text-slate-500">{{ __('emergency.muthowif.addons') }}</dt>
                        <dd class="mt-2">
                            <ul class="space-y-1 text-sm text-slate-800">
                                @foreach ($addonLines as $addon)
                                    <li class="flex flex-wrap items-baseline justify-between gap-2 rounded-lg bg-white px-3 py-1.5 ring-1 ring-slate-100">
                                        <span>{{ $addon->name ?? '—' }}</span>
                                        @if (isset($addon->price))
                                            <span class="text-xs font-semibold text-slate-600">Rp {{ IndonesianNumber::formatThousands((string) $addon->price) }}</span>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        </dd>
                    </div>
                @endif
                @if ($muthowifNetIdr > 0)
                    <div class="sm:col-span-2">
                        <dt class="text-xs font-semibold text-slate-500">{{ __('emergency.muthowif.estimated_net') }}</dt>
                        <dd class="mt-1 text-base font-bold text-emerald-800">
                            Rp {{ IndonesianNumber::formatThousands((string) (int) round($muthowifNetIdr)) }}
                            <span class="block text-xs font-normal text-slate-500">{{ __('emergency.muthowif.estimated_net_hint') }}</span>
                        </dd>
                    </div>
                @endif
            </dl>
        @endif

        @if ($offer->status === ReplacementOfferStatus::Offered)
            <div class="border-t border-slate-100 pt-4">
                <p class="text-xs text-slate-600">{{ __('emergency.muthowif.accept_hint') }}</p>
                <div class="mt-3 flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-end">
                    <form method="POST" action="{{ route('muthowif.emergency-offers.accept', $offer) }}" class="sm:shrink-0">
                        @csrf
                        <x-submit-button class="w-full rounded-xl bg-emerald-700 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-emerald-800 sm:w-auto">
                            {{ __('emergency.muthowif.accept') }}
                        </x-submit-button>
                    </form>
                    <form method="POST" action="{{ route('muthowif.emergency-offers.decline', $offer) }}" class="flex min-w-0 flex-1 flex-col gap-2 sm:flex-row sm:items-end">
                        @csrf
                        <label class="min-w-0 flex-1">
                            <span class="sr-only">{{ __('emergency.muthowif.decline_note') }}</span>
                            <input
                                type="text"
                                name="decline_note"
                                maxlength="2000"
                                placeholder="{{ __('emergency.muthowif.decline_note_placeholder') }}"
                                class="w-full rounded-xl border-slate-200 text-sm shadow-sm focus:border-brand-400 focus:ring-brand-500/20"
                            >
                        </label>
                        <x-submit-button class="w-full shrink-0 rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50 sm:w-auto">
                            {{ __('emergency.muthowif.decline') }}
                        </x-submit-button>
                    </form>
                </div>
            </div>
        @elseif ($offer->status === ReplacementOfferStatus::Accepted)
            <p class="rounded-xl bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-900 ring-1 ring-emerald-200/60">
                {{ __('emergency.flash.offer_accepted') }}
            </p>
        @endif
    </div>
</article>
