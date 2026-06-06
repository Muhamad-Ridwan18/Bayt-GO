@php
    /** Expects: $profile, $tripRangeLabel, $canSubmit, Alpine bindings via parent scope */
    $reviewsCount = (int) ($profile->booking_reviews_count ?? 0);
    $avgRating = $profile->booking_reviews_avg_rating !== null
        ? number_format((float) $profile->booking_reviews_avg_rating, 1)
        : null;
@endphp

<aside class="ui-booking-aside" aria-label="{{ __('marketplace.sidebar.aria_label') }}">
    <div class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-lg shadow-slate-900/5 ring-1 ring-slate-100/90">
        <div class="ui-booking-summary-head px-5 py-4">
            <h2 class="text-sm font-bold text-white">{{ __('marketplace.sidebar.summary_title') }}</h2>
        </div>

        <div class="border-b border-slate-100 px-5 py-4">
            <div class="flex items-center gap-3">
                <img
                    src="{{ $profile->photoUrl() }}"
                    alt=""
                    class="h-12 w-12 shrink-0 rounded-full object-cover ring-2 ring-white shadow-md"
                    loading="lazy"
                >
                <div class="min-w-0">
                    <p class="truncate text-sm font-bold text-slate-900">{{ $profile->user->name }}</p>
                    @if ($reviewsCount > 0 && $avgRating !== null)
                        <p class="mt-0.5 flex items-center gap-1 text-xs text-slate-600">
                            <svg class="h-3.5 w-3.5 text-amber-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M10.868 2.884c-.321-.772-1.415-.772-1.736 0l-1.83 4.401-4.753.381c-.833.067-1.171 1.107-.536 1.651l3.62 3.102-1.106 4.637c-.194.813.691 1.456 1.405 1.02L10 15.591l4.069 2.485c.713.436 1.598-.207 1.404-1.02l-1.106-4.637 3.62-3.102c.635-.544.297-1.584-.536-1.65l-4.752-.382-1.831-4.401z" clip-rule="evenodd" /></svg>
                            <span class="font-semibold text-slate-800">{{ $avgRating }}</span>
                            <span class="text-slate-500">({{ $reviewsCount }} review)</span>
                        </p>
                    @else
                        <p class="mt-0.5 text-xs text-slate-500">{{ __('marketplace.sidebar.guide_label') }}</p>
                    @endif
                </div>
            </div>
        </div>

        <ul class="divide-y divide-slate-100 px-5 py-1 text-sm">
            @if ($tripRangeLabel)
                <li class="flex items-start gap-3 py-3.5">
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-brand-50 text-brand-700" aria-hidden="true">
                        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.75 2a.75.75 0 01.75.75V4h7V2.75a.75.75 0 011.5 0V4h.25A2.75 2.75 0 0118 6.75v8.5A2.75 2.75 0 0115.25 18H4.75A2.75 2.75 0 012 15.25v-8.5A2.75 2.75 0 014.75 4H5V2.75A.75.75 0 015.75 2zm-1 5.5c-.69 0-1.25.56-1.25 1.25v6.5c0 .69.56 1.25 1.25 1.25h10.5c.69 0 1.25-.56 1.25-1.25v-6.5c0-.69-.56-1.25-1.25-1.25H4.75z" clip-rule="evenodd" /></svg>
                    </span>
                    <div class="min-w-0">
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">{{ __('marketplace.sidebar.travel_period') }}</p>
                        <p class="mt-0.5 font-semibold tabular-nums text-slate-900">{{ $tripRangeLabel }}</p>
                    </div>
                </li>
            @endif
            <li class="flex items-start gap-3 py-3.5">
                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-brand-50 text-brand-700" aria-hidden="true">
                    <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path d="M10 3.75a2 2 0 10-4 0 2 2 0 004 0zM17.25 4.5a.75.75 0 000-1.5h-3.5a.75.75 0 000 1.5h3.5zM5.25 4.5a.75.75 0 000-1.5h-3.5a.75.75 0 000 1.5h3.5zM10 8.25a2 2 0 10-4 0 2 2 0 004 0zM17.25 9a.75.75 0 000-1.5h-3.5a.75.75 0 000 1.5h3.5zM5.25 9a.75.75 0 000-1.5h-3.5a.75.75 0 000 1.5h3.5zM10 12.75a2 2 0 10-4 0 2 2 0 004 0zM17.25 13.5a.75.75 0 000-1.5h-3.5a.75.75 0 000 1.5h3.5zM5.25 13.5a.75.75 0 000-1.5h-3.5a.75.75 0 000 1.5h3.5z" /></svg>
                </span>
                <div class="min-w-0">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">{{ __('marketplace.sidebar.package_label') }}</p>
                    <p class="mt-0.5 font-semibold text-slate-900" x-text="summary.serviceLabelDisplay"></p>
                </div>
            </li>
            <li class="flex items-start gap-3 py-3.5">
                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-brand-50 text-brand-700" aria-hidden="true">
                    <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path d="M10 9a3 3 0 100-6 3 3 0 000 6zM6 8a2 2 0 11-4 0 2 2 0 014 0zM1.49 15.326a.78.78 0 01-.358-.442 3 3 0 014.308-3.516 6.803 6.803 0 016.306 0 3 3 0 014.308 3.516.78.78 0 01-.358.442C13.732 16.08 11.962 16.5 10 16.5s-3.732-.42-5.51-1.174zM16.5 8.25a2.25 2.25 0 100-4.5 2.25 2.25 0 000 4.5z" /></svg>
                </span>
                <div class="min-w-0">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">{{ __('marketplace.sidebar.total_pilgrims') }}</p>
                    <p class="mt-0.5 font-semibold text-slate-900">
                        <span x-text="summary.pilgrimCount"></span> {{ __('common.people') }}
                    </p>
                </div>
            </li>
        </ul>

        @if ($canSubmit)
            <div class="border-t border-slate-100 px-5 py-4">
                <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">{{ __('marketplace.sidebar.slot_status') }}</p>
                <p class="mt-1.5 inline-flex items-center gap-1.5 text-sm font-bold text-emerald-700">
                    <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" /></svg>
                    {{ __('marketplace.panel.jadwal_tersedia') }}
                </p>
            </div>
        @endif
    </div>

    <div class="mt-4 flex items-start gap-3 rounded-2xl border border-brand-100 bg-brand-50/60 px-4 py-3.5 ring-1 ring-brand-100/80">
        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-white text-brand-700 shadow-sm" aria-hidden="true">
            <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 1a4.5 4.5 0 00-4.5 4.5V9H5a2 2 0 00-2 2v6a2 2 0 002 2h10a2 2 0 002-2v-6a2 2 0 00-2-2h-.5V5.5A4.5 4.5 0 0010 1zm3 8V5.5a3 3 0 10-6 0V9h6z" clip-rule="evenodd" /></svg>
        </span>
        <div>
            <p class="text-sm font-bold text-brand-900">{{ __('marketplace.sidebar.security_title') }}</p>
            <p class="mt-0.5 text-xs leading-relaxed text-brand-800/80">{{ __('marketplace.sidebar.security_desc') }}</p>
        </div>
    </div>
</aside>
