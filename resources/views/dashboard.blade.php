<x-app-layout>
    <div class="relative min-h-[calc(100vh-4rem)] overflow-hidden bg-gradient-to-b from-slate-100 via-slate-50 to-white py-8 sm:py-12 @if (Auth::user()->isVerifiedMuthowif()) !py-5 sm:!py-6 @endif">
        <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_80%_50%_at_50%_-20%,rgba(120,53,15,0.08),transparent)]"></div>
        <div class="pointer-events-none absolute right-0 top-24 h-72 w-72 rounded-full bg-brand-400/5 blur-3xl"></div>
        <div class="pointer-events-none absolute -left-20 bottom-0 h-64 w-64 rounded-full bg-violet-400/5 blur-3xl"></div>

        <div class="relative mx-auto max-w-7xl space-y-10 px-4 sm:px-6 lg:px-8">

            @if (Auth::user()->isCustomer())
                {{-- Hero jamaah --}}
                <section class="relative overflow-hidden rounded-[1.75rem] bg-gradient-to-br from-slate-900 via-brand-900 to-amber-950 text-white shadow-[0_25px_50px_-12px_rgba(88,28,28,0.35)] ring-1 ring-white/10 sm:rounded-3xl">
                    <div class="absolute inset-0 bg-[url('data:image/svg+xml,%3Csvg width=\'60\' height=\'60\' viewBox=\'0 0 60 60\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'none\' fill-rule=\'evenodd\'%3E%3Cg fill=\'%23ffffff\' fill-opacity=\'0.05\'%3E%3Cpath d=\'M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E')] opacity-40"></div>
                    <div class="pointer-events-none absolute -right-16 top-0 h-80 w-80 rounded-full bg-brand-400/25 blur-3xl"></div>
                    <div class="pointer-events-none absolute bottom-0 left-1/3 h-72 w-96 rounded-full bg-amber-400/15 blur-3xl"></div>
                    <div class="pointer-events-none absolute right-1/4 top-1/2 h-32 w-32 rounded-full bg-white/5 blur-2xl"></div>

                    <div class="relative space-y-8 px-5 py-8 sm:px-8 sm:py-10 lg:px-10 lg:py-12">
                        <div class="max-w-3xl">
                            <div class="inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-1 text-[11px] font-semibold uppercase tracking-wider text-amber-100/95 ring-1 ring-white/15 backdrop-blur-sm">
                                <span class="h-1.5 w-1.5 rounded-full bg-emerald-400 shadow-[0_0_8px_rgba(52,211,153,0.8)]" aria-hidden="true"></span>
                                {{ __('dashboard.customer_badge') }}
                            </div>
                            <p class="mt-5 text-sm font-medium text-brand-100/90">{{ __('dashboard.hello') }}</p>
                            <p class="mt-0.5 text-3xl font-bold tracking-tight text-white sm:text-4xl">{{ Auth::user()->name }}</p>
                            <span class="mt-4 inline-flex items-center rounded-full bg-white/10 px-3 py-1 text-xs font-semibold text-brand-100 ring-1 ring-white/20">
                                {{ Auth::user()->role->label() }}
                            </span>
                            <h3 class="mt-6 text-xl font-semibold leading-snug text-white sm:text-2xl">
                                {{ __('dashboard.customer_headline') }}
                            </h3>
                            @php
                                $availableHighlight = '<strong class="font-semibold text-white">'.e(__('dashboard.available')).'</strong>';
                            @endphp
                            <p class="mt-3 text-sm leading-relaxed text-brand-100/90 sm:text-base">
                                {!! __('dashboard.marketplace_html', ['available' => $availableHighlight]) !!}
                            </p>
                        </div>

                        <div class="w-full min-w-0 rounded-2xl border border-white/15 bg-white/[0.07] p-4 shadow-inner shadow-black/10 backdrop-blur-md sm:p-5">
                            <p class="mb-3 flex items-center gap-2 text-sm font-semibold text-brand-50">
                                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-white/15 ring-1 ring-white/25" aria-hidden="true">
                                    <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.75 2a.75.75 0 01.75.75V4h7V2.75a.75.75 0 011.5 0V4h.25A2.75 2.75 0 0118 6.75v8.5A2.75 2.75 0 0115.25 18H4.75A2.75 2.75 0 012 15.25v-8.5A2.75 2.75 0 014.75 4H5V2.75A.75.75 0 015.75 2zm-1 5.5c-.69 0-1.25.56-1.25 1.25v6.5c0 .69.56 1.25 1.25 1.25h10.5c.69 0 1.25-.56 1.25-1.25v-6.5c0-.69-.56-1.25-1.25-1.25H4.75z" clip-rule="evenodd" /></svg>
                                </span>
                                {{ __('dashboard.search_availability') }}
                            </p>
                            @include('layanan.partials.date-search-form', [
                                'startDate' => '',
                                'endDate' => '',
                                'searchQuery' => '',
                            ])
                        </div>

                        <div class="flex flex-wrap gap-2.5 border-t border-white/10 pt-4 sm:gap-3">
                            @foreach ([
                                __('dashboard.chip_verified'),
                                __('dashboard.chip_realtime'),
                                __('dashboard.chip_group_private'),
                            ] as $chip)
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-white/10 px-3.5 py-2 text-xs font-medium text-white/95 ring-1 ring-white/15 backdrop-blur-sm transition hover:bg-white/15">
                                    <svg class="h-3.5 w-3.5 shrink-0 text-emerald-300" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                                    </svg>
                                    {{ $chip }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                </section>

                <div>
                    <div class="mb-5 flex items-end justify-between gap-4">
                        <div class="flex items-start gap-3">
                            <span class="mt-0.5 hidden h-10 w-1 rounded-full bg-gradient-to-b from-brand-500 to-amber-500 sm:block" aria-hidden="true"></span>
                            <div>
                                <h2 class="text-xl font-bold tracking-tight text-slate-900">{{ __('dashboard.quick_access') }}</h2>
                                <p class="mt-1 text-sm text-slate-600">{{ __('dashboard.quick_access_sub') }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <a href="{{ route('layanan.index') }}" class="group relative flex flex-col gap-4 overflow-hidden rounded-3xl border border-slate-200/80 bg-gradient-to-b from-white to-slate-50/90 p-6 shadow-sm shadow-slate-200/40 ring-1 ring-slate-100 transition-all duration-300 hover:-translate-y-1 hover:border-violet-200/80 hover:shadow-lg hover:shadow-violet-500/10 hover:ring-violet-100">
                            <div class="absolute -right-6 -top-6 h-24 w-24 rounded-full bg-violet-400/10 blur-2xl transition group-hover:bg-violet-400/20"></div>
                            <span class="relative flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br from-violet-500 to-violet-700 text-white shadow-lg shadow-violet-500/25 ring-1 ring-white/20 transition group-hover:scale-105" aria-hidden="true">
                                <svg class="h-7 w-7" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" /></svg>
                            </span>
                            <div class="relative">
                                <p class="font-semibold text-slate-900">{{ __('dashboard.shortcut_find_title') }}</p>
                                <p class="mt-1 text-sm leading-relaxed text-slate-600">{{ __('dashboard.shortcut_find_desc') }}</p>
                            </div>
                            <span class="relative inline-flex items-center gap-1 text-sm font-semibold text-violet-700 group-hover:text-violet-800">
                                {{ __('dashboard.shortcut_find_cta') }}
                                <svg class="h-4 w-4 transition group-hover:translate-x-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M3 10a.75.75 0 01.75-.75h10.638L10.23 5.29a.75.75 0 111.04-1.08l5.5 5.25a.75.75 0 010 1.08l-5.5 5.25a.75.75 0 11-1.04-1.08l4.158-3.96H3.75A.75.75 0 013 10z" clip-rule="evenodd" /></svg>
                            </span>
                        </a>
                        <a href="{{ route('bookings.index') }}" class="group relative flex flex-col gap-4 overflow-hidden rounded-3xl border border-slate-200/80 bg-gradient-to-b from-white to-slate-50/90 p-6 shadow-sm shadow-slate-200/40 ring-1 ring-slate-100 transition-all duration-300 hover:-translate-y-1 hover:border-brand-200/80 hover:shadow-lg hover:shadow-brand-500/10 hover:ring-brand-100">
                            <div class="absolute -right-6 -top-6 h-24 w-24 rounded-full bg-brand-400/10 blur-2xl transition group-hover:bg-brand-400/20"></div>
                            <span class="relative flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br from-brand-600 to-brand-800 text-white shadow-lg shadow-brand-600/25 ring-1 ring-white/20 transition group-hover:scale-105" aria-hidden="true">
                                <svg class="h-7 w-7" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3m-6.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z" /></svg>
                            </span>
                            <div class="relative">
                                <p class="font-semibold text-slate-900">{{ __('dashboard.shortcut_bookings_title') }}</p>
                                <p class="mt-1 text-sm leading-relaxed text-slate-600">{{ __('dashboard.shortcut_bookings_desc') }}</p>
                            </div>
                            <span class="relative inline-flex items-center gap-1 text-sm font-semibold text-brand-700 group-hover:text-brand-800">
                                {{ __('dashboard.shortcut_bookings_cta') }}
                                <svg class="h-4 w-4 transition group-hover:translate-x-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M3 10a.75.75 0 01.75-.75h10.638L10.23 5.29a.75.75 0 111.04-1.08l5.5 5.25a.75.75 0 010 1.08l-5.5 5.25a.75.75 0 11-1.04-1.08l4.158-3.96H3.75A.75.75 0 013 10z" clip-rule="evenodd" /></svg>
                            </span>
                        </a>
                        <a href="{{ route('profile.edit') }}" class="group relative flex flex-col gap-4 overflow-hidden rounded-3xl border border-slate-200/80 bg-gradient-to-b from-white to-slate-50/90 p-6 shadow-sm shadow-slate-200/40 ring-1 ring-slate-100 transition-all duration-300 hover:-translate-y-1 hover:border-slate-300 hover:shadow-lg hover:shadow-slate-400/10">
                            <div class="absolute -right-6 -top-6 h-24 w-24 rounded-full bg-slate-400/10 blur-2xl transition group-hover:bg-slate-400/15"></div>
                            <span class="relative flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br from-slate-600 to-slate-800 text-white shadow-lg shadow-slate-600/20 ring-1 ring-white/10 transition group-hover:scale-105" aria-hidden="true">
                                <svg class="h-7 w-7" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" /></svg>
                            </span>
                            <div class="relative">
                                <p class="font-semibold text-slate-900">{{ __('dashboard.shortcut_profile_title') }}</p>
                                <p class="mt-1 text-sm leading-relaxed text-slate-600">{{ __('dashboard.shortcut_profile_desc') }}</p>
                            </div>
                            <span class="relative inline-flex items-center gap-1 text-sm font-semibold text-slate-700 group-hover:text-slate-900">
                                {{ __('dashboard.shortcut_profile_cta') }}
                                <svg class="h-4 w-4 transition group-hover:translate-x-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M3 10a.75.75 0 01.75-.75h10.638L10.23 5.29a.75.75 0 111.04-1.08l5.5 5.25a.75.75 0 010 1.08l-5.5 5.25a.75.75 0 11-1.04-1.08l4.158-3.96H3.75A.75.75 0 013 10z" clip-rule="evenodd" /></svg>
                            </span>
                        </a>
                    </div>
                </div>
            @elseif (Auth::user()->isVerifiedMuthowif())
                @include('partials.dashboard-muthowif')
            @elseif (Auth::user()->isMuthowif())
                <div class="relative overflow-hidden rounded-3xl border border-amber-200/90 bg-gradient-to-br from-amber-50 via-white to-orange-50/50 p-6 shadow-lg shadow-amber-900/5 ring-1 ring-amber-100 sm:p-8">
                    <div class="pointer-events-none absolute right-0 top-0 h-40 w-40 rounded-full bg-amber-300/20 blur-3xl"></div>
                    <div class="relative flex flex-col gap-6 sm:flex-row sm:items-start sm:justify-between">
                        <div class="flex gap-4">
                            <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-amber-400 to-amber-600 text-white shadow-md shadow-amber-600/25" aria-hidden="true">
                                <svg class="h-7 w-7" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" /></svg>
                            </span>
                            <div>
                                <p class="text-sm font-semibold text-amber-900">{{ __('dashboard.pending_review_title') }}</p>
                                <p class="mt-1 text-lg font-bold text-slate-900">{{ __('dashboard.hello') }} {{ Auth::user()->name }}</p>
                                <p class="mt-2 max-w-xl text-sm leading-relaxed text-slate-600">
                                    {{ __('dashboard.pending_review_body') }}
                                </p>
                            </div>
                        </div>
                        <div class="flex flex-wrap gap-3 sm:justify-end">
                            <a href="{{ route('profile.edit') }}" class="inline-flex items-center gap-2 rounded-2xl border border-slate-200/90 bg-white px-5 py-2.5 text-sm font-semibold text-slate-800 shadow-sm ring-1 ring-slate-100 transition hover:bg-slate-50">
                                <svg class="h-5 w-5 text-slate-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" /></svg>
                                {{ __('dashboard.profile') }}
                            </a>
                            <a href="{{ route('layanan.index') }}" class="inline-flex items-center gap-2 rounded-2xl bg-gradient-to-r from-brand-600 to-brand-700 px-5 py-2.5 text-sm font-semibold text-white shadow-md shadow-brand-600/25 transition hover:from-brand-700 hover:to-brand-800">
                                <svg class="h-5 w-5 opacity-95" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 15M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" /></svg>
                                {{ __('dashboard.view_marketplace') }}
                            </a>
                        </div>
                    </div>
                </div>
            @else
                <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-brand-600 via-brand-700 to-brand-900 p-6 text-white shadow-xl shadow-brand-900/25 ring-1 ring-white/10 sm:p-8">
                    <div class="pointer-events-none absolute -right-10 -top-10 h-48 w-48 rounded-full bg-white/10 blur-3xl"></div>
                    <div class="pointer-events-none absolute bottom-0 left-0 h-32 w-64 rounded-full bg-amber-500/10 blur-3xl"></div>
                    <div class="relative flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="text-sm font-medium text-brand-100">{{ __('dashboard.hello') }}</p>
                            <p class="mt-1 text-3xl font-bold tracking-tight">{{ Auth::user()->name }}</p>
                            <p class="mt-2 text-sm text-brand-100/90">
                                {{ __('dashboard.signed_in_as') }}
                                <span class="font-semibold text-white">
                                    {{ Auth::user()->role->label() }}
                                </span>
                            </p>
                        </div>
                        <a href="{{ route('profile.edit') }}" class="inline-flex items-center gap-2 self-start rounded-2xl bg-white/15 px-5 py-2.5 text-sm font-semibold text-white ring-1 ring-white/30 backdrop-blur-sm transition hover:bg-white/25">
                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" /></svg>
                            {{ __('dashboard.profile_settings') }}
                        </a>
                    </div>
                </div>
            @endif

            @if (Auth::user()->isAdmin())
                <div class="relative overflow-hidden rounded-[1.75rem] bg-gradient-to-br from-slate-900 via-brand-950 to-slate-950 p-6 text-white shadow-[0_25px_50px_-12px_rgba(15,23,42,0.45)] ring-1 ring-white/10 sm:rounded-3xl sm:p-8">
                    <div class="pointer-events-none absolute inset-0 bg-[url('data:image/svg+xml,%3Csvg width=\'40\' height=\'40\' viewBox=\'0 0 40 40\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'%23ffffff\' fill-opacity=\'0.03\'%3E%3Cpath d=\'M20 20h20v20H20zM0 0h20v20H0z\'/%3E%3C/g%3E%3C/svg%3E')] opacity-60"></div>
                    <div class="pointer-events-none absolute -right-20 top-1/2 h-72 w-72 -translate-y-1/2 rounded-full bg-brand-500/20 blur-3xl"></div>

                    @php
                        $paidPaymentsBase = \App\Models\BookingPayment::query()->whereIn('status', ['settlement', 'capture']);
                        $totalPlatformFees = (float) (clone $paidPaymentsBase)->sum('platform_fee_amount');
                        $totalVolume = (int) (clone $paidPaymentsBase)->sum('gross_amount');
                        $settledCount = (int) (clone $paidPaymentsBase)->count();
                        $latestPayments = (clone $paidPaymentsBase)
                            ->with(['muthowifBooking.customer', 'muthowifBooking.muthowifProfile.user'])
                            ->orderByDesc('settled_at')
                            ->limit(5)
                            ->get();
                        $pendingWithdrawCount = (int) \App\Models\MuthowifWithdrawal::query()
                            ->where('status', 'pending_approval')
                            ->count();
                        $pendingRefundCount = (int) \App\Models\BookingRefundRequest::query()
                            ->where('status', \App\Enums\BookingChangeRequestStatus::Pending)
                            ->count();
                        $fmt = fn (float|int $n) => \App\Support\IndonesianNumber::formatThousands((string) (int) round((float) $n));
                    @endphp

                    <div class="relative grid grid-cols-1 items-start gap-8 lg:grid-cols-12">
                        <div class="lg:col-span-12">
                            <div class="flex flex-wrap items-end gap-3">
                                <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-white/10 ring-1 ring-white/20" aria-hidden="true">
                                    <svg class="h-5 w-5 text-emerald-300" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 006 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0118 16.5h-2.25m0 0h3m-3 0H15m0 0h3" /></svg>
                                </span>
                                <div>
                                    <p class="text-sm font-medium text-emerald-200/90">{{ __('dashboard.admin_label') }}</p>
                                    <h3 class="mt-0.5 text-2xl font-bold tracking-tight sm:text-3xl">
                                        {{ __('dashboard.admin_title') }}
                                    </h3>
                                </div>
                            </div>
                            <p class="mt-3 max-w-2xl text-sm leading-relaxed text-white/75">
                                {{ __('dashboard.admin_subtitle') }}
                            </p>

                            <div class="mt-6 grid grid-cols-1 gap-3 sm:grid-cols-3">
                                <div class="group relative overflow-hidden rounded-2xl border border-white/10 bg-white/[0.07] p-5 backdrop-blur-sm transition hover:bg-white/[0.1]">
                                    <div class="flex items-start justify-between gap-2">
                                        <p class="text-[11px] font-semibold uppercase tracking-wider text-white/60">{{ __('dashboard.stat_platform_fee') }}</p>
                                        <span class="rounded-lg bg-emerald-500/20 p-1.5 text-emerald-300 ring-1 ring-emerald-400/30" aria-hidden="true">
                                            <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                        </span>
                                    </div>
                                    <p class="mt-3 text-2xl font-bold tabular-nums tracking-tight text-white sm:text-3xl">Rp {{ $fmt($totalPlatformFees) }}</p>
                                </div>
                                <div class="group relative overflow-hidden rounded-2xl border border-white/10 bg-white/[0.07] p-5 backdrop-blur-sm transition hover:bg-white/[0.1]">
                                    <div class="flex items-start justify-between gap-2">
                                        <p class="text-[11px] font-semibold uppercase tracking-wider text-white/60">{{ __('dashboard.stat_gross_volume') }}</p>
                                        <span class="rounded-lg bg-sky-500/20 p-1.5 text-sky-200 ring-1 ring-sky-400/30" aria-hidden="true">
                                            <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18L9 11.25l4.306 4.307a11.95 11.95 0 015.814-5.519l2.74-1.22m0 0l-5.94-2.28m5.94 2.28l-2.28 5.941" /></svg>
                                        </span>
                                    </div>
                                    <p class="mt-3 text-2xl font-bold tabular-nums tracking-tight text-white sm:text-3xl">Rp {{ $fmt($totalVolume) }}</p>
                                </div>
                                <div class="group relative overflow-hidden rounded-2xl border border-white/10 bg-white/[0.07] p-5 backdrop-blur-sm transition hover:bg-white/[0.1]">
                                    <div class="flex items-start justify-between gap-2">
                                        <p class="text-[11px] font-semibold uppercase tracking-wider text-white/60">{{ __('dashboard.stat_transactions') }}</p>
                                        <span class="rounded-lg bg-violet-500/20 p-1.5 text-violet-200 ring-1 ring-violet-400/30" aria-hidden="true">
                                            <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3m-6.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z" /></svg>
                                        </span>
                                    </div>
                                    <p class="mt-3 text-2xl font-bold tabular-nums tracking-tight text-white sm:text-3xl">{{ $settledCount }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="lg:col-span-12 grid min-w-0 grid-cols-1 items-start gap-5 lg:grid-cols-12">
                            <div class="lg:col-span-8 flex min-w-0 flex-col overflow-hidden rounded-2xl border border-white/15 bg-white text-slate-900 shadow-xl shadow-black/20">
                                <div class="flex items-center justify-between gap-3 border-b border-slate-100 bg-slate-50/80 px-5 py-4">
                                    <h4 class="font-semibold text-slate-900">{{ __('dashboard.recent_transactions') }}</h4>
                                    <a href="{{ route('admin.finance.index') }}" class="inline-flex items-center gap-2 rounded-xl bg-brand-600 px-3 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700">
                                        {{ __('dashboard.view_all') }}
                                        <span aria-hidden="true">→</span>
                                    </a>
                                </div>

                                @if ($latestPayments->isEmpty())
                                    <p class="p-8 text-center text-sm text-slate-500">{{ __('dashboard.empty_settlements') }}</p>
                                @else
                                    <div class="min-w-0 w-full overflow-x-auto">
                                        <table class="min-w-full table-fixed text-sm">
                                            <thead class="bg-white text-left text-[11px] font-semibold uppercase tracking-wide text-slate-500">
                                                <tr>
                                                    <th class="whitespace-nowrap px-4 py-3">{{ __('dashboard.table_time') }}</th>
                                                    <th class="whitespace-nowrap px-4 py-3">{{ __('dashboard.table_order') }}</th>
                                                    <th class="whitespace-nowrap px-4 py-3">{{ __('dashboard.table_customer') }}</th>
                                                    <th class="whitespace-nowrap px-4 py-3">{{ __('dashboard.table_muthowif') }}</th>
                                                    <th class="whitespace-nowrap px-4 py-3 text-right">{{ __('dashboard.table_fee') }}</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-slate-100">
                                                @foreach ($latestPayments as $p)
                                                    @php $b = $p->muthowifBooking; @endphp
                                                    <tr class="transition hover:bg-brand-50/40">
                                                        <td class="whitespace-nowrap px-4 py-3 text-xs text-slate-600">
                                                            {{ $p->settled_at?->format('d/m/Y H:i') ?? '—' }}
                                                        </td>
                                                        <td class="truncate whitespace-nowrap px-4 py-3 font-mono text-xs text-slate-700" title="{{ $p->order_id }}">
                                                            {{ $p->order_id }}
                                                        </td>
                                                        <td class="truncate whitespace-nowrap px-4 py-3 text-slate-800">{{ $b?->customer?->name ?? '—' }}</td>
                                                        <td class="truncate whitespace-nowrap px-4 py-3 text-slate-800">{{ $b?->muthowifProfile?->user?->name ?? '—' }}</td>
                                                        <td class="whitespace-nowrap px-4 py-3 text-right font-medium text-brand-800">
                                                            Rp {{ $fmt((float) $p->platform_fee_amount) }}
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @endif
                            </div>

                            <div class="lg:col-span-4 w-full space-y-4">
                                <div class="overflow-visible rounded-2xl border border-white/15 bg-white/[0.06] p-5 backdrop-blur-sm">
                                    <h4 class="font-semibold text-white">{{ __('dashboard.admin_quick_title') }}</h4>
                                    <p class="mt-1 text-sm leading-relaxed text-white/75">
                                        {{ __('dashboard.admin_quick_sub') }}
                                    </p>
                                    <p class="mt-2 text-xs leading-relaxed text-white/60">
                                        {!! __('dashboard.admin_pending_counts', ['withdraw' => '<span class="font-semibold text-white">'.$pendingWithdrawCount.'</span>', 'refund' => '<span class="font-semibold text-white">'.$pendingRefundCount.'</span>']) !!}
                                    </p>
                                    <div class="mt-4 grid grid-cols-2 gap-2.5">
                                        <a href="{{ route('admin.finance.index') }}" class="group flex flex-col items-center gap-2 rounded-2xl bg-white px-3 py-4 text-center text-slate-900 shadow-md transition hover:scale-[1.02] hover:shadow-lg">
                                            <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-100 text-emerald-800 transition group-hover:bg-emerald-200" aria-hidden="true">
                                                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m0 0H21" /></svg>
                                            </span>
                                            <span class="text-xs font-semibold leading-tight">{{ __('dashboard.finance') }}</span>
                                        </a>
                                        <a href="{{ route('admin.refunds.index') }}" class="group flex flex-col items-center gap-2 rounded-2xl border border-amber-400/35 bg-amber-500/15 px-3 py-4 text-center text-white transition hover:scale-[1.02] hover:bg-amber-500/25">
                                            <span class="relative flex h-11 w-11 items-center justify-center rounded-xl bg-amber-400/25 text-amber-100" aria-hidden="true">
                                                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5" /></svg>
                                                @if ($pendingRefundCount > 0)
                                                    <span class="absolute -right-1 -top-1 flex h-4 min-w-[1rem] items-center justify-center rounded-full bg-amber-400 px-1 text-[10px] font-bold text-slate-900">{{ $pendingRefundCount > 9 ? '9+' : $pendingRefundCount }}</span>
                                                @endif
                                            </span>
                                            <span class="text-xs font-semibold leading-tight">{{ __('dashboard.refund') }}</span>
                                        </a>
                                        <a href="{{ route('admin.withdrawals.index') }}" class="group flex flex-col items-center gap-2 rounded-2xl border border-white/20 bg-white/5 px-3 py-4 text-center text-white transition hover:scale-[1.02] hover:bg-white/10">
                                            <span class="relative flex h-11 w-11 items-center justify-center rounded-xl bg-white/15 text-white" aria-hidden="true">
                                                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z" /></svg>
                                                @if ($pendingWithdrawCount > 0)
                                                    <span class="absolute -right-1 -top-1 flex h-4 min-w-[1rem] items-center justify-center rounded-full bg-white text-[10px] font-bold text-slate-900">{{ $pendingWithdrawCount > 9 ? '9+' : $pendingWithdrawCount }}</span>
                                                @endif
                                            </span>
                                            <span class="text-xs font-semibold leading-tight">{{ __('dashboard.withdraw') }}</span>
                                        </a>
                                        <a href="{{ route('admin.muthowif.index') }}" class="group flex flex-col items-center gap-2 rounded-2xl border border-white/20 bg-white/5 px-3 py-4 text-center text-white transition hover:scale-[1.02] hover:bg-white/10">
                                            <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-violet-500/30 text-violet-100" aria-hidden="true">
                                                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" /></svg>
                                            </span>
                                            <span class="text-xs font-semibold leading-tight">{{ __('dashboard.verify') }}</span>
                                        </a>
                                        <a href="{{ route('admin.logs.index') }}" class="group col-span-2 flex flex-col items-center gap-2 rounded-2xl border border-white/20 bg-white/5 px-3 py-4 text-center text-white transition hover:scale-[1.01] hover:bg-white/10">
                                            <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-slate-500/35 text-slate-100" aria-hidden="true">
                                                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25H12" /></svg>
                                            </span>
                                            <span class="text-xs font-semibold leading-tight">{{ __('dashboard.logs_webhook') }}</span>
                                        </a>
                                    </div>
                                </div>

                                <div class="overflow-visible rounded-2xl border border-white/12 bg-gradient-to-br from-white/8 to-transparent p-5">
                                    <h4 class="font-semibold text-white">{{ __('dashboard.platform_fee_note_title') }}</h4>
                                    <p class="mt-2 text-sm leading-relaxed text-white/75">
                                        {{ __('dashboard.platform_fee_note_body') }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <div class="grid grid-cols-1 gap-5 {{ Auth::user()->isCustomer() ? '' : 'lg:grid-cols-2' }}">
                <div class="group relative overflow-hidden rounded-3xl border border-slate-200/80 bg-gradient-to-br from-white to-amber-50/30 p-6 shadow-md shadow-slate-200/30 ring-1 ring-slate-100/90 transition hover:shadow-lg hover:shadow-amber-100/50">
                    <div class="pointer-events-none absolute -right-8 top-0 h-24 w-24 rounded-full bg-amber-200/30 blur-2xl transition group-hover:bg-amber-200/40"></div>
                    <div class="relative flex gap-4">
                        <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-amber-400 to-amber-600 text-white shadow-md shadow-amber-600/25 ring-1 ring-white/30" aria-hidden="true">
                            <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd" /></svg>
                        </span>
                        <div class="min-w-0 flex-1">
                            <h3 class="font-semibold text-slate-900">{{ __('dashboard.next_steps') }}</h3>
                            <p class="mt-2 text-sm leading-relaxed text-slate-600">
                                @if(Auth::user()->isAdmin())
                                    {!! __('dashboard.next_steps_admin', ['menu' => '<strong class="text-slate-800">'.e(__('dashboard.next_steps_admin_menu')).'</strong>']) !!}
                                @elseif(Auth::user()->isCustomer())
                                    {{ __('dashboard.next_steps_customer') }}
                                @elseif(Auth::user()->isVerifiedMuthowif())
                                    {!! __('dashboard.next_steps_verified_muthowif', ['menu' => '<strong class="text-slate-800">'.e(__('dashboard.next_steps_verified_menu')).'</strong>']) !!}
                                @elseif(Auth::user()->isMuthowif())
                                    {{ __('dashboard.next_steps_pending_muthowif') }}
                                @else
                                    {{ __('dashboard.next_steps_default') }}
                                @endif
                            </p>
                        </div>
                    </div>
                </div>
                @unless(Auth::user()->isCustomer())
                    <div class="group relative overflow-hidden rounded-3xl border border-slate-200/80 bg-gradient-to-br from-white via-white to-brand-50/40 p-6 shadow-md shadow-slate-200/30 ring-1 ring-slate-100/90 transition hover:shadow-lg hover:shadow-brand-100/40">
                        <div class="pointer-events-none absolute -left-6 bottom-0 h-28 w-28 rounded-full bg-brand-300/20 blur-2xl transition group-hover:bg-brand-300/30"></div>
                        <div class="relative flex gap-4">
                            <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-brand-500 to-brand-700 text-white shadow-md shadow-brand-600/25 ring-1 ring-white/25" aria-hidden="true">
                                <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" /></svg>
                            </span>
                            <div class="min-w-0 flex-1">
                                <h3 class="font-semibold text-slate-900">{{ __('dashboard.profile_card_title') }}</h3>
                                <p class="mt-2 text-sm leading-relaxed text-slate-600">
                                    {{ __('dashboard.profile_card_desc') }}
                                </p>
                                <a href="{{ route('profile.edit') }}" class="mt-5 inline-flex items-center gap-2 rounded-2xl bg-gradient-to-r from-brand-600 to-brand-700 px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-brand-600/25 transition hover:from-brand-700 hover:to-brand-800">
                                    <svg class="h-4 w-4 opacity-95" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M7.84 1.804A1 1 0 018.82 1h2.36a1 1 0 01.98.804l.331 1.652a6.993 6.993 0 011.929 1.115l1.598-.54a1 1 0 011.186.447l1.18 2.044a1 1 0 01-.205 1.251l-1.267 1.113a7.047 7.047 0 010 2.228l1.267 1.113a1 1 0 01.206 1.25l-1.18 2.045a1 1 0 01-1.187.447l-1.598-.54A6.993 6.993 0 0111.82 15.33l-.33 1.652a1 1 0 01-.98.804H8.82a1 1 0 01-.98-.804l-.331-1.652a6.993 6.993 0 01-1.929-1.115l-1.598.54a1 1 0 01-1.186-.447l-1.18-2.044a1 1 0 01.205-1.251l1.267-1.114a7.05 7.05 0 010-2.227L1.821 7.773a1 1 0 01-.206-1.25l1.18-2.045a1 1 0 011.187-.447l1.598.54A6.993 6.993 0 017.51 3.456l.33-1.652z" clip-rule="evenodd" /><path d="M10 13a3 3 0 100-6 3 3 0 000 6z" /></svg>
                                    {{ __('dashboard.profile_cta') }}
                                </a>
                            </div>
                        </div>
                    </div>
                @endunless
            </div>
        </div>
    </div>
</x-app-layout>
