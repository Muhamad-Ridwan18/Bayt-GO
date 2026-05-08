{{--
  Expects: $profile, $group, $private, $bookingIntent, $startDate, $endDate, $searchRangeLabel
--}}
@php
    /** @var \App\Models\MuthowifProfile $profile */

    $bookQueryParams = array_filter([
        'start_date' => $startDate !== '' ? $startDate : null,
        'end_date' => $endDate !== '' ? $endDate : null,
    ], fn ($v) => filled($v));

    $bookingPageUrl = route('layanan.book', array_merge(
        ['publicProfile' => $profile],
        $bookQueryParams
    ));

    $intent = $bookingIntent;
    $canBook = ($intent['can_submit'] ?? false) && ($group || $private);
    $hasConfiguredServices = (bool) ($group || $private);
@endphp

<section class="relative overflow-hidden rounded-2xl border border-slate-200/90 bg-white p-5 shadow-md ring-1 ring-slate-100/85 sm:p-6" aria-label="{{ __('layanan.profile_cta.section_aria') }}">
    <div class="pointer-events-none absolute -right-8 top-0 h-36 w-36 rounded-full bg-brand-400/15 blur-2xl" aria-hidden="true"></div>
    <div class="relative">
        <h2 class="text-base font-bold text-slate-900 sm:text-lg">{{ __('layanan.profile_cta.title') }}</h2>
        <p class="mt-1 text-sm leading-relaxed text-slate-600">{{ __('layanan.profile_cta.intro') }}</p>

        @if ($canBook)
            <div class="mt-5 flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center">
                <a href="{{ $bookingPageUrl }}" class="inline-flex w-full min-h-[3rem] shrink-0 items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-brand-600 to-brand-700 px-6 py-3.5 text-center text-base font-bold text-white shadow-lg shadow-brand-900/18 transition hover:from-brand-500 hover:to-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 sm:w-auto">
                    {{ __('layanan.profile_cta.go_booking') }}
                    <svg class="h-5 w-5 shrink-0 opacity-95" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M3 10a.75.75 0 01.75-.75h10.638L10.23 5.29a.75.75 0 111.04-1.08l5.5 5.25a.75.75 0 010 1.08l-5.5 5.25a.75.75 0 11-1.04-1.08l4.158-3.96H3.75A.75.75 0 013 10z" clip-rule="evenodd" /></svg>
                </a>
                @if ($searchRangeLabel)
                    <span class="inline-flex items-center gap-2 rounded-xl bg-brand-50 px-3 py-2 text-sm font-semibold text-brand-900 ring-1 ring-brand-200/80">{{ $searchRangeLabel }}</span>
                @endif
            </div>
            <p class="mt-3 text-xs leading-relaxed text-slate-500">{{ __('layanan.profile_cta.hint_wide_form') }}</p>
        @elseif (($intent['reason'] ?? '') === 'guest')
            <div class="mt-5 rounded-xl border border-slate-200 bg-slate-50/90 px-4 py-4 text-sm text-slate-700 ring-1 ring-slate-100/80">
                <p>{{ __('marketplace.panel.guest_intro') }}</p>
                <a href="{{ route('login.intended', ['next' => $bookingPageUrl]) }}" class="mt-4 inline-flex w-full min-h-[2.875rem] items-center justify-center rounded-xl bg-brand-600 px-5 py-3 text-base font-semibold text-white shadow-md hover:bg-brand-700 sm:w-auto">{{ __('marketplace.panel.guest_login') }}</a>
                @if (Route::has('register'))
                    <a href="{{ route('register') }}" class="mt-2 block text-sm font-semibold text-brand-700 hover:text-brand-800">{{ __('marketplace.panel.guest_register') }}</a>
                @endif
            </div>
        @elseif (($intent['reason'] ?? '') === 'not_customer')
            <p class="mt-5 rounded-xl border border-amber-200 bg-amber-50 px-4 py-4 text-sm text-amber-900 ring-1 ring-amber-100/80">
                {!! __('marketplace.panel.not_customer') !!}
            </p>
        @elseif (($intent['reason'] ?? '') === 'missing_dates')
            <div class="mt-5 rounded-xl border border-slate-200 bg-white px-4 py-4 text-sm text-slate-700 shadow-sm ring-1 ring-slate-100/80">
                {!! __('marketplace.panel.missing_dates_html', ['link' => '<a href="'.e(route('layanan.index')).'" class="font-semibold text-brand-700 hover:text-brand-800">'.e(__('layanan.booking_panel_link')).'</a>']) !!}
                <p class="mt-4 text-xs text-slate-500">{{ __('layanan.profile_cta.missing_dates_extra') }}</p>
            </div>
        @elseif (($intent['reason'] ?? '') === 'invalid_dates')
            <p class="mt-5 rounded-xl border border-red-200 bg-red-50 px-4 py-4 text-sm text-red-800 ring-1 ring-red-100/80">{{ __('marketplace.panel.invalid_dates') }}</p>
            <div class="mt-3"><a href="{{ route('layanan.index', ['start_date' => $startDate, 'end_date' => $endDate]) }}" class="text-sm font-semibold text-brand-700 underline decoration-brand-600/35 underline-offset-2 hover:text-brand-800">{{ __('layanan.booking_search_again') }}</a></div>
        @elseif (($intent['reason'] ?? '') === 'past_start')
            <p class="mt-5 rounded-xl border border-red-200 bg-red-50 px-4 py-4 text-sm text-red-800 ring-1 ring-red-100/80">{{ __('marketplace.panel.past_start') }}</p>
        @elseif (($intent['reason'] ?? '') === 'range_too_long')
            <p class="mt-5 rounded-xl border border-red-200 bg-red-50 px-4 py-4 text-sm text-red-800 ring-1 ring-red-100/80">{{ __('marketplace.panel.range_too_long') }}</p>
        @elseif (($intent['reason'] ?? '') === 'slot_unavailable')
            <div class="mt-5 rounded-xl border border-amber-200 bg-amber-50 px-4 py-4 text-sm text-amber-950 ring-1 ring-amber-100/80">
                {!! __('marketplace.panel.slot_unavailable_html', [
                    'range' => $searchRangeLabel ?? '—',
                    'link' => '<a href="'.e(route('layanan.index', array_filter(['start_date' => $startDate, 'end_date' => $endDate !== '' ? $endDate : null]))).'" class="font-semibold underline">'.e(__('layanan.booking_panel_link')).'</a>',
                ]) !!}
            </div>
        @elseif ($intent['can_submit'] && ! $hasConfiguredServices)
            <p class="mt-5 rounded-xl border border-amber-200 bg-amber-50 px-4 py-4 text-sm text-amber-900 ring-1 ring-amber-100/80">{{ __('marketplace.panel.services_unconfigured') }}</p>
        @else
            <p class="mt-5 text-sm text-slate-600">{{ __('layanan.profile_cta.try_again') }}</p>
            <a href="{{ $bookingPageUrl }}" class="mt-3 inline-flex text-sm font-semibold text-brand-700 underline decoration-brand-700/35 underline-offset-2 hover:text-brand-800">{{ __('layanan.profile_cta.open_booking_page') }}</a>
        @endif
    </div>
</section>
