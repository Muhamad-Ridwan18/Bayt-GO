@php
    use App\Enums\BookingStatus;
    use App\Enums\MuthowifServiceType;
    use Carbon\Carbon;
    use App\Support\Currency;
    use App\Support\MuthowifFinanceSummary;
    use App\Services\MuthowifDashboardCalendarDataBuilder;

    $mp = Auth::user()->muthowifProfile;
    $mp->loadCount([
        'bookings as pending_bookings_count' => fn ($q) => $q->where('status', BookingStatus::Pending),
        'bookings as confirmed_bookings_count' => fn ($q) => $q->where('status', BookingStatus::Confirmed),
    ]);
    $balance = (float) ($mp->wallet_balance ?? 0);
    $balanceFormatted = \App\Support\Currency::format((float) $balance);

    $monthParam = request()->query('month');
    $calendarData = MuthowifDashboardCalendarDataBuilder::build(
        $mp,
        is_string($monthParam) ? $monthParam : null
    );

    $upcomingBookings = $mp->bookings()
        ->whereIn('status', [BookingStatus::Pending, BookingStatus::Confirmed, BookingStatus::Completed])
        ->whereDate('ends_on', '>=', now()->toDateString())
        ->orderBy('starts_on')
        ->limit(8)
        ->get(['id', 'starts_on', 'ends_on', 'status', 'customer_id', 'service_type']);
    $upcomingBookings->load('customer:id,name');

    $welcomeHeroBg = null;
    foreach (['webp', 'png', 'jpg', 'jpeg'] as $ext) {
        if (file_exists(public_path('images/bg-welcome.'.$ext))) {
            $welcomeHeroBg = asset('images/bg-welcome.'.$ext);
            break;
        }
    }
    if ($welcomeHeroBg === null && is_dir(public_path('images/bg-welcome'))) {
        foreach (array_diff(scandir(public_path('images/bg-welcome')) ?: [], ['.', '..']) as $n) {
            if (preg_match('/\.(jpe?g|png|webp)$/i', $n)) {
                $welcomeHeroBg = asset('images/bg-welcome/'.$n);
                break;
            }
        }
    }
    if ($welcomeHeroBg === null) {
        $welcomeHeroBg = file_exists(public_path('images/welcome-hero.jpg'))
            ? asset('images/welcome-hero.jpg')
            : 'https://images.unsplash.com/photo-1519817914152-22d216bb9170?q=85&w=2160&auto=format&fit=crop';
    }

    $userInitial = mb_strtoupper(mb_substr(Auth::user()->name, 0, 1));
@endphp

<div class="space-y-8 scroll-smooth">
    {{-- Hero: full-bleed seperti welcome â€” bg-welcome lebar penuh; kiri cream polos, foto dominan ke kanan --}}
    <section class="relative left-1/2 w-screen max-w-[100vw] -translate-x-1/2 overflow-hidden bg-welcomeCanvas min-h-[21rem] pb-8 sm:min-h-[24rem] sm:pb-10 lg:min-h-[26rem] lg:pb-12">
        <div class="pointer-events-none absolute inset-0 z-0" aria-hidden="true">
            <img
                src="{{ $welcomeHeroBg }}"
                alt=""
                class="h-full w-full min-h-[22rem] object-cover object-[70%_26%] sm:min-h-[25rem] sm:object-[74%_28%] lg:min-h-[28rem] lg:object-[76%_28%]"
                loading="eager"
                decoding="async"
            />
        </div>
        {{-- Mobile: cream kuat dari atas (teks terbaca), seperti welcome --}}
        <div class="pointer-events-none absolute inset-0 z-[1] bg-gradient-to-b from-welcomeCanvas via-welcomeCanvas/90 to-welcomeCanvas/35 sm:hidden" aria-hidden="true"></div>
        {{-- sm+: kiri lebih polos, transisi lebih lambat ke foto di kanan --}}
        <div class="pointer-events-none absolute inset-0 z-[1] hidden bg-gradient-to-r from-welcomeCanvas from-[38%] via-welcomeCanvas/92 via-[62%] to-welcomeCanvas/5 sm:block lg:from-[42%] lg:via-[66%] lg:to-transparent" aria-hidden="true"></div>
        {{-- Samakan transisi bawah ke area konten --}}
        <div class="pointer-events-none absolute inset-x-0 bottom-0 z-[1] h-20 bg-gradient-to-t from-welcomeCanvas via-welcomeCanvas/60 to-transparent sm:h-24" aria-hidden="true"></div>

        <div class="relative z-10 mx-auto flex max-w-7xl flex-col justify-center px-4 pt-10 sm:px-6 sm:pt-12 lg:px-8 lg:pt-14">
            <div class="flex flex-col gap-5 sm:max-w-xl sm:flex-row sm:items-start sm:gap-6 lg:max-w-2xl">
                <div class="relative h-14 w-14 shrink-0 sm:h-16 sm:w-16">
                    <img
                        src="{{ route('layanan.photo', $mp) }}"
                        alt="{{ __('dashboard_muthowif.photo_alt', ['name' => Auth::user()->name]) }}"
                        class="h-full w-full rounded-full border-2 border-white/90 bg-slate-100 object-cover shadow-lg ring-2 ring-white"
                        onerror="this.classList.add('hidden'); document.getElementById('muthowif-dashboard-avatar-fallback').classList.remove('hidden');"
                    />
                    <span id="muthowif-dashboard-avatar-fallback" class="hidden flex h-full w-full items-center justify-center rounded-full bg-baytgo text-xl font-bold text-white shadow-lg ring-2 ring-white sm:text-2xl" aria-hidden="true">{{ $userInitial }}</span>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-lg leading-snug text-slate-800 md:text-xl">
                        {!! __('dashboard_muthowif.hero_hi_html', ['name' => e(Auth::user()->name)]) !!}
                    </p>
                    <p class="mt-3 max-w-xl text-sm leading-relaxed text-slate-700 md:text-base">
                        {{ __('dashboard_muthowif.hero_sub') }}
                    </p>
                    <div class="mt-6 flex flex-wrap gap-3">
                        <a
                            href="{{ route('muthowif.bookings.index') }}"
                            class="inline-flex items-center justify-center gap-2 rounded-xl bg-baytgo px-5 py-2.5 text-sm font-semibold text-white shadow-md shadow-baytgo/20 transition hover:bg-baytgo-800"
                        >
                            <svg class="h-5 w-5 opacity-95" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5a2.25 2.25 0 002.25-2.25m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5a2.25 2.25 0 012.25 2.25v7.5" />
                            </svg>
                            {{ __('dashboard_muthowif.hero_btn_bookings') }}
                        </a>
                        <a
                            href="#muthowif-schedule"
                            class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200/90 bg-white/90 px-5 py-2.5 text-sm font-semibold text-slate-800 shadow-sm backdrop-blur-sm transition hover:border-baytgo/40 hover:bg-white"
                        >
                            <svg class="h-5 w-5 text-slate-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5a2.25 2.25 0 002.25-2.25m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5a2.25 2.25 0 012.25 2.25v7.5" />
                            </svg>
                            {{ __('dashboard_muthowif.hero_btn_calendar') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @if (filled($mp->referral_code))
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="rounded-2xl border border-emerald-200/80 bg-emerald-50/60 px-4 py-3 text-sm text-emerald-950 shadow-sm ring-1 ring-emerald-100/80 sm:px-5 sm:py-4">
                <p class="font-semibold">{{ __('dashboard_muthowif.referral_code_heading') }}</p>
                <p class="mt-1 font-mono text-base font-bold tracking-wide select-all">{{ $mp->referral_code }}</p>
                <p class="mt-2 text-xs text-emerald-900/80">{{ __('dashboard_muthowif.referral_code_hint') }}</p>
            </div>
        </div>
    @endif

    <div class="grid grid-cols-1 gap-8 lg:grid-cols-12 lg:items-start">
        {{-- Kolom kiri: booking + kalender --}}
        <div class="space-y-8 lg:col-span-8">
            <section id="muthowif-upcoming" class="scroll-mt-24 space-y-3">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <h2 class="text-base font-bold text-slate-900 sm:text-lg">{{ __('dashboard_muthowif.upcoming') }}</h2>
                    <a href="{{ route('muthowif.bookings.index') }}" class="text-sm font-semibold text-baytgo hover:text-baytgo-800">{{ __('dashboard_muthowif.open') }}</a>
                </div>
                <div class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm sm:p-8">
                    @if ($upcomingBookings->isEmpty())
                        <div class="flex flex-col items-center py-8 text-center sm:flex-row sm:items-start sm:text-left sm:py-10">
                            <div class="mb-4 flex h-24 w-24 shrink-0 items-center justify-center rounded-2xl bg-slate-50 sm:mb-0 sm:mr-8 ring-1 ring-slate-100" aria-hidden="true">
                                <svg class="h-14 w-14 text-slate-300" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5a2.25 2.25 0 002.25-2.25m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5a2.25 2.25 0 012.25 2.25v7.5M9 12h6" />
                                </svg>
                            </div>
                            <div class="max-w-md">
                                <p class="text-lg font-bold text-slate-900">{{ __('dashboard_muthowif.upcoming_empty_headline') }}</p>
                                <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ __('dashboard_muthowif.upcoming_empty_body') }}</p>
                                <a
                                    href="{{ route('muthowif.bookings.index') }}"
                                    class="mt-6 inline-flex items-center justify-center rounded-xl bg-baytgo px-5 py-2.5 text-sm font-semibold text-white shadow-md shadow-baytgo/20 transition hover:bg-baytgo-800"
                                >
                                    {{ __('dashboard_muthowif.upcoming_cta') }}
                                </a>
                            </div>
                        </div>
                    @else
                        <ul class="space-y-2 text-sm">
                            @foreach ($upcomingBookings as $row)
                                @php
                                    $statusPill = match ($row->status) {
                                        BookingStatus::Pending => 'bg-amber-100 text-amber-950 ring-amber-200/90',
                                        BookingStatus::Confirmed => 'bg-emerald-100 text-emerald-950 ring-emerald-200/90',
                                        BookingStatus::Completed => 'bg-slate-100 text-slate-800 ring-slate-200/90',
                                        BookingStatus::Cancelled => 'bg-rose-100 text-rose-950 ring-rose-200/90',
                                        default => 'bg-slate-100 text-slate-700 ring-slate-200/80',
                                    };
                                @endphp
                                <li>
                                    <a href="{{ route('muthowif.bookings.show', $row) }}" class="flex flex-col gap-1 rounded-xl border border-slate-200/80 bg-slate-50/50 px-4 py-3 text-left shadow-sm transition hover:border-baytgo/30 hover:bg-white focus:outline-none focus-visible:ring-2 focus-visible:ring-baytgo focus-visible:ring-offset-2">
                                        <div class="flex flex-wrap items-start justify-between gap-1.5">
                                            <p class="min-w-0 text-sm font-semibold text-slate-900">{{ $row->customer?->name ?? __('dashboard_muthowif.guest') }}</p>
                                            <span class="inline-flex shrink-0 rounded-full px-2 py-0.5 text-[10px] font-semibold ring-1 {{ $statusPill }}">{{ $row->status->label() }}</span>
                                        </div>
                                        <p class="text-[11px] text-slate-600">
                                            {{ Carbon::parse($row->starts_on)->format('d/m') }} â€“ {{ Carbon::parse($row->ends_on)->format('d/m') }}
                                            @if ($row->service_type)
                                                <span class="text-slate-400"> Â· </span>{{ $row->service_type->label() }}
                                            @endif
                                        </p>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </section>

            <section id="muthowif-schedule" class="scroll-mt-24 space-y-3">
                <h2 class="text-base font-bold text-slate-900 sm:text-lg">{{ __('dashboard_muthowif.section_schedule') }}</h2>
                <div
                    class="space-y-4"
                    x-data="muthowifDashboardCalendar({
                        url: @js(route('dashboard.muthowif-calendar')),
                        dashboardUrl: @js(route('dashboard')),
                        month: @js($calendarData['calendarMonth']->format('Y-m')),
                    })"
                    @click.capture="onCalendarNavClick($event)"
                >
                    <div :class="{ 'opacity-60 pointer-events-none': loading }">
                        @include('partials.muthowif-calendar-panel', $calendarData)
                    </div>
                    <div :class="{ 'opacity-60 pointer-events-none': loading }">
                        @include('partials.muthowif-blocked-panel', $calendarData)
                    </div>
                </div>
            </section>
        </div>

        {{-- Kolom kanan: aksi cepat + ringkasan --}}
        <aside class="space-y-6 lg:col-span-4">
            <div>
                <h3 class="text-sm font-bold text-slate-900 sm:text-base">{{ __('dashboard_muthowif.quick_actions') }}</h3>
                <p class="mt-1 text-xs text-slate-500">{{ __('dashboard_muthowif.quick_actions_sub') }}</p>
                <div class="mt-4 grid grid-cols-2 gap-3">
                    <a href="{{ route('muthowif.bookings.index') }}" class="group flex flex-col rounded-2xl border border-slate-200/90 bg-white p-4 shadow-sm transition hover:border-emerald-200 hover:shadow-md focus:outline-none focus-visible:ring-2 focus-visible:ring-baytgo">
                        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700 ring-1 ring-emerald-100" aria-hidden="true">
                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5a2.25 2.25 0 002.25-2.25m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5a2.25 2.25 0 012.25 2.25v7.5" /></svg>
                        </span>
                        <span class="mt-3 text-sm font-semibold text-slate-900">{{ __('dashboard_muthowif.qa_booking_title') }}</span>
                        <span class="mt-0.5 text-xs text-slate-500">{{ __('dashboard_muthowif.qa_booking_desc') }}</span>
                    </a>
                    <a href="{{ route('muthowif.jadwal.index') }}" class="group flex flex-col rounded-2xl border border-slate-200/90 bg-white p-4 shadow-sm transition hover:border-sky-200 hover:shadow-md focus:outline-none focus-visible:ring-2 focus-visible:ring-baytgo">
                        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-sky-50 text-sky-700 ring-1 ring-sky-100" aria-hidden="true">
                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </span>
                        <span class="mt-3 text-sm font-semibold text-slate-900">{{ __('dashboard_muthowif.qa_schedule_title') }}</span>
                        <span class="mt-0.5 text-xs text-slate-500">{{ __('dashboard_muthowif.qa_schedule_desc') }}</span>
                    </a>
                    <a href="{{ route('muthowif.withdrawals.index') }}" class="group flex flex-col rounded-2xl border border-slate-200/90 bg-white p-4 shadow-sm transition hover:border-violet-200 hover:shadow-md focus:outline-none focus-visible:ring-2 focus-visible:ring-baytgo">
                        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-violet-50 text-violet-700 ring-1 ring-violet-100" aria-hidden="true">
                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.069.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z" /><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd" /></svg>
                        </span>
                        <span class="mt-3 text-sm font-semibold text-slate-900">{{ __('dashboard_muthowif.qa_wallet_title') }}</span>
                        <span class="mt-0.5 text-xs text-slate-500">{{ __('dashboard_muthowif.qa_wallet_desc') }}</span>
                    </a>
                    <a href="{{ route('support.index') }}" class="group flex flex-col rounded-2xl border border-slate-200/90 bg-white p-4 shadow-sm transition hover:border-amber-200 hover:shadow-md focus:outline-none focus-visible:ring-2 focus-visible:ring-baytgo">
                        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-50 text-amber-800 ring-1 ring-amber-100" aria-hidden="true">
                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" /></svg>
                        </span>
                        <span class="mt-3 text-sm font-semibold text-slate-900">{{ __('dashboard_muthowif.qa_help_title') }}</span>
                        <span class="mt-0.5 text-xs text-slate-500">{{ __('dashboard_muthowif.qa_help_desc') }}</span>
                    </a>
                </div>
            </div>

            <div class="space-y-3">
                <a href="{{ route('muthowif.withdrawals.index') }}" class="flex items-center gap-4 rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm transition hover:border-emerald-200 hover:shadow-md">
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700 ring-1 ring-emerald-100" aria-hidden="true">
                        <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.069.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z" /><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd" /></svg>
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('dashboard_muthowif.wallet_balance') }}</p>
                        <p class="text-lg font-bold tabular-nums text-slate-900">{{ $balanceFormatted }}</p>
                        <p class="mt-1 text-xs font-semibold text-baytgo">{{ __('dashboard_muthowif.action_withdraw') }} â†’</p>
                    </div>
                </a>
                <a href="{{ route('muthowif.bookings.index') }}" class="flex items-center gap-4 rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm transition hover:border-sky-200 hover:shadow-md">
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-sky-50 text-sky-700 ring-1 ring-sky-100" aria-hidden="true">
                        <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd" /></svg>
                    </span>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('dashboard_muthowif.stat_active_label') }}</p>
                        <p class="text-2xl font-bold tabular-nums text-slate-900">{{ $mp->confirmed_bookings_count }}</p>
                    </div>
                </a>
                <a href="{{ route('muthowif.bookings.index') }}" class="flex items-center gap-4 rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm transition hover:border-amber-200 hover:shadow-md">
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-amber-50 text-amber-800 ring-1 ring-amber-100" aria-hidden="true">
                        <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" /></svg>
                    </span>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('dashboard_muthowif.stat_pending_short') }}</p>
                        <p class="text-2xl font-bold tabular-nums text-slate-900">{{ $mp->pending_bookings_count }}</p>
                    </div>
                </a>
            </div>

            <div class="rounded-2xl border border-dashed border-slate-200 bg-slate-50/60 p-4 text-xs text-slate-600">
                <p class="font-semibold text-slate-800">{{ __('dashboard_muthowif.action_public') }}</p>
                <a href="{{ route('layanan.show', $mp) }}" class="mt-2 inline-flex items-center gap-1 font-semibold text-baytgo hover:text-baytgo-800">
                    {{ __('dashboard_muthowif.open_public_profile') }}
                    <svg class="h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M3 10a.75.75 0 01.75-.75h10.638L10.23 5.29a.75.75 0 111.04-1.08l5.5 5.25a.75.75 0 010 1.08l-5.5 5.25a.75.75 0 11-1.04-1.08l4.158-3.96H3.75A.75.75 0 013 10z" clip-rule="evenodd" /></svg>
                </a>
            </div>
        </aside>
    </div>
</div>

