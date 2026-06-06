@php
    use App\Support\IndonesianNumber;

    /** Expects: $profile, $profileUrl, $tripRangeLabel, $canSubmit, $group, $private */
    $prices = collect([$group?->daily_price, $private?->daily_price])->filter();
    $minPrice = $prices->min();
@endphp

<div class="ui-booking-hero">
    <div class="pointer-events-none absolute -right-8 top-0 h-40 w-40 rounded-full bg-brand-300/20 blur-3xl" aria-hidden="true"></div>
    <div class="pointer-events-none absolute -left-6 bottom-0 h-32 w-32 rounded-full bg-amber-200/25 blur-3xl" aria-hidden="true"></div>

    <div class="relative flex flex-col gap-4 sm:flex-row sm:items-center sm:gap-5">
        <div class="relative shrink-0">
            <img
                src="{{ $profile->photoUrl() }}"
                alt=""
                width="88"
                height="88"
                class="h-20 w-20 rounded-2xl object-cover shadow-lg ring-4 ring-white sm:h-[5.5rem] sm:w-[5.5rem] sm:rounded-3xl"
                loading="eager"
            >
            <span class="absolute -bottom-1 -right-1 flex h-7 w-7 items-center justify-center rounded-full bg-emerald-600 text-white shadow-md ring-2 ring-white" aria-hidden="true">
                <svg class="h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" /></svg>
            </span>
        </div>

        <div class="min-w-0 flex-1">
            <p class="text-[11px] font-bold uppercase tracking-wider text-brand-200/90">{{ __('marketplace.panel.checkout') }}</p>
            <h1 class="mt-1 text-xl font-bold tracking-tight text-white sm:text-2xl">{{ __('marketplace.panel.title') }}</h1>
            <p class="mt-1 text-sm text-white/75">{{ $profile->user->name }}</p>
            @if ($tripRangeLabel)
                <p class="mt-2 inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-1 text-xs font-semibold tabular-nums text-white ring-1 ring-white/15">
                    <svg class="h-3.5 w-3.5 text-brand-200" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.75 2a.75.75 0 01.75.75V4h7V2.75a.75.75 0 011.5 0V4h.25A2.75 2.75 0 0118 6.75v8.5A2.75 2.75 0 0115.25 18H4.75A2.75 2.75 0 012 15.25v-8.5A2.75 2.75 0 014.75 4H5V2.75A.75.75 0 015.75 2zm-1 5.5c-.69 0-1.25.56-1.25 1.25v6.5c0 .69.56 1.25 1.25 1.25h10.5c.69 0 1.25-.56 1.25-1.25v-6.5c0-.69-.56-1.25-1.25-1.25H4.75z" clip-rule="evenodd" /></svg>
                    {{ $tripRangeLabel }}
                </p>
            @endif
        </div>

        <div class="flex shrink-0 flex-wrap items-center gap-2 sm:flex-col sm:items-end">
            @if ($canSubmit)
                <x-ui.status-pill type="ready">{{ __('marketplace.panel.jadwal_tersedia') }}</x-ui.status-pill>
            @endif
            @if ($minPrice)
                <p class="rounded-xl bg-white/10 px-3 py-1.5 text-xs font-semibold text-white ring-1 ring-white/15">
                    {{ __('marketplace.panel.from_daily') }}
                    <span class="tabular-nums">Rp {{ IndonesianNumber::formatThousands((string) (int) $minPrice) }}</span>{{ __('marketplace.panel.per_day') }}
                </p>
            @endif
            <a href="{{ $profileUrl }}" class="inline-flex items-center gap-1.5 rounded-xl bg-white/10 px-3 py-2 text-xs font-semibold text-white ring-1 ring-white/15 transition hover:bg-white/20">
                <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M12.79 5.23a.75.75 0 01-.02 1.06L8.832 10l3.938 3.71a.75.75 0 11-1.04 1.08l-4.5-4.25a.75.75 0 010-1.08l4.5-4.25a.75.75 0 011.06.02z" clip-rule="evenodd" /></svg>
                {{ __('layanan.book_back_profile') }}
            </a>
        </div>
    </div>
</div>
