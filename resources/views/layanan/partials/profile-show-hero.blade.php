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
    $workLocation = $profile->workLocationLabel();
@endphp

<section class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-lg shadow-slate-900/5 ring-1 ring-slate-100/80">
    <div class="grid lg:grid-cols-[minmax(0,340px)_1fr]">
        <div class="relative aspect-[4/5] min-h-[280px] bg-slate-100 sm:min-h-[320px] lg:aspect-auto lg:min-h-[420px]">
            <img
                src="{{ $profile->photoUrl() }}"
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

        <div class="ui-card-pad-lg flex flex-col justify-center lg:p-10">
            <p class="text-xs font-bold uppercase tracking-widest text-brand-700">{{ __('marketplace.show.kicker') }}</p>
            <h1 class="mt-2 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl lg:text-4xl">{{ $profile->user->name }}</h1>

            @if (filled($workLocation))
                <div class="mt-2 inline-flex w-fit max-w-full flex-col gap-1 rounded-xl bg-sky-50 px-3 py-2 ring-1 ring-sky-200/90">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-sky-700">{{ __('marketplace.card.work_location_label') }}</span>
                    <span class="inline-flex items-center gap-1.5 text-sm font-bold text-sky-950">
                        <svg class="h-4 w-4 shrink-0 text-sky-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M9.69 18.933l.003.001C9.89 19.02 10 19 10 19s.11.02.308-.066l.002-.001.006-.003.018-.008a5.741 5.741 0 00.281-.14c.288-.15.715-.369 1.245-.667 1.032-.6 2.405-1.474 3.79-2.65 1.385-1.176 2.618-2.54 3.39-3.96a10.78 10.78 0 002.133-5.85V6.75A2.25 2.25 0 0013.5 4.5h-7A2.25 2.25 0 004.5 6.75v.823c.001 1.812.317 3.569.92 5.176 1.003 2.63 2.79 4.893 4.87 6.174zM10 10.25a2.25 2.25 0 100-4.5 2.25 2.25 0 000 4.5z" clip-rule="evenodd" /></svg>
                        <span>{{ $workLocation }}</span>
                    </span>
                </div>
            @endif

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
                            <span class="flex h-6 w-6 items-center justify-center rounded-full bg-slate-100 text-slate-600" aria-hidden="true">
                                <svg class="h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0121 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0112 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 013 12c0-1.605.42-3.113 1.157-4.418" /></svg>
                            </span>
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
                    <a href="{{ $bookingPageUrl }}" class="ui-btn-primary flex-1 px-6 text-base sm:flex-none sm:min-w-[200px]">
                        {{ __('marketplace.show.book_now') }}
                    </a>
                @else
                    <a href="{{ $bookingPageUrl }}" class="ui-btn-secondary flex-1 px-6 text-base sm:flex-none sm:min-w-[200px]">
                        {{ __('layanan.profile_cta.open_booking_page') }}
                    </a>
                @endif
            </div>
        </div>
    </div>
</section>
