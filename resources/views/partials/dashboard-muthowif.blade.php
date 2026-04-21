@php
    use App\Enums\BookingStatus;
    use App\Enums\MuthowifServiceType;
    use Carbon\Carbon;
    use App\Support\IndonesianNumber;
    use App\Support\MuthowifFinanceSummary;
    use App\Services\MuthowifDashboardCalendarDataBuilder;

    $mp = Auth::user()->muthowifProfile;
    $mp->loadCount([
        'bookings as pending_bookings_count' => fn ($q) => $q->where('status', BookingStatus::Pending),
        'bookings as confirmed_bookings_count' => fn ($q) => $q->where('status', BookingStatus::Confirmed),
    ]);
    $balance = (float) ($mp->wallet_balance ?? 0);
    $balanceFormatted = IndonesianNumber::formatThousands((string) (int) round($balance));
    $grossVolume = MuthowifFinanceSummary::grossVolumeExcludingRefundedBookings($mp);
    $grossVolumeFormatted = IndonesianNumber::formatThousands((string) $grossVolume);

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
@endphp

<div class="space-y-4 md:space-y-5 scroll-smooth">
    {{-- Hero ringkas --}}
    <section class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-slate-900 via-emerald-950 to-brand-900 text-white shadow-lg shadow-emerald-900/20 ring-1 ring-white/10 sm:rounded-3xl">
        <div class="absolute inset-0 bg-[url('data:image/svg+xml,%3Csvg width=\'60\' height=\'60\' viewBox=\'0 0 60 60\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'none\' fill-rule=\'evenodd\'%3E%3Cg fill=\'%23ffffff\' fill-opacity=\'0.05\'%3E%3Cpath d=\'M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E')] opacity-40"></div>
        <div class="pointer-events-none absolute -right-16 top-0 h-80 w-80 rounded-full bg-emerald-400/20 blur-3xl"></div>
        <div class="pointer-events-none absolute bottom-0 left-1/4 h-72 w-96 rounded-full bg-brand-400/15 blur-3xl"></div>

        <div class="relative space-y-4 px-4 py-5 sm:px-6 sm:py-6 lg:px-8">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between lg:items-start">
                <div class="min-w-0 max-w-3xl flex-1 space-y-2.5">
                    <div class="inline-flex items-center gap-2 rounded-full bg-white/10 px-2.5 py-0.5 text-[10px] font-semibold uppercase tracking-wider text-emerald-100/95 ring-1 ring-white/15">
                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-400" aria-hidden="true"></span>
                        {{ __('dashboard.muthowif_panel_label') }}
                    </div>
                    <div>
                        <p class="text-xs font-medium text-emerald-100/90">{{ __('dashboard.hello') }}</p>
                        <p class="text-2xl font-bold tracking-tight text-white sm:text-3xl">{{ Auth::user()->name }}</p>
                        <span class="mt-2 inline-flex items-center gap-1 rounded-full bg-white/10 px-2.5 py-0.5 text-[11px] font-semibold text-emerald-50 ring-1 ring-white/20">
                            <svg class="h-3 w-3 text-emerald-300" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                            </svg>
                            {{ __('dashboard_muthowif.verified_badge') }}
                        </span>
                    </div>
                    <p class="max-w-xl text-xs leading-snug text-emerald-100/85 sm:text-sm">
                        {{ __('dashboard.muthowif_dashboard_subtitle') }}
                    </p>
                </div>

                <div class="mx-auto shrink-0 sm:mx-0">
                    <div class="relative">
                        <div class="absolute -inset-0.5 rounded-2xl bg-gradient-to-br from-white/25 to-emerald-400/20 blur-sm"></div>
                        <img
                            src="{{ route('layanan.photo', $mp) }}"
                            alt="{{ __('dashboard_muthowif.photo_alt', ['name' => Auth::user()->name]) }}"
                            class="relative h-24 w-24 rounded-2xl border-2 border-white/40 bg-white/10 object-cover shadow-lg ring-2 ring-white/20 sm:h-28 sm:w-28"
                            onerror="this.onerror=null; this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22128%22 height=%22128%22%3E%3Crect fill=%22%230f172a%22 width=%22128%22 height=%22128%22/%3E%3Ctext x=%2250%25%22 y=%2255%25%22 dominant-baseline=%22middle%22 text-anchor=%22middle%22 font-size=%2248%22 fill=%22%23ffffff%22%3E{{ mb_substr(Auth::user()->name, 0, 1) }}%3C/text%3E%3C/svg%3E'"
                        >
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-2 sm:grid-cols-3 sm:gap-2.5">
                <div class="group relative overflow-hidden rounded-xl border border-white/15 bg-white/[0.07] p-3 text-sm shadow-inner backdrop-blur-sm transition hover:bg-white/[0.1]">
                    <span class="absolute right-3 top-3 flex h-8 w-8 items-center justify-center rounded-lg bg-white/10 text-emerald-100 ring-1 ring-white/15" aria-hidden="true">
                        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM4.332 8.027a6.012 6.012 0 011.912-2.706C6.512 5.73 6.613 6.369 6.74 7c.079.424.15 1.095-.232 1.752-.379.657-.887.907-1.348.967-.46.06-.97-.013-1.463-.18zm6.65 6.653a6.012 6.012 0 002.706-1.912c-.326.091-.65.182-.973.273-.65.182-1.3.364-1.95.546-.65.182-1.3.364-1.95.546-.65.182-1.3.364-1.95.546-.65.182-1.3.364-1.95.546z" clip-rule="evenodd" /></svg>
                    </span>
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-emerald-100/80">{{ __('dashboard_muthowif.label_languages') }}</p>
                    <p class="mt-1.5 pr-5 text-xs leading-snug text-white/95 line-clamp-3 sm:text-sm">
                        {{ $mp->languagesForDisplay() !== [] ? implode(', ', $mp->languagesForDisplay()) : __('dashboard_muthowif.empty_field') }}
                    </p>
                </div>
                <div class="group relative overflow-hidden rounded-xl border border-white/15 bg-white/[0.07] p-3 text-sm shadow-inner backdrop-blur-sm transition hover:bg-white/[0.1]">
                    <span class="absolute right-3 top-3 flex h-8 w-8 items-center justify-center rounded-lg bg-white/10 text-emerald-100 ring-1 ring-white/15" aria-hidden="true">
                        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path d="M10.394 2.08a1 1 0 00-.788 0l-7 3a1 1 0 000 1.84L5.25 8.051a.999.999 0 01.356-.257l4-1.714a1 1 0 11.788 1.838L7.667 9.088l1.94.831a1 1 0 00.787 0l7-3a1 1 0 000-1.838l-7-3zM3.31 9.397L5 10.12v4.102a8.969 8.969 0 00-1.05-.174 1 1 0 01-.89-.89 11.115 11.115 0 01.25-3.762zM9.3 16.573A9.026 9.026 0 007 14.935v-3.957l1.818.78a3 3 0 002.364 0l5.508-2.361a11.026 11.026 0 01.25 3.762 1 1 0 01-.89.89 8.968 8.968 0 00-5.35 2.524 1 1 0 01-1.4 0zM6 18a1 1 0 001-1v-2.065a8.935 8.935 0 00-2-.712V17a1 1 0 001 1z" /></svg>
                    </span>
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-emerald-100/80">{{ __('dashboard_muthowif.label_education') }}</p>
                    <p class="mt-1.5 pr-5 text-xs leading-snug text-white/95 line-clamp-3 sm:text-sm">
                        {{ $mp->educationsForDisplay() !== [] ? implode(', ', $mp->educationsForDisplay()) : __('dashboard_muthowif.empty_field') }}
                    </p>
                </div>
                <div class="group relative overflow-hidden rounded-xl border border-white/15 bg-white/[0.07] p-3 text-sm shadow-inner backdrop-blur-sm transition hover:bg-white/[0.1] sm:col-span-3 lg:col-span-1">
                    <span class="absolute right-3 top-3 flex h-8 w-8 items-center justify-center rounded-lg bg-white/10 text-emerald-100 ring-1 ring-white/15" aria-hidden="true">
                        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd" /></svg>
                    </span>
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-emerald-100/80">{{ __('dashboard_muthowif.label_experience') }}</p>
                    <p class="mt-1.5 pr-5 text-xs leading-snug text-white/95 line-clamp-3 sm:text-sm">
                        {{ $mp->workExperiencesForDisplay() !== [] ? implode(', ', $mp->workExperiencesForDisplay()) : __('dashboard_muthowif.empty_field') }}
                    </p>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-x-4 gap-y-1 border-t border-white/10 pt-3">
                <a href="{{ route('profile.edit') }}" class="inline-flex items-center gap-1 text-xs font-semibold text-white/95 underline decoration-white/30 underline-offset-2 transition hover:decoration-white sm:text-sm">
                    {{ __('dashboard_muthowif.nav_profile') }}
                    <svg class="h-4 w-4 shrink-0 opacity-80" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M3 10a.75.75 0 01.75-.75h10.638L10.23 5.29a.75.75 0 111.04-1.08l5.5 5.25a.75.75 0 010 1.08l-5.5 5.25a.75.75 0 11-1.04-1.08l4.158-3.96H3.75A.75.75 0 013 10z" clip-rule="evenodd" /></svg>
                </a>
                <a href="{{ route('layanan.show', $mp) }}" class="inline-flex items-center gap-1 text-xs font-semibold text-emerald-100 underline decoration-emerald-300/40 underline-offset-2 transition hover:text-white hover:decoration-white sm:text-sm">
                    {{ __('dashboard_muthowif.action_public') }}
                    <svg class="h-4 w-4 shrink-0 opacity-80" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M3 10a.75.75 0 01.75-.75h10.638L10.23 5.29a.75.75 0 111.04-1.08l5.5 5.25a.75.75 0 010 1.08l-5.5 5.25a.75.75 0 11-1.04-1.08l4.158-3.96H3.75A.75.75 0 013 10z" clip-rule="evenodd" /></svg>
                </a>
            </div>
        </div>
    </section>

    {{-- Pintasan: ikon + label --}}
    <nav class="sticky top-0 z-30 -mx-1 rounded-xl border border-slate-200/80 bg-white/95 px-1 py-1.5 shadow-sm backdrop-blur-sm sm:mx-0" aria-label="{{ __('dashboard_muthowif.sticky_nav_aria') }}">
        <div class="flex snap-x snap-mandatory gap-1.5 overflow-x-auto pb-0.5 [-ms-overflow-style:none] [scrollbar-width:none] sm:flex-wrap sm:gap-2 sm:overflow-x-visible sm:pb-0 [&::-webkit-scrollbar]:hidden">
            <a href="#muthowif-overview" class="inline-flex snap-start shrink-0 items-center gap-1.5 rounded-lg border border-slate-200/90 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-800 shadow-sm transition hover:border-brand-200 hover:bg-brand-50/80 sm:px-3 sm:text-[13px]">
                <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-md bg-brand-100 text-brand-700" aria-hidden="true">
                    <svg class="h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4.25 2A2.25 2.25 0 002 4.25v2.5A2.25 2.25 0 004.25 9h2.5A2.25 2.25 0 009 6.75v-2.5A2.25 2.25 0 006.75 2h-2.5zm0 9A2.25 2.25 0 002 13.25v2.5A2.25 2.25 0 004.25 18h2.5A2.25 2.25 0 009 15.75v-2.5A2.25 2.25 0 006.75 11h-2.5zm9-9A2.25 2.25 0 0011 4.25v2.5A2.25 2.25 0 0013.25 9h2.5A2.25 2.25 0 0018 6.75v-2.5A2.25 2.25 0 0015.75 2h-2.5zm0 9A2.25 2.25 0 0011 13.25v2.5A2.25 2.25 0 0013.25 18h2.5A2.25 2.25 0 0018 15.75v-2.5A2.25 2.25 0 0015.75 11h-2.5z" clip-rule="evenodd" /></svg>
                </span>
                {{ __('dashboard_muthowif.nav_summary') }}
            </a>
            <a href="{{ route('muthowif.bookings.index') }}" class="inline-flex snap-start shrink-0 items-center gap-1.5 rounded-lg border border-slate-200/90 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-800 shadow-sm transition hover:border-violet-200 hover:bg-violet-50/90 sm:px-3 sm:text-[13px]">
                <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-md bg-violet-100 text-violet-700" aria-hidden="true">
                    <svg class="h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4.5 2A1.5 1.5 0 003 3.5v13A1.5 1.5 0 004.5 18h11a1.5 1.5 0 001.5-1.5V7.621a1.5 1.5 0 00-.44-1.06l-4.12-4.122A1.5 1.5 0 0011.378 2H4.5zm2.25 8.5a.75.75 0 000 1.5h6.75a.75.75 0 000-1.5H6.75zm0 2.5a.75.75 0 000 1.5h6.75a.75.75 0 000-1.5H6.75z" clip-rule="evenodd" /></svg>
                </span>
                {{ __('dashboard_muthowif.nav_bookings') }}
            </a>
            <a href="#muthowif-schedule" class="inline-flex snap-start shrink-0 items-center gap-1.5 rounded-lg border border-slate-200/90 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-800 shadow-sm transition hover:border-brand-200 hover:bg-brand-50/80 sm:px-3 sm:text-[13px]">
                <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-md bg-amber-100 text-amber-800" aria-hidden="true">
                    <svg class="h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.75 2a.75.75 0 01.75.75V4h7V2.75a.75.75 0 011.5 0V4h.25A2.75 2.75 0 0118 6.75v8.5A2.75 2.75 0 0115.25 18H4.75A2.75 2.75 0 012 15.25v-8.5A2.75 2.75 0 014.75 4H5V2.75A.75.75 0 015.75 2zm-1 5.5c-.69 0-1.25.56-1.25 1.25v6.5c0 .69.56 1.25 1.25 1.25h10.5c.69 0 1.25-.56 1.25-1.25v-6.5c0-.69-.56-1.25-1.25-1.25H4.75z" clip-rule="evenodd" /></svg>
                </span>
                {{ __('dashboard_muthowif.nav_calendar') }}
            </a>
            <a href="{{ route('muthowif.pelayanan.edit') }}" class="inline-flex snap-start shrink-0 items-center gap-1.5 rounded-lg border border-slate-200/90 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-800 shadow-sm transition hover:border-brand-200 hover:bg-brand-50/80 sm:px-3 sm:text-[13px]">
                <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-md bg-sky-100 text-sky-700" aria-hidden="true">
                    <svg class="h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z" /></svg>
                </span>
                {{ __('dashboard_muthowif.nav_services') }}
            </a>
            <a href="{{ route('muthowif.jadwal.index') }}" class="inline-flex snap-start shrink-0 items-center gap-1.5 rounded-lg border border-slate-200/90 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-800 shadow-sm transition hover:border-amber-200 hover:bg-amber-50/90 sm:px-3 sm:text-[13px]">
                <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-md bg-orange-100 text-orange-800" aria-hidden="true">
                    <svg class="h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm.75-13a.75.75 0 00-1.5 0v5c0 .414.336.75.75.75h4a.75.75 0 000-1.5h-3.25V5z" clip-rule="evenodd" /></svg>
                </span>
                {{ __('dashboard_muthowif.nav_time_off') }}
            </a>
            <a href="{{ route('muthowif.withdrawals.index') }}" class="inline-flex snap-start shrink-0 items-center gap-1.5 rounded-lg border border-slate-200/90 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-800 shadow-sm transition hover:border-emerald-200 hover:bg-emerald-50/90 sm:px-3 sm:text-[13px]">
                <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-md bg-emerald-100 text-emerald-800" aria-hidden="true">
                    <svg class="h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M2.5 4A1.5 1.5 0 001 5.5V6h18v-.5A1.5 1.5 0 0017.5 4h-15zM19 8.5H1v6A1.5 1.5 0 002.5 16h15a1.5 1.5 0 001.5-1.5v-6zM3 12.5a1 1 0 011-1h2a1 1 0 011 1v1a1 1 0 01-1 1H4a1 1 0 01-1-1v-1z" clip-rule="evenodd" /></svg>
                </span>
                {{ __('dashboard_muthowif.nav_wallet') }}
            </a>
            <a href="{{ route('profile.edit') }}" class="inline-flex snap-start shrink-0 items-center gap-1.5 rounded-lg border border-slate-200/90 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-800 shadow-sm transition hover:bg-slate-50 sm:px-3 sm:text-[13px]">
                <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-md bg-slate-200 text-slate-700" aria-hidden="true">
                    <svg class="h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-5.5-2.5a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0zM10 12a5.5 5.5 0 00-4.9 3H14.9A5.5 5.5 0 0010 12z" clip-rule="evenodd" /></svg>
                </span>
                {{ __('dashboard_muthowif.nav_profile') }}
            </a>
        </div>
    </nav>

    <section id="muthowif-overview" class="scroll-mt-20 space-y-3 md:scroll-mt-24">
        <h2 class="text-sm font-bold text-slate-900 sm:text-base">{{ __('dashboard_muthowif.section_summary') }}</h2>
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 sm:gap-3 lg:grid-cols-12">
        <a href="{{ route('muthowif.withdrawals.index') }}" class="group relative flex flex-col overflow-hidden rounded-2xl border border-emerald-300/40 bg-gradient-to-br from-emerald-600 via-emerald-700 to-emerald-900 p-4 text-white shadow-md ring-1 ring-white/10 transition hover:shadow-lg focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-300 focus-visible:ring-offset-2 sm:col-span-2 lg:col-span-4">
            <div class="pointer-events-none absolute -right-8 -top-8 h-36 w-36 rounded-full bg-white/15 blur-2xl"></div>
            <div class="pointer-events-none absolute bottom-0 left-0 h-24 w-full bg-gradient-to-t from-black/10 to-transparent"></div>
            <div class="relative flex items-start gap-3">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white/15 ring-1 ring-white/25" aria-hidden="true">
                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.069.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z" /><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd" /></svg>
                </span>
                <div class="min-w-0 flex-1">
                    <p class="text-[10px] font-semibold uppercase tracking-wide text-emerald-100/90">{{ __('dashboard_muthowif.wallet_balance') }}</p>
                    <p class="mt-0.5 text-xl font-bold tabular-nums tracking-tight sm:text-2xl">Rp {{ $balanceFormatted }}</p>
                    <p class="mt-1 hidden text-[11px] leading-snug text-emerald-100/80 sm:block">{{ __('dashboard_muthowif.wallet_caption') }}</p>
                    <span class="mt-2 inline-flex items-center gap-0.5 text-xs font-semibold text-white">
                        {{ __('dashboard_muthowif.action_withdraw') }}
                        <svg class="h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M3 10a.75.75 0 01.75-.75h10.638L10.23 5.29a.75.75 0 111.04-1.08l5.5 5.25a.75.75 0 010 1.08l-5.5 5.25a.75.75 0 11-1.04-1.08l4.158-3.96H3.75A.75.75 0 013 10z" clip-rule="evenodd" /></svg>
                    </span>
                </div>
            </div>
        </a>
        <a href="{{ route('muthowif.bookings.index') }}" class="flex flex-col justify-center rounded-2xl border border-slate-200/80 bg-gradient-to-b from-white to-slate-50/90 p-4 shadow-sm ring-1 ring-slate-100 transition hover:border-amber-200 hover:shadow-sm focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-400 focus-visible:ring-offset-2 sm:col-span-1 lg:col-span-2">
            <div class="flex items-center gap-3">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-amber-400 to-amber-600 text-white shadow-sm ring-1 ring-white/30">
                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" /></svg>
                </span>
                <div class="min-w-0 flex-1">
                    <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">{{ __('dashboard_muthowif.pending') }}</p>
                    <p class="text-xl font-bold tabular-nums text-slate-900 sm:text-2xl">{{ $mp->pending_bookings_count }}</p>
                    <p class="text-[11px] text-slate-600">{{ __('dashboard_muthowif.new_requests') }}</p>
                </div>
            </div>
        </a>
        <a href="{{ route('muthowif.bookings.index') }}" class="flex flex-col justify-center rounded-2xl border border-slate-200/80 bg-gradient-to-b from-white to-slate-50/90 p-4 shadow-sm ring-1 ring-slate-100 transition hover:border-brand-200 hover:shadow-sm focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400 focus-visible:ring-offset-2 sm:col-span-1 lg:col-span-2">
            <div class="flex items-center gap-3">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-brand-500 to-brand-700 text-white shadow-sm ring-1 ring-white/30">
                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" /></svg>
                </span>
                <div class="min-w-0 flex-1">
                    <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">{{ __('dashboard_muthowif.confirmed') }}</p>
                    <p class="text-xl font-bold tabular-nums text-slate-900 sm:text-2xl">{{ $mp->confirmed_bookings_count }}</p>
                    <p class="text-[11px] text-slate-600">{{ __('dashboard_muthowif.active_bookings') }}</p>
                </div>
            </div>
        </a>
        <div class="flex flex-col justify-center rounded-2xl border border-slate-200/80 bg-gradient-to-b from-white to-slate-50/90 p-4 shadow-sm ring-1 ring-slate-100 sm:col-span-2 lg:col-span-4">
            <div class="flex items-center gap-3">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-sky-500 to-sky-700 text-white shadow-sm ring-1 ring-white/30" aria-hidden="true">
                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.069.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z" /><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd" /></svg>
                </span>
                <div class="min-w-0 flex-1">
                    <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">{{ __('dashboard_muthowif.stat_gross_volume') }}</p>
                    <p class="text-xl font-bold tabular-nums text-slate-900 sm:text-2xl">Rp {{ $grossVolumeFormatted }}</p>
                    <p class="text-[11px] text-slate-600">{{ __('dashboard_muthowif.stat_gross_volume_caption') }}</p>
                </div>
            </div>
        </div>
    </div>
    </section>

    <section id="muthowif-schedule" class="scroll-mt-20 space-y-3 md:scroll-mt-24">
        <h2 class="text-sm font-bold text-slate-900 sm:text-base">{{ __('dashboard_muthowif.section_schedule') }}</h2>
    <div
        class="grid grid-cols-1 gap-4 lg:grid-cols-12 lg:gap-5"
        x-data="muthowifDashboardCalendar({
            url: @js(route('dashboard.muthowif-calendar')),
            dashboardUrl: @js(route('dashboard')),
            month: @js($calendarData['calendarMonth']->format('Y-m')),
        })"
        @click.capture="onCalendarNavClick($event)"
    >
        <div class="lg:col-span-7" :class="{ 'opacity-60 pointer-events-none': loading }">
            @include('partials.muthowif-calendar-panel', $calendarData)
        </div>

        <div class="flex flex-col gap-3 lg:col-span-5">
            <div class="rounded-2xl border border-slate-200/80 bg-gradient-to-b from-white to-slate-50/90 p-4 shadow-sm ring-1 ring-slate-100">
                <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-200/80 pb-2">
                    <div class="flex items-center gap-2">
                        <span class="h-8 w-1 rounded-full bg-gradient-to-b from-brand-500 to-emerald-500" aria-hidden="true"></span>
                        <h4 class="text-sm font-bold text-slate-900">{{ __('dashboard_muthowif.upcoming') }}</h4>
                    </div>
                    <a href="{{ route('muthowif.bookings.index') }}" class="shrink-0 rounded-full bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-700 ring-1 ring-slate-200/80 transition hover:bg-slate-200/80">{{ __('dashboard_muthowif.open') }}</a>
                </div>
                @if ($upcomingBookings->isEmpty())
                    <p class="mt-3 text-sm text-slate-500">{{ __('dashboard_muthowif.no_upcoming') }}</p>
                @else
                    <ul class="mt-3 space-y-2 text-sm">
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
                                <a href="{{ route('muthowif.bookings.show', $row) }}" class="flex flex-col gap-1 rounded-xl border border-slate-200/80 bg-white/80 px-3 py-2.5 text-left shadow-sm ring-1 ring-slate-100/80 transition hover:border-brand-200/80 hover:bg-brand-50/30 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:ring-offset-2">
                                    <div class="flex flex-wrap items-start justify-between gap-1.5">
                                        <p class="min-w-0 text-sm font-semibold text-slate-900">{{ $row->customer?->name ?? __('dashboard_muthowif.guest') }}</p>
                                        <span class="inline-flex shrink-0 rounded-full px-2 py-0.5 text-[10px] font-semibold ring-1 {{ $statusPill }}">{{ $row->status->label() }}</span>
                                    </div>
                                    <p class="text-[11px] text-slate-600">
                                        {{ Carbon::parse($row->starts_on)->format('d/m') }} – {{ Carbon::parse($row->ends_on)->format('d/m') }}
                                        @if ($row->service_type)
                                            <span class="text-slate-400"> · </span>{{ $row->service_type->label() }}
                                        @endif
                                    </p>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            <div :class="{ 'opacity-60 pointer-events-none': loading }">
                @include('partials.muthowif-blocked-panel', $calendarData)
            </div>
        </div>
    </div>
    </section>
</div>
