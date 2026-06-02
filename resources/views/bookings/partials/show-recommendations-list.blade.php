@php
    $b = $booking;
    $alternatives = $referralNetworkAlternatives ?? collect();

    $referralLayananQuery = array_filter([
        'start_date' => $b->starts_on?->toDateString(),
        'end_date' => $b->ends_on?->toDateString(),
        'service_type' => $b->service_type?->value,
        'pilgrim_count' => $b->pilgrim_count !== null ? (string) (int) $b->pilgrim_count : null,
    ], fn ($v) => filled($v));
@endphp

@if ($alternatives->isEmpty())
    <p class="rounded-xl border border-dashed border-slate-200 bg-slate-50/80 px-4 py-5 text-center text-xs text-slate-600">
        {{ __('bookings.show.referral_network_empty') }}
    </p>
    <a
        href="{{ route('layanan.index', $referralLayananQuery) }}"
        class="mt-4 inline-flex w-full items-center justify-center rounded-xl border border-brand-200 bg-white px-4 py-2.5 text-sm font-semibold text-brand-800 transition hover:bg-brand-50"
    >
        {{ __('bookings.show.browse_all_muthowifs') }}
    </a>
@else
    <ul class="space-y-4">
        @foreach ($alternatives->take(5) as $profile)
            @php
                $reviewsCount = (int) ($profile->booking_reviews_count ?? 0);
                $avgRating = $profile->booking_reviews_avg_rating !== null
                    ? round((float) $profile->booking_reviews_avg_rating, 1)
                    : null;
            @endphp
            <li class="rounded-xl border border-slate-100 bg-slate-50/50 p-3.5">
                <div class="flex gap-3">
                    <img
                        src="{{ route('layanan.photo', $profile) }}"
                        alt=""
                        class="h-12 w-12 shrink-0 rounded-xl object-cover ring-1 ring-white shadow-sm"
                        loading="lazy"
                    >
                    <div class="min-w-0 flex-1">
                        <p class="truncate font-semibold text-slate-900">{{ $profile->user?->name ?? '—' }}</p>
                        <p class="mt-0.5 text-xs text-slate-600">
                            {{ $b->service_type?->label() ?? '—' }}
                            · {{ __('bookings.index.pilgrims_count', ['count' => $b->pilgrim_count, 'pilgrims_word' => __('common.pilgrims')]) }}
                        </p>
                        @if ($reviewsCount > 0 && $avgRating !== null)
                            <p class="mt-1 text-xs font-semibold text-amber-800">
                                {{ __('marketplace.card.reviews_line', ['rating' => $avgRating, 'count' => $reviewsCount]) }}
                            </p>
                        @endif
                    </div>
                </div>
                <a
                    href="{{ route('layanan.show', array_merge(['publicProfile' => $profile], $referralLayananQuery)) }}"
                    class="mt-3 inline-flex w-full items-center justify-center gap-2 rounded-xl bg-brand-700 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-800"
                >
                    {{ __('bookings.show.referral_network_view_profile') }}
                    <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M3 10a.75.75 0 01.75-.75h10.638L10.23 5.29a.75.75 0 111.04-1.08l5.5 5.25a.75.75 0 010 1.08l-5.5 5.25a.75.75 0 11-1.04-1.08l4.158-3.96H3.75A.75.75 0 013 10z" clip-rule="evenodd" />
                    </svg>
                </a>
            </li>
        @endforeach
    </ul>
    <a
        href="{{ route('layanan.index', $referralLayananQuery) }}"
        class="mt-4 inline-flex w-full items-center justify-center rounded-xl border border-brand-200 bg-white px-4 py-2.5 text-sm font-semibold text-brand-800 transition hover:bg-brand-50"
    >
        {{ __('bookings.show.browse_all_muthowifs') }}
    </a>
@endif
