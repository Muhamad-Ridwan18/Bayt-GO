@php
    use App\Enums\MuthowifServiceType;
    use Illuminate\Support\Str;

    /** @var \App\Models\BookingReplacementOffer $offer */
    /** @var \App\Models\MuthowifBooking $booking */
    $profile = $offer->muthowifProfile;
    $user = $profile?->user;
    $name = $user?->name ?? '—';
    $initial = mb_substr($name, 0, 1);
    $fallbackSvg = 'data:image/svg+xml,'.rawurlencode(
        '<svg xmlns="http://www.w3.org/2000/svg" width="128" height="128"><rect fill="#e2e8f0" width="128" height="128"/><text x="50%" y="55%" dominant-baseline="middle" text-anchor="middle" font-size="48" fill="#475569">'
        .htmlspecialchars($initial, ENT_XML1 | ENT_QUOTES, 'UTF-8')
        .'</text></svg>'
    );

    $service = $profile?->services?->firstWhere('type', $booking->service_type);
    if ($service && filled($service->description)) {
        $bio = Str::limit(trim(strip_tags($service->description)), 160);
    } else {
        $langs = $profile?->languagesForDisplay() ?? [];
        $bio = count($langs) > 0
            ? __('emergency.customer.candidate_bio_langs', ['langs' => implode(' · ', array_slice($langs, 0, 4))])
            : __('emergency.customer.candidate_bio_fallback');
    }

    $avgRating = $profile?->average_rating !== null ? round((float) $profile->average_rating, 1) : null;
    $reviewsCount = (int) ($profile?->booking_reviews_count ?? 0);
    $confirmedCount = (int) ($profile?->confirmed_bookings_count ?? 0);
    $languages = $profile?->languagesForDisplay() ?? [];
@endphp

<article class="overflow-hidden rounded-2xl border border-emerald-200/90 bg-white shadow-sm ring-1 ring-emerald-100/50">
    <div class="flex gap-4 border-b border-slate-100 p-4 sm:p-5">
        <div class="relative shrink-0">
            @if ($profile)
                <img
                    src="{{ route('layanan.photo', $profile) }}"
                    alt=""
                    width="80"
                    height="80"
                    class="h-20 w-20 rounded-2xl bg-slate-100 object-cover ring-2 ring-white shadow-md"
                    loading="lazy"
                    onerror="this.onerror=null; this.src={!! json_encode($fallbackSvg) !!}"
                >
            @else
                <span class="flex h-20 w-20 items-center justify-center rounded-2xl bg-slate-200 text-2xl font-bold text-slate-600">{{ $initial }}</span>
            @endif
            <span class="absolute -bottom-1 -right-1 rounded-full bg-emerald-600 px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-wide text-white ring-2 ring-white">
                {{ __('marketplace.card.verified_badge') }}
            </span>
        </div>
        <div class="min-w-0 flex-1">
            <h4 class="text-lg font-bold text-slate-900">{{ $name }}</h4>
            @if ($offer->responded_at)
                <p class="mt-0.5 text-xs text-emerald-800">
                    {{ __('emergency.customer.candidate_ready_since', ['time' => $offer->responded_at->timezone(config('app.timezone'))->format('d/m/Y H:i')]) }}
                </p>
            @endif
            <div class="mt-2 flex flex-wrap gap-2 text-xs">
                @if ($avgRating !== null && $reviewsCount > 0)
                    <span class="inline-flex items-center gap-1 rounded-lg bg-amber-50 px-2.5 py-1 font-semibold text-amber-950 ring-1 ring-amber-200/70">
                        <span aria-hidden="true">★</span> {{ number_format($avgRating, 1, ',', '') }}
                        <span class="font-normal text-amber-900/80">({{ __('emergency.customer.candidate_reviews', ['count' => $reviewsCount]) }})</span>
                    </span>
                @endif
                @if ($confirmedCount > 0)
                    <span class="inline-flex rounded-lg bg-slate-100 px-2.5 py-1 font-semibold text-slate-700 ring-1 ring-slate-200/80">
                        {{ __('emergency.customer.candidate_confirmed', ['count' => $confirmedCount]) }}
                    </span>
                @endif
                @if ($booking->service_type === MuthowifServiceType::Group)
                    <span class="inline-flex rounded-lg bg-brand-50 px-2.5 py-1 font-semibold text-brand-800 ring-1 ring-brand-200/60">{{ __('enums.muthowif_service_type.group') }}</span>
                @else
                    <span class="inline-flex rounded-lg bg-violet-50 px-2.5 py-1 font-semibold text-violet-800 ring-1 ring-violet-200/60">{{ __('enums.muthowif_service_type.private') }}</span>
                @endif
            </div>
        </div>
    </div>

    <div class="space-y-3 px-4 py-4 sm:px-5">
        <p class="text-sm leading-relaxed text-slate-700">{{ $bio }}</p>
        @if ($languages !== [])
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">{{ __('emergency.customer.candidate_languages') }}</p>
                <p class="mt-1.5 flex flex-wrap gap-1.5">
                    @foreach (array_slice($languages, 0, 6) as $lang)
                        <span class="inline-flex rounded-md bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-700">{{ $lang }}</span>
                    @endforeach
                </p>
            </div>
        @endif
        @if (filled($profile?->slug))
            <a href="{{ route('layanan.show', $profile) }}" class="inline-flex items-center gap-1 text-sm font-semibold text-brand-700 hover:text-brand-800" target="_blank" rel="noopener">
                {{ __('emergency.customer.view_profile') }}
                <span aria-hidden="true">→</span>
            </a>
        @endif
    </div>

    <div class="border-t border-slate-100 bg-slate-50/50 px-4 py-4 sm:px-5">
        <form method="POST" action="{{ route('bookings.emergency.select', [$booking, $offer]) }}" onsubmit="return confirm(@json(__('emergency.customer.select_confirm', ['name' => $name])));">
            @csrf
            <x-submit-button class="w-full rounded-xl bg-emerald-700 px-4 py-3 text-sm font-bold text-white shadow-sm hover:bg-emerald-800">
                {{ __('emergency.customer.select_button') }}
            </x-submit-button>
        </form>
    </div>
</article>
