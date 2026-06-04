@php
    use App\Enums\BookingStatus;
    use App\Enums\MuthowifServiceType;
    use App\Models\MuthowifProfile;
    use Carbon\Carbon;
    use App\Support\IndonesianNumber;
    use App\Services\MuthowifDashboardCalendarDataBuilder;

    $mp = MuthowifProfile::query()
        ->whereKey(Auth::user()->muthowifProfile->getKey())
        ->withMarketplaceStats()
        ->withCount([
            'bookings as pending_bookings_count' => fn ($q) => $q->where('status', BookingStatus::Pending),
            'bookings as confirmed_bookings_count' => fn ($q) => $q->where('status', BookingStatus::Confirmed),
        ])
        ->firstOrFail();
    $validServicesCount = $mp->services()
        ->whereNotNull('name')
        ->where('name', '<>', '')
        ->whereNotNull('daily_price')
        ->where('daily_price', '>', 0)
        ->count();
    $hasServices = $validServicesCount > 0;
    $balance = (float) ($mp->wallet_balance ?? 0);
    $balanceFormatted = IndonesianNumber::formatThousands((string) (int) round($balance));

    $monthParam = request()->query('month');
    $calendarData = MuthowifDashboardCalendarDataBuilder::build(
        $mp,
        is_string($monthParam) ? $monthParam : null
    );

    $upcomingBookings = $mp->bookings()
        ->whereIn('status', [BookingStatus::Pending, BookingStatus::Confirmed, BookingStatus::Completed])
        ->whereDate('ends_on', '>=', now()->toDateString())
        ->orderBy('starts_on')
        ->limit(3)
        ->get(['id', 'starts_on', 'ends_on', 'status', 'customer_id', 'service_type', 'pilgrim_count']);
    $upcomingBookings->load('customer:id,name');

    $weekStart = now()->startOfWeek(Carbon::MONDAY);
    $weekEnd = now()->endOfWeek(Carbon::SUNDAY);
    $weeklySchedule = $mp->bookings()
        ->whereIn('status', [BookingStatus::Pending, BookingStatus::Confirmed])
        ->whereDate('starts_on', '<=', $weekEnd->toDateString())
        ->whereDate('ends_on', '>=', $weekStart->toDateString())
        ->orderBy('starts_on')
        ->limit(12)
        ->get(['id', 'starts_on', 'ends_on', 'status', 'customer_id', 'service_type', 'pilgrim_count']);
    $weeklySchedule->load('customer:id,name');

    $avgRating = $mp->average_rating !== null ? round((float) $mp->average_rating, 1) : null;
    $reviewsCount = (int) ($mp->booking_reviews_count ?? 0);

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

<div
    class="space-y-8 scroll-smooth"
    x-data="{ showServicePrompt: {{ $hasServices ? 'false' : 'true' }} }"
    x-init="if (showServicePrompt) { $nextTick(() => { $refs.serviceBtn?.focus(); }); }"
>
    <div
        x-show="showServicePrompt"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 px-4 py-6 sm:px-6"
        role="dialog"
        aria-modal="true"
    >
        <div class="w-full max-w-xl overflow-hidden rounded-[2rem] border border-slate-200 bg-white p-6 shadow-2xl shadow-slate-900/10">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-[0.2em] text-baytgo">Aksi penting</p>
                    <h2 class="mt-3 text-2xl font-bold text-slate-900">Lengkapi layanan muthowif Anda</h2>
                    <p class="mt-3 text-sm leading-relaxed text-slate-600">Karena Anda belum menambahkan layanan, kami sarankan untuk langsung atur layanan agar profil Anda siap menerima permintaan booking.</p>
                </div>
                <button
                    type="button"
                    class="rounded-full border border-slate-200 bg-slate-100 p-2 text-slate-600 transition hover:bg-slate-200"
                    @click="showServicePrompt = false"
                    aria-label="Tutup pemberitahuan"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M4.28 4.28a.75.75 0 011.06 0L10 8.94l4.66-4.66a.75.75 0 111.06 1.06L11.06 10l4.66 4.66a.75.75 0 11-1.06 1.06L10 11.06l-4.66 4.66a.75.75 0 01-1.06-1.06L8.94 10 4.28 5.34a.75.75 0 010-1.06z" clip-rule="evenodd" />
                    </svg>
                </button>
            </div>
            <div class="mt-6 grid gap-4 sm:grid-cols-[1fr_auto] sm:items-center">
                <div class="space-y-2">
                    <p class="text-sm text-slate-700">Tambahkan layanan harian Anda sekarang sehingga daftar layanan bisa tampil ke jamaah dan muthowif bisa mulai menerima booking.</p>
                    <p class="text-xs text-slate-500">Anda bisa mengatur layanan group dan private secara terpisah di halaman layanan.</p>
                </div>
                <a
                    href="{{ route('muthowif.pelayanan.edit') }}"
                    x-ref="serviceBtn"
                    class="inline-flex items-center justify-center rounded-2xl bg-baytgo px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-baytgo/15 transition hover:bg-baytgo-800"
                >
                    Atur layanan sekarang
                </a>
            </div>
        </div>
    </div>
    {{-- Hero --}}
    <section class="relative overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-md shadow-slate-900/5 ring-1 ring-slate-100/80">
        <div class="pointer-events-none absolute inset-0 z-0" aria-hidden="true">
            <img src="{{ $welcomeHeroBg }}" alt="" class="h-full w-full min-h-[12rem] object-cover object-[72%_30%] sm:min-h-[14rem]" loading="eager" decoding="async" />
        </div>
        <div class="pointer-events-none absolute inset-0 z-[1] bg-gradient-to-r from-welcomeCanvas from-[42%] via-welcomeCanvas/95 via-[58%] to-welcomeCanvas/10" aria-hidden="true"></div>
        <div class="relative z-10 px-6 py-8 sm:px-8 sm:py-10 lg:max-w-xl">
            <p class="text-xl font-bold leading-snug text-slate-900 sm:text-2xl">
                {!! __('dashboard_muthowif.hero_hi_html', ['name' => e(Auth::user()->name)]) !!}
            </p>
            <p class="mt-2 text-sm leading-relaxed text-slate-700">{{ __('dashboard_muthowif.hero_sub') }}</p>
            <div class="mt-5 flex flex-wrap gap-3">
                <a href="{{ route('muthowif.bookings.index') }}" class="inline-flex items-center gap-2 rounded-xl bg-baytgo px-5 py-2.5 text-sm font-semibold text-white shadow-md hover:bg-baytgo-800">
                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5a2.25 2.25 0 002.25-2.25m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5a2.25 2.25 0 012.25 2.25v7.5" /></svg>
                    {{ __('dashboard_muthowif.hero_btn_bookings') }}
                </a>
                <a href="#muthowif-schedule" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white/95 px-5 py-2.5 text-sm font-semibold text-slate-800 shadow-sm hover:bg-white">
                    <svg class="h-5 w-5 text-slate-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5a2.25 2.25 0 002.25-2.25m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5a2.25 2.25 0 012.25 2.25v7.5" /></svg>
                    {{ __('dashboard_muthowif.hero_btn_calendar') }}
                </a>
            </div>
        </div>
    </section>

    {{-- Statistik --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <a href="{{ route('muthowif.withdrawals.index') }}" class="flex items-start gap-4 rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm transition hover:border-emerald-200 hover:shadow-md">
            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700 ring-1 ring-emerald-100">
                <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.069.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z" /><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd" /></svg>
            </span>
            <div class="min-w-0">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('dashboard_muthowif.wallet_balance') }}</p>
                <p class="mt-1 text-2xl font-bold tabular-nums text-slate-900">Rp {{ $balanceFormatted }}</p>
                <p class="mt-1 text-xs font-semibold text-baytgo">{{ __('dashboard_muthowif.action_withdraw') }} →</p>
            </div>
        </a>
        <a href="{{ route('muthowif.bookings.index') }}" class="flex items-start gap-4 rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm transition hover:border-sky-200 hover:shadow-md">
            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-sky-50 text-sky-700 ring-1 ring-sky-100">
                <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd" /></svg>
            </span>
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('dashboard_muthowif.stat_active_label') }}</p>
                <p class="mt-1 text-2xl font-bold tabular-nums text-slate-900">{{ $mp->confirmed_bookings_count }}</p>
                <p class="mt-1 text-xs text-slate-500">{{ __('dashboard_muthowif.stat_active_caption') }}</p>
            </div>
        </a>
        <a href="{{ route('muthowif.bookings.index') }}" class="flex items-start gap-4 rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm transition hover:border-amber-200 hover:shadow-md">
            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-amber-50 text-amber-800 ring-1 ring-amber-100">
                <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" /></svg>
            </span>
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('dashboard_muthowif.stat_pending_short') }}</p>
                <p class="mt-1 text-2xl font-bold tabular-nums text-slate-900">{{ $mp->pending_bookings_count }}</p>
                <p class="mt-1 text-xs text-slate-500">{{ __('dashboard_muthowif.stat_pending_caption') }}</p>
            </div>
        </a>
        <div class="flex items-start gap-4 rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm">
            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-amber-50 text-amber-600 ring-1 ring-amber-100">
                <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10.868 2.884c-.321-.772-1.415-.772-1.736 0l-1.83 4.401-4.753.381c-.833.067-1.171 1.107-.536 1.651l3.62 3.102-1.106 4.637c-.194.813.691 1.456 1.405 1.02L10 15.591l4.069 2.485c.713.436 1.598-.207 1.404-1.02l-1.106-4.637 3.62-3.102c.635-.544.297-1.584-.536-1.65l-4.752-.382-1.83-4.401z" clip-rule="evenodd" /></svg>
            </span>
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('dashboard_muthowif.stat_rating_label') }}</p>
                <p class="mt-1 text-2xl font-bold tabular-nums text-slate-900">{{ $avgRating !== null ? number_format($avgRating, 1, ',', '') : '—' }}</p>
                <p class="mt-1 text-xs text-slate-500">
                    @if ($reviewsCount > 0)
                        {{ __('dashboard_muthowif.stat_rating_reviews', ['count' => $reviewsCount]) }}
                    @else
                        {{ __('dashboard_muthowif.stat_rating_empty') }}
                    @endif
                </p>
            </div>
        </div>
    </div>

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
                        <div class="space-y-3">
                            @foreach ($upcomingBookings as $row)
                                @php
                                    $statusPill = match ($row->status) {
                                        BookingStatus::Pending => 'bg-amber-100 text-amber-950 ring-amber-200/90',
                                        BookingStatus::Confirmed => 'bg-emerald-100 text-emerald-950 ring-emerald-200/90',
                                        BookingStatus::Completed => 'bg-slate-100 text-slate-800 ring-slate-200/90',
                                        BookingStatus::Cancelled => 'bg-rose-100 text-rose-950 ring-rose-200/90',
                                        default => 'bg-slate-100 text-slate-700 ring-slate-200/80',
                                    };
                                    $nights = $row->billingNightsInclusive();
                                @endphp
                                <article class="rounded-2xl border border-slate-200/80 bg-slate-50/40 p-4 sm:p-5">
                                    <div class="flex flex-wrap items-start justify-between gap-2">
                                        <div>
                                            <p class="text-base font-bold text-slate-900">{{ $row->customer?->name ?? __('dashboard_muthowif.guest') }}</p>
                                            <p class="mt-1 text-sm text-slate-600">
                                                {{ $row->starts_on?->format('d M Y') }} – {{ $row->ends_on?->format('d M Y') }}
                                                <span class="text-slate-400">·</span> {{ $nights }} {{ __('common.days') }}
                                            </p>
                                            <p class="mt-0.5 text-sm text-slate-600">
                                                {{ $row->service_type?->label() }}
                                                · {{ __('muthowif.bookings.pilgrim_count', ['count' => $row->pilgrim_count]) }}
                                            </p>
                                        </div>
                                        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ring-1 {{ $statusPill }}">{{ $row->status->label() }}</span>
                                    </div>
                                    <div class="mt-4 flex flex-wrap gap-2">
                                        <a href="{{ route('muthowif.bookings.show', $row) }}" class="inline-flex flex-1 items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-800 hover:bg-slate-50 sm:flex-none sm:min-w-[8rem]">
                                            {{ __('dashboard_muthowif.view_detail') }}
                                        </a>
                                        <button type="button" @click="$dispatch('open-booking-chat', { bookingId: @js($row->getKey()) })" class="inline-flex flex-1 items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-800 hover:bg-slate-50 sm:flex-none sm:min-w-[6rem]">
                                            {{ __('dashboard_muthowif.chat') }}
                                        </button>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    @endif
                </div>
            </section>

            <section class="space-y-3">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <h2 class="text-base font-bold text-slate-900 sm:text-lg">{{ __('dashboard_muthowif.weekly_schedule') }}</h2>
                    <a href="#muthowif-schedule" class="text-sm font-semibold text-baytgo hover:text-baytgo-800">{{ __('dashboard_muthowif.view_all_schedule') }}</a>
                </div>
                <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm sm:p-5">
                    @if ($weeklySchedule->isEmpty())
                        <p class="text-sm text-slate-600">{{ __('dashboard_muthowif.weekly_schedule_empty') }}</p>
                    @else
                        <ul class="divide-y divide-slate-100">
                            @foreach ($weeklySchedule as $row)
                                @php
                                    $statusPill = match ($row->status) {
                                        BookingStatus::Pending => 'bg-amber-100 text-amber-950',
                                        BookingStatus::Confirmed => 'bg-emerald-100 text-emerald-950',
                                        default => 'bg-slate-100 text-slate-700',
                                    };
                                @endphp
                                <li class="flex flex-wrap items-center gap-3 py-3 first:pt-0 last:pb-0">
                                    <div class="w-14 shrink-0 text-center">
                                        <p class="text-[10px] font-bold uppercase text-slate-500">{{ $row->starts_on?->translatedFormat('M') }}</p>
                                        <p class="text-xl font-bold text-slate-900">{{ $row->starts_on?->format('d') }}</p>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="font-semibold text-slate-900">{{ $row->customer?->name ?? __('dashboard_muthowif.guest') }}</p>
                                        <p class="text-xs text-slate-600">{{ $row->service_type?->label() }} · {{ $row->starts_on?->format('d/m') }}–{{ $row->ends_on?->format('d/m') }}</p>
                                    </div>
                                    <span class="rounded-full px-2 py-0.5 text-[10px] font-semibold {{ $statusPill }}">{{ $row->status->label() }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </section>

            <section id="muthowif-schedule" class="scroll-mt-24 space-y-3">
                <h2 class="text-base font-bold text-slate-900 sm:text-lg">{{ __('dashboard_muthowif.calendar_title', ['month' => $calendarData['calendarMonth']->translatedFormat('F Y')]) }}</h2>
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

            @include('partials.dashboard-muthowif-share', ['mp' => $mp])
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
                <a href="{{ route('muthowif.portfolio.index') }}" class="mt-3 group flex items-center justify-between gap-3 rounded-2xl border border-slate-200/90 bg-gradient-to-r from-sky-50 to-blue-50/50 p-4 shadow-sm transition hover:border-sky-300 hover:shadow-md focus:outline-none focus-visible:ring-2 focus-visible:ring-sky-500">
                    <div class="flex items-center gap-3">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-sky-100 text-sky-700 ring-1 ring-sky-200" aria-hidden="true">
                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" /></svg>
                        </span>
                        <div class="text-left">
                            <span class="block text-sm font-semibold text-slate-900">{{ __('dashboard_muthowif.portfolio_title') }}</span>
                            <span class="block text-[11px] text-slate-500">{{ __('dashboard_muthowif.portfolio_sub') }}</span>
                        </div>
                    </div>
                    <svg class="h-5 w-5 text-slate-400 transition-transform group-hover:translate-x-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" /></svg>
                </a>
            </div>

            <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('dashboard_muthowif.profile_account') }}</p>
                <div class="mt-3 flex items-center gap-3">
                    <div class="relative h-12 w-12 shrink-0">
                        <img
                            src="{{ route('layanan.photo', $mp) }}"
                            alt=""
                            class="h-full w-full rounded-full object-cover ring-2 ring-slate-100"
                            onerror="this.classList.add('hidden'); this.nextElementSibling.classList.remove('hidden');"
                        />
                        <span class="hidden flex h-full w-full items-center justify-center rounded-full bg-baytgo text-lg font-bold text-white">{{ $userInitial }}</span>
                    </div>
                    <div class="min-w-0">
                        <p class="truncate font-bold text-slate-900">{{ Auth::user()->name }}</p>
                        <p class="truncate text-xs text-slate-500">{{ Auth::user()->email }}</p>
                    </div>
                </div>
                <a href="{{ route('profile.edit') }}" class="mt-4 inline-flex w-full items-center justify-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm font-semibold text-slate-800 transition hover:bg-white">
                    <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 010 .255c-.008.378.137.75.43.991l1.003.827c.424.35.534.955.26 1.431l-1.296 2.247a1.125 1.125 0 01-1.37.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.37-.49l-1.296-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 010-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 01-.26-1.431l1.296-2.247a1.125 1.125 0 011.37-.491l1.217.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                    {{ __('dashboard_muthowif.profile_settings') }}
                </a>
                <a href="{{ route('layanan.show', $mp) }}" class="mt-3 block text-center text-xs font-semibold text-baytgo hover:text-baytgo-800">
                    {{ __('dashboard_muthowif.open_public_profile') }} →
                </a>
            </div>
        </aside>
    </div>
</div>
