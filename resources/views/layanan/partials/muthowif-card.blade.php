@props([
    'card',
    'as' => 'li',
])

@php
    /** @var \App\ViewModels\Layanan\MarketplaceProfileCardData $card */
    $profile = $card->profile;
@endphp

<{{ $as }} class="h-full list-none">
    <article class="group/card relative flex h-full overflow-hidden rounded-xl border border-slate-200/90 bg-white shadow-sm ring-1 ring-slate-100/80 transition duration-300 hover:border-baytgo/25 hover:shadow-md hover:shadow-baytgo/5 sm:flex-col sm:rounded-2xl">
        {{-- Photo: compact thumb on mobile, shorter vertical on desktop --}}
        <div class="relative w-[6.75rem] shrink-0 overflow-hidden bg-slate-200 sm:w-full">
            <div class="relative aspect-square w-full sm:aspect-[5/4]">
                <a href="{{ $card->profileHref }}" class="absolute inset-0 block focus:outline-none focus-visible:ring-2 focus-visible:ring-baytgo focus-visible:ring-inset" tabindex="-1" aria-hidden="true">
                    <img
                        src="{{ $profile->photoUrl() }}"
                        alt=""
                        class="h-full w-full object-cover object-[center_30%] transition duration-500 group-hover/card:scale-[1.02]"
                        loading="lazy"
                        decoding="async"
                        onerror="this.onerror=null; this.src={!! json_encode($card->fallbackSvg) !!}"
                    />
                </a>
            </div>
            <span class="absolute left-1.5 top-1.5 z-10 inline-flex items-center gap-0.5 rounded bg-emerald-600 px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-wide text-white shadow-sm sm:left-2.5 sm:top-2.5 sm:rounded-md sm:px-2 sm:text-[10px]">
                <svg class="h-2.5 w-2.5 sm:h-3 sm:w-3" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" /></svg>
                {{ __('marketplace.card.badge_verified') }}
            </span>
        </div>

        <div class="flex min-w-0 flex-1 flex-col p-2.5 sm:p-3.5">
            <div class="flex items-start justify-between gap-1.5">
                <a href="{{ $card->profileHref }}" class="min-w-0 rounded focus:outline-none focus-visible:ring-2 focus-visible:ring-baytgo">
                    <h2 class="line-clamp-1 text-sm font-bold text-slate-900 transition group-hover/card:text-baytgo sm:text-base">{{ $profile->user->name }}</h2>
                </a>
                @if ($card->avgRating !== null)
                    <span class="inline-flex shrink-0 items-center gap-0.5 text-[11px] font-bold text-amber-700 sm:rounded-full sm:bg-amber-50 sm:px-1.5 sm:py-0.5 sm:text-xs sm:text-amber-950 sm:ring-1 sm:ring-amber-200/80">
                        <svg class="h-3 w-3 text-amber-500 sm:h-3.5 sm:w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                        {{ $card->avgRating }}
                        @if ($card->reviewsCount > 0)
                            <span class="hidden font-semibold text-amber-800/80 sm:inline">{{ __('marketplace.card.reviews_count', ['count' => $card->reviewsCount]) }}</span>
                        @endif
                    </span>
                @endif
            </div>

            <div class="mt-0.5 flex flex-wrap items-center gap-x-2 gap-y-0.5 text-[11px] text-slate-500 sm:mt-1 sm:gap-x-2.5 sm:text-xs">
                @if (filled($card->workLocation))
                    <span class="inline-flex min-w-0 max-w-full items-center gap-0.5">
                        <svg class="h-3 w-3 shrink-0 text-slate-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M9.69 18.933l.003.001C9.89 19.02 10 19 10 19s.11.02.308-.066l.002-.001.006-.003.018-.008a5.741 5.741 0 00.281-.14c.288-.15.715-.369 1.245-.667 1.032-.6 2.405-1.474 3.79-2.65 1.385-1.176 2.618-2.54 3.39-3.96a10.78 10.78 0 002.133-5.85V6.75A2.25 2.25 0 0013.5 4.5h-7A2.25 2.25 0 004.5 6.75v.823c.001 1.812.317 3.569.92 5.176 1.003 2.63 2.79 4.893 4.87 6.174zM10 10.25a2.25 2.25 0 100-4.5 2.25 2.25 0 000 4.5z" clip-rule="evenodd" /></svg>
                        <span class="truncate">{{ $card->workLocation }}</span>
                    </span>
                @endif
                @if ($card->langsLine)
                    <span class="hidden truncate sm:inline">{{ $card->langsLine }}</span>
                @endif
            </div>

            @if ($card->specialization)
                <p class="mt-1 hidden line-clamp-1 text-xs text-slate-600 sm:block">{{ $card->specialization }}</p>
            @endif

            <div class="mt-1.5 flex flex-wrap items-center gap-1 sm:mt-2">
                @if ($card->group)
                    <span class="inline-flex rounded bg-sky-100 px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-wide text-sky-900 sm:rounded-md sm:text-[10px]">{{ __('marketplace.card.badge_group') }}</span>
                @endif
                @if ($card->private)
                    <span class="inline-flex rounded bg-orange-100 px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-wide text-orange-950 sm:rounded-md sm:text-[10px]">{{ __('marketplace.card.badge_private') }}</span>
                @endif
            </div>

            <div class="mt-auto pt-2 sm:pt-3">
                @if ($card->minPrice !== null)
                    <p class="text-xs font-bold text-baytgo sm:text-sm">
                        {{ __('marketplace.card.from') }} Rp {{ $card->minPriceFormatted() }}
                        <span class="text-[10px] font-semibold text-slate-500 sm:text-xs">{{ __('common.per_day') }}</span>
                    </p>
                @else
                    <p class="text-[11px] text-slate-500">{{ __('marketplace.card.price_contact') }}</p>
                @endif

                <div class="mt-2 grid grid-cols-2 gap-1.5 sm:mt-2.5 sm:gap-2">
                    <a href="{{ $card->profileHref }}" class="inline-flex items-center justify-center rounded-lg border border-baytgo/30 bg-white px-2 py-1.5 text-center text-[11px] font-semibold text-baytgo transition hover:border-baytgo hover:bg-welcomeCanvas/60 sm:rounded-xl sm:px-3 sm:py-2 sm:text-xs">
                        {{ __('marketplace.card.view_profile') }}
                    </a>
                    <a href="{{ $card->bookHref }}" class="inline-flex items-center justify-center rounded-lg bg-baytgo px-2 py-1.5 text-center text-[11px] font-semibold text-white shadow-sm shadow-baytgo/20 transition hover:bg-baytgo-800 sm:rounded-xl sm:px-3 sm:py-2 sm:text-xs">
                        {{ __('marketplace.card.quick_book') }}
                    </a>
                </div>
            </div>
        </div>
    </article>
</{{ $as }}>
