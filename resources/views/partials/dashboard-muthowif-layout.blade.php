@php
    /** @var \App\Models\MuthowifProfile $mp */
    use App\Enums\BookingStatus;
@endphp

{{--
    Mobile: satu kolom dengan order — hero (1) → ringkasan saldo (2) → bagikan profil (3) → aksi cepat (4) → … → aktivitas (10).
    Desktop (lg): dua kolom; order di-reset agar kiri = konten utama, kanan = sidebar.
--}}
<div class="flex flex-col gap-6 lg:grid lg:grid-cols-12 lg:items-start">
    <div class="contents lg:col-span-8 lg:flex lg:flex-col lg:gap-6">
        {{-- Hero --}}
        <section class="relative order-1 overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-md ring-1 ring-slate-100/80 lg:order-none">
            <div class="pointer-events-none absolute inset-0 z-0" aria-hidden="true">
                <img src="{{ $welcomeHeroBg }}" alt="" class="h-full w-full min-h-[11rem] object-cover object-[80%_35%] sm:min-h-[13rem]" loading="eager" decoding="async" />
            </div>
            <div class="pointer-events-none absolute inset-0 z-[1] bg-gradient-to-r from-welcomeCanvas from-[38%] via-welcomeCanvas/92 via-[55%] to-welcomeCanvas/15" aria-hidden="true"></div>
            <div class="relative z-10 flex flex-col gap-4 p-6 sm:p-8 lg:flex-row lg:items-stretch lg:gap-6">
                <div class="min-w-0 flex-1 lg:max-w-md">
                    <p class="text-xl font-bold leading-snug text-slate-900 sm:text-2xl">
                        {!! __('dashboard_muthowif.hero_hi_html', ['name' => e(Auth::user()->name)]) !!}
                    </p>
                    <p class="mt-2 text-sm text-slate-600">{{ __('dashboard_muthowif.hero_sub') }}</p>
                    @if ($activeBookingsCount > 0)
                        <p class="mt-1 text-sm font-medium text-baytgo">{{ __('dashboard_muthowif.hero_sub_active', ['count' => $activeBookingsCount]) }}</p>
                    @endif
                    <div class="mt-5 flex flex-wrap gap-3">
                        <a href="{{ route('muthowif.bookings.index') }}" class="inline-flex items-center gap-2 rounded-xl bg-baytgo px-5 py-2.5 text-sm font-semibold text-white shadow-md hover:bg-baytgo-800">
                            <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                            {{ __('dashboard_muthowif.hero_btn_bookings') }}
                        </a>
                        <a href="#muthowif-schedule" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white/95 px-5 py-2.5 text-sm font-semibold text-slate-800 shadow-sm hover:bg-white">
                            {{ __('dashboard_muthowif.hero_btn_calendar') }}
                        </a>
                    </div>
                </div>
                @if ($nextBooking)
                    @php
                        $heroStatusPill = match ($nextBooking->status) {
                            BookingStatus::Pending => 'bg-amber-100 text-amber-950',
                            BookingStatus::Confirmed => 'bg-emerald-100 text-emerald-950',
                            default => 'bg-slate-100 text-slate-700',
                        };
                    @endphp
                    <div class="lg:flex lg:w-72 lg:shrink-0 lg:items-end">
                        <div class="w-full rounded-2xl border border-slate-200/90 bg-white/95 p-4 shadow-lg backdrop-blur-sm ring-1 ring-white/80">
                            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">{{ __('dashboard_muthowif.hero_next_up') }}</p>
                            <p class="mt-1 text-lg font-bold text-slate-900">{{ $nextBooking->customer?->name ?? __('dashboard_muthowif.guest') }}</p>
                            <p class="mt-0.5 text-sm text-slate-600">
                                {{ $nextBooking->starts_on?->format('d') }}–{{ $nextBooking->ends_on?->format('d') }} {{ $nextBooking->starts_on?->translatedFormat('M Y') }}
                            </p>
                            <p class="mt-0.5 text-xs text-slate-500">{{ $nextBooking->service_type?->label() }}</p>
                            <div class="mt-3 flex items-center justify-between gap-2">
                                <span class="rounded-full px-2 py-0.5 text-[10px] font-semibold {{ $heroStatusPill }}">{{ $nextBooking->status->label() }}</span>
                                <a href="{{ route('muthowif.bookings.show', $nextBooking) }}" class="text-xs font-semibold text-baytgo hover:text-baytgo-800">{{ __('dashboard_muthowif.view_detail') }} →</a>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </section>

        {{-- Aksi cepat (ikon bulat) --}}
        <section class="order-4 rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm sm:p-5 lg:order-none">
            <h2 class="text-sm font-bold text-slate-900">{{ __('dashboard_muthowif.quick_actions') }}</h2>
            <div class="mt-4 flex gap-3 overflow-x-auto pb-1 sm:gap-4">
                <a href="{{ route('muthowif.bookings.index') }}" class="flex min-w-[4.25rem] flex-col items-center gap-2 text-center">
                    <span class="relative flex h-14 w-14 items-center justify-center rounded-full border border-slate-200 bg-slate-50 text-emerald-700 shadow-sm">
                        <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5a2.25 2.25 0 002.25-2.25m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5a2.25 2.25 0 012.25 2.25v7.5" /></svg>
                        @if ($mp->pending_bookings_count > 0)
                            <span class="absolute -right-0.5 -top-0.5 flex h-5 min-w-[1.25rem] items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-bold text-white">{{ $mp->pending_bookings_count }}</span>
                        @endif
                    </span>
                    <span class="text-xs font-semibold text-slate-700">{{ __('dashboard_muthowif.qa_booking_title') }}</span>
                </a>
                <a href="{{ route('muthowif.jadwal.index') }}" class="flex min-w-[4.25rem] flex-col items-center gap-2 text-center">
                    <span class="flex h-14 w-14 items-center justify-center rounded-full border border-slate-200 bg-slate-50 text-sky-700 shadow-sm">
                        <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </span>
                    <span class="text-xs font-semibold text-slate-700">{{ __('dashboard_muthowif.qa_schedule_title') }}</span>
                </a>
                <a href="{{ route('muthowif.pelayanan.edit') }}" class="flex min-w-[4.25rem] flex-col items-center gap-2 text-center">
                    <span class="flex h-14 w-14 items-center justify-center rounded-full border border-slate-200 bg-slate-50 text-rose-700 shadow-sm">
                        <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21 11.25v8.25a2.25 2.25 0 01-2.25 2.25H5.25a2.25 2.25 0 01-2.25-2.25v-8.25M12 4.875A2.25 2.25 0 1014.25 7.5H9.75A2.25 2.25 0 1012 4.875zM8.25 10.125V7.875a3.375 3.375 0 116.75 0v2.25" /></svg>
                    </span>
                    <span class="text-xs font-semibold text-slate-700">{{ __('nav.services') }}</span>
                </a>
                <a href="{{ route('muthowif.emergency-offers.index') }}" class="flex min-w-[4.25rem] flex-col items-center gap-2 text-center">
                    <span class="relative flex h-14 w-14 items-center justify-center rounded-full border border-slate-200 bg-slate-50 text-orange-700 shadow-sm">
                        <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>
                        @if ($pendingEmergencyOffersCount > 0)
                            <span class="absolute -right-0.5 -top-0.5 flex h-5 min-w-[1.25rem] items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-bold text-white">{{ $pendingEmergencyOffersCount > 9 ? '9+' : $pendingEmergencyOffersCount }}</span>
                        @endif
                    </span>
                    <span class="text-xs font-semibold text-slate-700">{{ __('nav.emergency_offers') }}</span>
                </a>
                <button type="button" @click="$dispatch('open-global-chat-panel')" class="flex min-w-[4.25rem] flex-col items-center gap-2 text-center">
                    <span class="relative flex h-14 w-14 items-center justify-center rounded-full border border-slate-200 bg-slate-50 text-violet-700 shadow-sm">
                        <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0a.375.375 0 11-.75 0 .375.375 0 01.75 0M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z" /></svg>
                        @if ($unreadChatCount > 0)
                            <span class="absolute -right-0.5 -top-0.5 flex h-5 min-w-[1.25rem] items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-bold text-white">{{ $unreadChatCount > 9 ? '9+' : $unreadChatCount }}</span>
                        @endif
                    </span>
                    <span class="text-xs font-semibold text-slate-700">{{ __('dashboard_muthowif.qa_chat_title') }}</span>
                </button>
                <a href="{{ route('muthowif.withdrawals.index') }}" class="flex min-w-[4.25rem] flex-col items-center gap-2 text-center">
                    <span class="flex h-14 w-14 items-center justify-center rounded-full border border-slate-200 bg-slate-50 text-amber-700 shadow-sm">
                        <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.069.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z" /><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd" /></svg>
                    </span>
                    <span class="text-xs font-semibold text-slate-700">{{ __('dashboard_muthowif.qa_wallet_title') }}</span>
                </a>
                <a href="{{ route('support.index') }}" class="flex min-w-[4.25rem] flex-col items-center gap-2 text-center">
                    <span class="flex h-14 w-14 items-center justify-center rounded-full border border-slate-200 bg-slate-50 text-slate-700 shadow-sm">
                        <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9 5.25h.008v.008H12v-.008z" /></svg>
                    </span>
                    <span class="text-xs font-semibold text-slate-700">{{ __('dashboard_muthowif.qa_help_title') }}</span>
                </a>
            </div>
        </section>

        {{-- Pesanan mendatang --}}
        <section id="muthowif-upcoming" class="order-4 scroll-mt-24 space-y-3 lg:order-none">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <h2 class="text-base font-bold text-slate-900">{{ __('dashboard_muthowif.upcoming') }}</h2>
                <a href="{{ route('muthowif.bookings.index') }}" class="text-sm font-semibold text-baytgo hover:text-baytgo-800">{{ __('dashboard_muthowif.view_all') }}</a>
            </div>
            <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm sm:p-5">
                @if ($upcomingBookings->isEmpty())
                    <p class="py-6 text-center text-sm text-slate-600">{{ __('dashboard_muthowif.upcoming_empty_body') }}</p>
                @else
                    <div class="space-y-3">
                        @foreach ($upcomingBookings as $row)
                            @php
                                $statusPill = match ($row->status) {
                                    BookingStatus::Pending => 'bg-amber-100 text-amber-950',
                                    BookingStatus::Confirmed => 'bg-emerald-100 text-emerald-950',
                                    default => 'bg-slate-100 text-slate-700',
                                };
                            @endphp
                            <article class="flex flex-col gap-4 rounded-2xl border border-slate-100 bg-slate-50/50 p-4 sm:flex-row sm:items-center">
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <p class="text-base font-bold text-slate-900">{{ $row->customer?->name ?? __('dashboard_muthowif.guest') }}</p>
                                        <span class="rounded-full px-2 py-0.5 text-[10px] font-semibold {{ $statusPill }}">{{ $row->status->label() }}</span>
                                    </div>
                                    <p class="mt-1 text-sm text-slate-600">
                                        {{ $row->starts_on?->format('d') }}–{{ $row->ends_on?->format('d') }} {{ $row->starts_on?->translatedFormat('M Y') }}
                                        · {{ $row->service_type?->label() }}
                                    </p>
                                </div>
                                <div class="flex shrink-0 gap-2">
                                    <a href="{{ route('muthowif.bookings.show', $row) }}" class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-800 hover:bg-slate-50">{{ __('dashboard_muthowif.view_detail') }}</a>
                                    <button type="button" @click="$dispatch('open-booking-chat', { bookingId: @js($row->getKey()) })" class="inline-flex items-center justify-center rounded-xl bg-baytgo px-4 py-2 text-sm font-semibold text-white hover:bg-baytgo-800">{{ __('dashboard_muthowif.chat') }}</button>
                                </div>
                            </article>
                        @endforeach
                    </div>
                @endif
            </div>
        </section>

        {{-- Jadwal minggu ini --}}
        <section class="order-5 space-y-3 lg:order-none">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <h2 class="text-base font-bold text-slate-900">{{ __('dashboard_muthowif.weekly_schedule') }}</h2>
                <a href="{{ route('muthowif.jadwal.index') }}" class="text-sm font-semibold text-baytgo hover:text-baytgo-800">{{ __('dashboard_muthowif.view_all_schedule') }}</a>
            </div>
            <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm">
                @if ($weeklySchedule->isEmpty())
                    <div class="flex flex-col items-center py-8 text-center">
                        <div class="flex h-20 w-20 items-center justify-center rounded-full bg-slate-50 text-slate-300" aria-hidden="true">
                            <svg class="h-10 w-10" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.25" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5a2.25 2.25 0 002.25-2.25m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5a2.25 2.25 0 012.25 2.25v7.5" /></svg>
                        </div>
                        <p class="mt-4 font-semibold text-slate-900">{{ __('dashboard_muthowif.weekly_empty_title') }}</p>
                        <p class="mt-1 text-sm text-slate-500">{{ __('dashboard_muthowif.weekly_empty_body') }}</p>
                    </div>
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
                                <div class="w-12 shrink-0 text-center">
                                    <p class="text-[10px] font-bold uppercase text-slate-400">{{ $row->starts_on?->translatedFormat('M') }}</p>
                                    <p class="text-lg font-bold text-slate-900">{{ $row->starts_on?->format('d') }}</p>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="font-semibold text-slate-900">{{ $row->customer?->name ?? __('dashboard_muthowif.guest') }}</p>
                                    <p class="text-xs text-slate-500">{{ $row->service_type?->label() }}</p>
                                </div>
                                <span class="rounded-full px-2 py-0.5 text-[10px] font-semibold {{ $statusPill }}">{{ $row->status->label() }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </section>

        {{-- Kalender --}}
        <section id="muthowif-schedule" class="order-6 scroll-mt-24 space-y-3 lg:order-none">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <h2 class="text-base font-bold text-slate-900">{{ __('dashboard_muthowif.section_schedule') }}</h2>
                <a href="{{ route('muthowif.jadwal.index') }}" class="text-sm font-semibold text-baytgo hover:text-baytgo-800">{{ __('dashboard_muthowif.calendar_view_monthly') }}</a>
            </div>
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

    {{-- Sidebar --}}
    <aside class="contents lg:col-span-4 lg:flex lg:flex-col lg:gap-5">
        <div class="order-2 rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm lg:order-none">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('dashboard_muthowif.account_summary') }}</p>
            <a href="{{ route('muthowif.withdrawals.index') }}" class="mt-3 block rounded-2xl bg-gradient-to-br from-baytgo to-baytgo-800 p-5 text-white shadow-md">
                <p class="text-xs font-medium text-white/80">{{ __('dashboard_muthowif.balance_available') }}</p>
                <p class="mt-1 text-2xl font-bold tabular-nums">Rp {{ $balanceFormatted }}</p>
                <p class="mt-2 text-xs font-semibold text-white/90">{{ __('dashboard_muthowif.action_withdraw') }} →</p>
            </a>
            <div class="mt-3 grid grid-cols-2 gap-3">
                <div class="rounded-xl border border-slate-100 bg-slate-50/80 p-3">
                    <p class="text-[10px] font-semibold uppercase text-slate-500">{{ __('dashboard_muthowif.stat_rating_label') }}</p>
                    <p class="mt-1 flex items-center gap-1 text-lg font-bold text-slate-900">
                        {{ $avgRating !== null ? number_format($avgRating, 1, ',', '') : '—' }}
                        <svg class="h-4 w-4 text-amber-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10.868 2.884c-.321-.772-1.415-.772-1.736 0l-1.83 4.401-4.753.381c-.833.067-1.171 1.107-.536 1.651l3.62 3.102-1.106 4.637c-.194.813.691 1.456 1.405 1.02L10 15.591l4.069 2.485c.713.436 1.598-.207 1.404-1.02l-1.106-4.637 3.62-3.102c.635-.544.297-1.584-.536-1.65l-4.752-.382-1.83-4.401z" clip-rule="evenodd" /></svg>
                    </p>
                </div>
                <a href="{{ route('muthowif.bookings.index') }}" class="rounded-xl border border-slate-100 bg-slate-50/80 p-3 transition hover:border-amber-200">
                    <p class="text-[10px] font-semibold uppercase text-slate-500">{{ __('dashboard_muthowif.stat_pending_short') }}</p>
                    <p class="mt-1 text-lg font-bold text-slate-900">{{ $mp->pending_bookings_count }}</p>
                </a>
            </div>
        </div>

        <a href="{{ route('muthowif.portfolio.index') }}" class="order-7 group flex items-center justify-between gap-3 rounded-2xl border border-slate-200/90 bg-white p-4 shadow-sm transition hover:border-sky-300 hover:shadow-md lg:order-none">
            <div class="flex items-center gap-3">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-sky-100 text-sky-700">
                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" /></svg>
                </span>
                <div>
                    <span class="block text-sm font-semibold text-slate-900">{{ __('dashboard_muthowif.portfolio_title') }}</span>
                    <span class="block text-xs text-slate-500">{{ __('dashboard_muthowif.portfolio_sub') }}</span>
                </div>
            </div>
            <svg class="h-5 w-5 text-slate-400 transition group-hover:translate-x-0.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" /></svg>
        </a>

        <div class="order-3 lg:order-none">
            @include('partials.dashboard-muthowif-share', ['mp' => $mp])
        </div>

        <div class="order-9 rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm lg:order-none">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('dashboard_muthowif.profile_account') }}</p>
            <div class="mt-3 flex items-center gap-3">
                <div class="relative h-12 w-12 shrink-0">
                    <img src="{{ route('layanan.photo', $mp) }}" alt="" class="h-full w-full rounded-full object-cover ring-2 ring-slate-100" onerror="this.classList.add('hidden'); this.nextElementSibling.classList.remove('hidden');" />
                    <span class="hidden flex h-full w-full items-center justify-center rounded-full bg-baytgo text-lg font-bold text-white">{{ $userInitial }}</span>
                </div>
                <div class="min-w-0">
                    <p class="truncate font-bold text-slate-900">{{ Auth::user()->name }}</p>
                    <p class="truncate text-xs text-slate-500">{{ Auth::user()->email }}</p>
                </div>
            </div>
            <a href="{{ route('profile.edit') }}" class="mt-4 inline-flex w-full items-center justify-center rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm font-semibold text-slate-800 hover:bg-white">{{ __('dashboard_muthowif.edit_profile') }}</a>
        </div>

        <div class="order-10 rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm lg:order-none">
            <p class="text-sm font-bold text-slate-900">{{ __('dashboard_muthowif.recent_activity') }}</p>
            @if ($recentActivities->isEmpty())
                <p class="mt-3 text-sm text-slate-500">{{ __('dashboard_muthowif.recent_activity_empty') }}</p>
            @else
                <ul class="mt-4 space-y-4">
                    @foreach ($recentActivities as $activity)
                        <li class="flex gap-3">
                            <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full {{ $activity['kind'] === 'chat' ? 'bg-violet-500' : 'bg-emerald-500' }}"></span>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm text-slate-800">{{ $activity['text'] }}</p>
                                @if ($activity['time'])
                                    <p class="mt-0.5 text-xs text-slate-400">{{ $activity['time']->diffForHumans() }}</p>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </aside>
</div>
