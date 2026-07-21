@props([
    'card',
    'as' => 'li',
])

@php
    /** @var \App\ViewModels\Layanan\MarketplaceProfileCardData $card */
    $profile = $card->profile;
@endphp

<{{ $as }} class="h-full list-none">
    <article class="group/card relative flex h-full flex-col overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-sm ring-1 ring-slate-100/80 transition duration-300 hover:border-baytgo/25 hover:shadow-lg hover:shadow-baytgo/5">
        <div class="relative aspect-[4/5] overflow-hidden bg-slate-200">
            <a href="{{ $card->profileHref }}" class="block h-full focus:outline-none focus-visible:ring-2 focus-visible:ring-baytgo focus-visible:ring-inset" tabindex="-1" aria-hidden="true">
                <img
                    src="{{ $profile->photoUrl() }}"
                    alt=""
                    class="h-full w-full object-cover object-[center_35%] transition duration-500 group-hover/card:scale-[1.02]"
                    loading="lazy"
                    decoding="async"
                    onerror="this.onerror=null; this.src={!! json_encode($card->fallbackSvg) !!}"
                />
            </a>
            <span class="absolute left-3 top-3 inline-flex items-center gap-1 rounded-md bg-emerald-600 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-white shadow-sm">
                <svg class="h-3 w-3" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" /></svg>
                {{ __('marketplace.card.badge_verified') }}
            </span>
        </div>

        <div class="ui-card-pad-compact flex flex-1 flex-col">
            <div class="flex items-start justify-between gap-2">
                <a href="{{ $card->profileHref }}" class="min-w-0 focus:outline-none focus-visible:ring-2 focus-visible:ring-baytgo rounded">
                    <h2 class="line-clamp-1 text-base font-bold text-slate-900 transition group-hover/card:text-baytgo sm:text-lg">{{ $profile->user->name }}</h2>
                </a>
                @if ($card->avgRating !== null)
                    <span class="inline-flex shrink-0 items-center gap-0.5 rounded-full bg-amber-50 px-2 py-0.5 text-xs font-bold text-amber-950 ring-1 ring-amber-200/80">
                        <svg class="h-3.5 w-3.5 text-amber-500" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                        {{ $card->avgRating }}
                        @if ($card->reviewsCount > 0)
                            <span class="font-semibold text-amber-800/80">{{ __('marketplace.card.reviews_count', ['count' => $card->reviewsCount]) }}</span>
                        @endif
                    </span>
                @endif
            </div>

            @if (filled($card->workLocation))
                <span class="mt-1.5 inline-flex w-fit max-w-full items-center gap-1.5 rounded-full bg-sky-50 px-2.5 py-1 text-[11px] font-bold text-sky-900 ring-1 ring-sky-200/90">
                    <svg class="h-3.5 w-3.5 shrink-0 text-sky-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M9.69 18.933l.003.001C9.89 19.02 10 19 10 19s.11.02.308-.066l.002-.001.006-.003.018-.008a5.741 5.741 0 00.281-.14c.288-.15.715-.369 1.245-.667 1.032-.6 2.405-1.474 3.79-2.65 1.385-1.176 2.618-2.54 3.39-3.96a10.78 10.78 0 002.133-5.85V6.75A2.25 2.25 0 0013.5 4.5h-7A2.25 2.25 0 004.5 6.75v.823c.001 1.812.317 3.569.92 5.176 1.003 2.63 2.79 4.893 4.87 6.174zM10 10.25a2.25 2.25 0 100-4.5 2.25 2.25 0 000 4.5z" clip-rule="evenodd" /></svg>
                    <span class="truncate">{{ $card->workLocation }}</span>
                </span>
            @endif

            @if ($card->experienceLine)
                <p class="mt-1.5 text-xs text-slate-600">{{ $card->experienceLine }}</p>
            @elseif ((int) ($profile->confirmed_bookings_count ?? 0) > 0)
                <p class="mt-1.5 text-xs text-slate-600">{{ __('marketplace.card.bookings_confirmed', ['count' => (int) $profile->confirmed_bookings_count]) }}</p>
            @endif

            @if ($card->langsLine)
                <p class="mt-1 text-xs text-slate-500"><span class="font-medium text-slate-700">{{ __('marketplace.card.languages_prefix') }}</span> {{ $card->langsLine }}</p>
            @endif

            <div class="mt-2.5 flex flex-wrap gap-1.5">
                @if ($card->group)
                    <span class="inline-flex rounded-md bg-sky-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-sky-900">{{ __('marketplace.card.badge_group') }}</span>
                @endif
                @if ($card->private)
                    <span class="inline-flex rounded-md bg-orange-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-orange-950">{{ __('marketplace.card.badge_private') }}</span>
                @endif
            </div>

            @if ($card->specialization)
                <p class="mt-2 line-clamp-2 text-xs leading-relaxed text-slate-600">
                    <span class="font-semibold text-slate-800">{{ __('marketplace.card.specialization_prefix') }}</span> {{ $card->specialization }}
                </p>
            @endif

            <div class="mt-auto pt-4">
                @if ($card->minPrice !== null)
                    <p class="text-sm font-bold text-baytgo">
                        {{ __('marketplace.card.from') }} Rp {{ $card->minPriceFormatted() }}
                        <span class="text-xs font-semibold text-slate-500">{{ __('common.per_day') }}</span>
                    </p>
                @else
                    <p class="text-xs text-slate-500">{{ __('marketplace.card.price_contact') }}</p>
                @endif

                @if (filled($card->rangeLabel))
                    <p class="mt-1.5 flex items-center gap-1.5 text-[11px] text-slate-500">
                        <svg class="h-3.5 w-3.5 shrink-0 text-baytgo" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.75 2a.75.75 0 01.75.75V4h7V2.75a.75.75 0 011.5 0V4h.25A2.75 2.75 0 0118 6.75v8.5A2.75 2.75 0 0115.25 18H4.75A2.75 2.75 0 012 15.25v-8.5A2.75 2.75 0 014.75 4H5V2.75A.75.75 0 015.75 2zm-1 5.5c-.69 0-1.25.56-1.25 1.25v6.5c0 .69.56 1.25 1.25 1.25h10.5c.69 0 1.25-.56 1.25-1.25v-6.5c0-.69-.56-1.25-1.25-1.25H4.75z" clip-rule="evenodd" /></svg>
                        {{ $card->rangeLabel }}
                    </p>
                @endif

                <div class="mt-4 grid grid-cols-2 gap-2">
                    <a href="{{ $card->profileHref }}" class="inline-flex items-center justify-center rounded-xl border border-baytgo/30 bg-white px-3 py-2.5 text-center text-xs font-semibold text-baytgo transition hover:border-baytgo hover:bg-welcomeCanvas/60 sm:text-sm">
                        {{ __('marketplace.card.view_profile') }}
                    </a>
                    <a href="{{ $card->bookHref }}" class="inline-flex items-center justify-center rounded-xl bg-baytgo px-3 py-2.5 text-center text-xs font-semibold text-white shadow-sm shadow-baytgo/20 transition hover:bg-baytgo-800 sm:text-sm">
                        {{ __('marketplace.card.quick_book') }}
                    </a>
                </div>
            </div>
        </div>
    </article>
</{{ $as }}>
