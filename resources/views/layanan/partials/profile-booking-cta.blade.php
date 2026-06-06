{{--
  Expects: $profile, $group, $private, $bookingIntent, $startDate, $endDate, $searchRangeLabel
--}}
@php
    /** @var \App\Models\MuthowifProfile $profile */

    $bookQueryParams = array_filter([
        'start_date' => $startDate !== '' ? $startDate : null,
        'end_date' => $endDate !== '' ? $endDate : null,
        'service_type' => is_string(request()->query('service_type')) && in_array(request()->query('service_type'), ['group', 'private'], true)
            ? request()->query('service_type')
            : null,
        'pilgrim_count' => is_numeric(request()->query('pilgrim_count')) && (int) request()->query('pilgrim_count') > 0
            ? (string) (int) request()->query('pilgrim_count')
            : null,
    ], fn ($v) => filled($v));

    $bookingPageUrl = route('layanan.book', array_merge(
        ['publicProfile' => $profile],
        $bookQueryParams
    ));

    $intent = $bookingIntent;
    $canBook = ($intent['can_submit'] ?? false) && ($group || $private);
    $hasConfiguredServices = (bool) ($group || $private);
    $reason = $intent['reason'] ?? '';
@endphp

<x-ui.card pad="md" class="relative overflow-hidden" aria-label="{{ __('layanan.profile_cta.section_aria') }}">
    <div class="relative">
        <h2 class="text-base font-bold text-slate-900 sm:text-lg">{{ __('layanan.profile_cta.title') }}</h2>
        <p class="mt-1 text-sm leading-relaxed text-slate-600">{{ __('layanan.profile_cta.intro') }}</p>

        @if ($canBook)
            <div class="mt-5 flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center">
                <x-ui.status-pill type="ready">{{ __('marketplace.panel.jadwal_tersedia') }}</x-ui.status-pill>
                @if ($searchRangeLabel)
                    <span class="inline-flex items-center gap-2 rounded-xl bg-brand-50 px-3 py-2 text-sm font-semibold text-brand-900 ring-1 ring-brand-200/80">{{ $searchRangeLabel }}</span>
                @endif
            </div>
            <p class="mt-3 text-xs leading-relaxed text-slate-500">{{ __('layanan.profile_cta.hint_wide_form') }}</p>
            <a href="{{ $bookingPageUrl }}" class="mt-4 inline-flex text-sm font-semibold text-brand-700 underline decoration-brand-600/35 underline-offset-2 hover:text-brand-800">
                {{ __('layanan.profile_cta.go_booking') }} →
            </a>
        @elseif ($reason === 'guest')
            <x-ui.alert type="info" class="mt-5">
                <p>{{ __('marketplace.panel.guest_intro') }}</p>
                <a href="{{ route('login.intended', ['next' => $bookingPageUrl]) }}" class="ui-btn-primary mt-4 w-full sm:w-auto">{{ __('marketplace.panel.guest_login') }}</a>
                @if (Route::has('register'))
                    <a href="{{ route('register') }}" class="mt-2 block text-sm font-semibold text-brand-700 hover:text-brand-800">{{ __('marketplace.panel.guest_register') }}</a>
                @endif
            </x-ui.alert>
        @elseif ($reason === 'not_customer')
            <x-ui.alert type="warning" class="mt-5">
                {!! __('marketplace.panel.not_customer') !!}
            </x-ui.alert>
        @elseif ($reason === 'missing_dates')
            <x-ui.card class="mt-5 border-slate-200/90 p-4 shadow-none ring-0">
                {!! __('marketplace.panel.missing_dates_html', ['link' => '<a href="'.e(route('layanan.index')).'" class="font-semibold text-brand-700 hover:text-brand-800">'.e(__('layanan.booking_panel_link')).'</a>']) !!}
                <p class="mt-4 text-xs text-slate-500">{{ __('layanan.profile_cta.missing_dates_extra') }}</p>
            </x-ui.card>
        @elseif ($reason === 'invalid_dates')
            <x-ui.alert type="error" class="mt-5">{{ __('marketplace.panel.invalid_dates') }}</x-ui.alert>
            <a href="{{ route('layanan.index', ['start_date' => $startDate, 'end_date' => $endDate]) }}" class="mt-3 inline-flex text-sm font-semibold text-brand-700 underline decoration-brand-600/35 underline-offset-2 hover:text-brand-800">{{ __('layanan.booking_search_again') }}</a>
        @elseif ($reason === 'past_start')
            <x-ui.alert type="error" class="mt-5">{{ __('marketplace.panel.past_start') }}</x-ui.alert>
        @elseif ($reason === 'range_too_long')
            <x-ui.alert type="error" class="mt-5">{{ __('marketplace.panel.range_too_long') }}</x-ui.alert>
        @elseif ($reason === 'jadwal_tidak_tersedia')
            <x-ui.alert type="warning" class="mt-5">
                {!! __('marketplace.panel.jadwal_tidak_tersedia_html', [
                    'range' => $searchRangeLabel ?? '—',
                    'link' => '<a href="'.e(route('layanan.index', array_filter(['start_date' => $startDate, 'end_date' => $endDate !== '' ? $endDate : null]))).'" class="font-semibold underline">'.e(__('layanan.booking_panel_link')).'</a>',
                ]) !!}
            </x-ui.alert>
        @elseif ($intent['can_submit'] && ! $hasConfiguredServices)
            <x-ui.alert type="warning" class="mt-5">{{ __('marketplace.panel.services_unconfigured') }}</x-ui.alert>
        @elseif ($reason !== '')
            <p class="mt-5 text-sm text-slate-600">{{ __('layanan.profile_cta.try_again') }}</p>
            <a href="{{ $bookingPageUrl }}" class="mt-3 inline-flex text-sm font-semibold text-brand-700 underline decoration-brand-700/35 underline-offset-2 hover:text-brand-800">{{ __('layanan.profile_cta.open_booking_page') }}</a>
        @endif
    </div>
</x-ui.card>
