{{-- Expects: $profile, $searchRangeLabel, $bookingIntent --}}
@php
    $intent = $bookingIntent;
    $canAttempt = ($intent['can_submit'] ?? false) === true;
    $blockedReasons = ['guest', 'missing_dates', 'invalid_dates', 'past_start', 'range_too_long', 'jadwal_tidak_tersedia'];
    $isBlocked = in_array($intent['reason'] ?? null, $blockedReasons, true);
@endphp

<aside
    aria-label="{{ __('marketplace.sidebar.aria_label') }}"
    class="overflow-hidden rounded-3xl border border-slate-200/90 bg-white shadow-market ring-1 ring-slate-100/90"
>
    <div class="relative overflow-hidden bg-gradient-to-r from-slate-900 via-brand-900 to-teal-900 px-5 py-4 sm:py-5">
        <span class="absolute right-0 top-0 h-32 w-32 translate-x-6 -translate-y-8 rounded-full bg-white/10 blur-2xl" aria-hidden="true"></span>
        <p class="relative text-[11px] font-bold uppercase tracking-wide text-teal-200/95">{{ __('marketplace.sidebar.kicker') }}</p>
        <h2 class="relative mt-1 text-lg font-bold leading-tight text-white sm:text-xl">{{ __('marketplace.sidebar.title') }}</h2>
    </div>

    <div class="ui-stack-tight ui-card-pad text-sm">
        <div class="flex gap-3">
            <img
                src="{{ $profile->photoUrl() }}"
                alt=""
                width="56"
                height="56"
                class="h-14 w-14 shrink-0 rounded-2xl object-cover shadow-md ring-2 ring-white"
                loading="lazy"
            >
            <div class="min-w-0 pt-0.5">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('marketplace.sidebar.guide_label') }}</p>
                <p class="truncate text-base font-bold text-slate-900">{{ $profile->user->name }}</p>
            </div>
        </div>

        @if ($searchRangeLabel)
            <div class="rounded-2xl border border-slate-100 bg-slate-50/90 px-3.5 py-3 ring-1 ring-slate-100/80">
                <p class="text-[11px] font-bold uppercase tracking-wide text-slate-500">{{ __('marketplace.sidebar.dates_label') }}</p>
                <p class="mt-1 text-sm font-semibold tabular-nums text-slate-900">{{ $searchRangeLabel }}</p>
            </div>
        @endif

        @if ($canAttempt)
            <x-ui.status-pill type="ready" class="w-full justify-center py-1.5">
                {{ __('marketplace.sidebar.status_ready') }}
            </x-ui.status-pill>
            <p class="text-xs leading-relaxed text-slate-600">{{ __('marketplace.sidebar.hint_ready') }}</p>
        @elseif (($intent['reason'] ?? '') === 'guest')
            <x-ui.status-pill type="neutral" class="w-full justify-center py-1.5">
                {{ __('marketplace.sidebar.status_guest') }}
            </x-ui.status-pill>
            <p class="text-xs leading-relaxed text-slate-600">{{ __('marketplace.sidebar.hint_guest') }}</p>
        @elseif (($intent['reason'] ?? '') === 'not_customer')
            <x-ui.status-pill type="guest" class="w-full justify-center py-1.5">
                {{ __('marketplace.sidebar.status_not_customer') }}
            </x-ui.status-pill>
            <p class="text-xs leading-relaxed text-slate-600">{{ __('marketplace.sidebar.hint_not_customer') }}</p>
        @elseif ($isBlocked)
            <x-ui.status-pill type="guest" class="w-full justify-center py-1.5">
                {{ __('marketplace.sidebar.status_adjust') }}
            </x-ui.status-pill>
            <p class="text-xs leading-relaxed text-slate-600">{{ __('marketplace.sidebar.hint_adjust') }}</p>
        @else
            <p class="text-xs leading-relaxed text-slate-600">{{ __('marketplace.sidebar.hint_fallback') }}</p>
        @endif

        <a href="#booking-box" class="ui-btn-primary w-full text-sm">
            <span>{{ __('marketplace.sidebar.cta_scroll') }}</span>
            <svg class="h-4 w-4 shrink-0 opacity-90 lg:hidden" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M10 3a.75.75 0 01.75.75v9.546l2.955-3.084a.75.75 0 111.09 1.03l-4.25 4.442a.75.75 0 01-1.09 0L5.22 11.243a.75.75 0 011.09-1.03l2.955 3.084V3.75A.75.75 0 0110 3z" clip-rule="evenodd" /></svg>
        </a>
    </div>
</aside>
