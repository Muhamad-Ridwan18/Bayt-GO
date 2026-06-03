@php
    /** @var \App\Models\MuthowifProfile $profile */

    $experienceLabel = $experienceStat ?? '—';
    $pilgrimStat = $confirmedBookings >= 500
        ? __('marketplace.show.stat_pilgrims_count', ['count' => '500'])
        : ($confirmedBookings > 0
            ? __('marketplace.show.stat_pilgrims_few', ['count' => $confirmedBookings])
            : '—');
    $langList = $profile->languagesForDisplay();
    $langStat = count($langList) > 0
        ? __('marketplace.show.stat_languages_count', ['count' => count($langList)])
        : '—';
@endphp

<section class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-lg shadow-slate-900/5 ring-1 ring-slate-100/80">
    <div class="grid lg:grid-cols-[minmax(0,340px)_1fr]">
        <div class="relative aspect-[4/5] min-h-[280px] bg-slate-100 sm:min-h-[320px] lg:aspect-auto lg:min-h-[420px]">
            <img
                src="{{ route('layanan.photo', $profile) }}"
                alt="{{ $profile->user->name }}"
                class="absolute inset-0 h-full w-full object-cover"
                loading="eager"
                onerror="this.onerror=null; this.src={!! json_encode($fallbackSvg) !!}"
            >
            <span class="absolute bottom-4 left-4 inline-flex items-center gap-1.5 rounded-full bg-emerald-600 px-3 py-1.5 text-[11px] font-bold uppercase tracking-wide text-white shadow-lg ring-2 ring-white">
                <svg class="h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" /></svg>
                {{ __('marketplace.show.verified_overlay') }}
            </span>
        </div>

        <div class="flex flex-col justify-center p-6 sm:p-8 lg:p-10">
            <p class="text-xs font-bold uppercase tracking-widest text-brand-700">{{ __('marketplace.show.kicker') }}</p>
            <h1 class="mt-2 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl lg:text-4xl">{{ $profile->user->name }}</h1>
            <p class="mt-2 text-sm text-slate-600 sm:text-base">{{ __('marketplace.show.tagline') }}</p>

            @if ($reviewsCount === 0 && $confirmedBookings === 0)
                <p class="mt-3 inline-flex w-fit rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-900 ring-1 ring-amber-200/80">
                    {{ __('marketplace.card.new_marketplace') }}
                </p>
            @endif

            @if ($langList !== [])
                <div class="mt-4 flex flex-wrap items-center gap-3">
                    @foreach (array_slice($langList, 0, 4) as $lang)
                        <span class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-700">
                            <span class="flex h-6 w-6 items-center justify-center rounded-full bg-slate-100 text-xs" aria-hidden="true">🌐</span>
                            {{ $lang }}
                        </span>
                    @endforeach
                </div>
            @endif

            <dl class="mt-6 grid grid-cols-2 gap-4 border-y border-slate-100 py-5 sm:grid-cols-4">
                <div>
                    <dt class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">{{ __('marketplace.show.stat_experience') }}</dt>
                    <dd class="mt-1 text-sm font-bold text-slate-900 line-clamp-2">{{ $experienceLabel }}</dd>
                </div>
                <div>
                    <dt class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">{{ __('marketplace.show.stat_pilgrims') }}</dt>
                    <dd class="mt-1 text-sm font-bold text-slate-900">{{ $pilgrimStat }}</dd>
                </div>
                <div>
                    <dt class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">{{ __('marketplace.show.stat_languages') }}</dt>
                    <dd class="mt-1 text-sm font-bold text-slate-900">{{ $langStat }}</dd>
                    @if (count($langList) > 0)
                        <dd class="mt-0.5 text-[11px] text-slate-500 line-clamp-1">{{ __('marketplace.show.stat_languages_names', ['names' => implode(', ', array_slice($langList, 0, 3))]) }}</dd>
                    @endif
                </div>
                <div>
                    <dt class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">{{ __('marketplace.show.stat_rating') }}</dt>
                    <dd class="mt-1 flex items-center gap-1 text-sm font-bold text-slate-900">
                        @if ($reviewsCount > 0 && $avgRating !== null)
                            <svg class="h-4 w-4 text-gold" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" /></svg>
                            {{ $avgRating }} <span class="font-normal text-slate-500">({{ $reviewsCount }} review)</span>
                        @else
                            <span class="text-slate-400">—</span>
                        @endif
                    </dd>
                </div>
            </dl>

            <div class="mt-5 flex flex-col gap-3 sm:flex-row sm:flex-wrap">
                @if ($canBook)
                    <a href="{{ $bookingPageUrl }}" class="inline-flex min-h-[3rem] flex-1 items-center justify-center gap-2 rounded-xl bg-brand-700 px-6 py-3 text-base font-bold text-white shadow-md transition hover:bg-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 sm:flex-none sm:min-w-[200px]">
                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.435 9.884-9.881 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                        {{ __('marketplace.show.book_now') }}
                    </a>
                @else
                    <a href="{{ $bookingPageUrl }}" class="inline-flex min-h-[3rem] flex-1 items-center justify-center gap-2 rounded-xl bg-brand-700 px-6 py-3 text-base font-bold text-white shadow-md transition hover:bg-brand-600 sm:flex-none sm:min-w-[200px]">
                        {{ __('layanan.profile_cta.open_booking_page') }}
                    </a>
                @endif
            </div>
        </div>
    </div>
</section>
