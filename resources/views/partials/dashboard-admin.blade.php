<div class="pb-10 sm:pb-12">
    <x-page-container class="ui-stack pt-6 sm:pt-8">

        {{-- ── Kartu statistik ─────────────────────────────────────────── --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
            @foreach ([
                [
                    'label' => __('dashboard.stat_platform_fee'),
                    'value' => 'Rp '.$page->formatMoney($page->platformFeeThis),
                    'pct' => $page->pctPlatform,
                    'icon_bg' => 'bg-emerald-50 text-emerald-600 ring-emerald-100',
                    'icon' => 'M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
                    'caption' => null,
                ],
                [
                    'label' => __('dashboard.stat_gross_volume'),
                    'value' => 'Rp '.$page->formatMoney($page->grossThisMonth),
                    'pct' => $page->pctGross,
                    'icon_bg' => 'bg-violet-50 text-violet-600 ring-violet-100',
                    'icon' => 'M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z',
                    'caption' => null,
                ],
                [
                    'label' => __('dashboard.stat_settlements_period'),
                    'value' => (string) $page->settledThisMonth,
                    'pct' => $page->pctSettled,
                    'icon_bg' => 'bg-amber-50 text-amber-600 ring-amber-100',
                    'icon' => 'M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5',
                    'caption' => null,
                ],
                [
                    'label' => __('dashboard.stat_total_bookings'),
                    'value' => (string) $page->totalBookings,
                    'pct' => $page->pctBookings,
                    'icon_bg' => 'bg-sky-50 text-sky-600 ring-sky-100',
                    'icon' => 'M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z',
                    'caption' => null,
                ],
                [
                    'label' => __('dashboard.stat_active_muthowif'),
                    'value' => (string) $page->activeMuthowif,
                    'pct' => null,
                    'icon_bg' => 'bg-teal-50 text-teal-600 ring-teal-100',
                    'icon' => 'M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z',
                    'caption' => __('dashboard.stat_live_snapshot'),
                ],
            ] as $card)
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-start gap-3">
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full ring-1 {{ $card['icon_bg'] }}" aria-hidden="true">
                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $card['icon'] }}" /></svg>
                        </span>
                        <div class="min-w-0">
                            <p class="text-[11px] font-bold uppercase leading-snug tracking-wide text-slate-500">{{ $card['label'] }}</p>
                            <p class="mt-1 truncate text-xl font-bold tabular-nums text-slate-900">{{ $card['value'] }}</p>
                        </div>
                    </div>
                    @if ($card['pct'] !== null)
                        <p class="mt-2.5 text-xs font-semibold {{ $card['pct'] >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                            {{ $card['pct'] >= 0 ? '+' : '' }}{{ $card['pct'] }}% {{ __('dashboard.stat_vs_last_month') }}
                        </p>
                    @else
                        <p class="mt-2.5 text-xs font-medium text-slate-400">{{ $card['caption'] ?? '—' }}</p>
                    @endif
                </div>
            @endforeach
        </div>

        {{-- ── Konten utama ────────────────────────────────────────────── --}}
        <div class="grid grid-cols-1 items-start gap-5 lg:grid-cols-12">
            <div class="min-w-0 space-y-5 lg:col-span-8">

                {{-- Ikhtisar transaksi --}}
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h2 class="text-base font-bold text-slate-900">{{ __('dashboard.txn_overview_title') }}</h2>
                            <p class="mt-0.5 text-xs text-slate-500">{{ $page->chart['month_label'] }}</p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <span class="inline-flex items-center rounded-lg border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-semibold text-slate-700">{{ __('dashboard.txn_period_this_month') }}</span>
                            <span class="inline-flex items-center rounded-lg border border-slate-200 bg-white px-3 py-1 text-xs font-medium text-slate-500">{{ __('dashboard.txn_period_vs_prev') }}</span>
                        </div>
                    </div>

                    <div class="mt-4 grid grid-cols-1 gap-5 lg:grid-cols-[1fr_minmax(11rem,13rem)]">
                        <div class="min-w-0">
                            <div class="flex gap-2">
                                <div class="flex w-10 shrink-0 flex-col justify-between pb-5 text-right text-[10px] tabular-nums text-slate-400">
                                    <span>{{ $page->formatShort((float) $page->maxY) }}</span>
                                    <span>{{ $page->formatShort($page->maxY * 0.75) }}</span>
                                    <span>{{ $page->formatShort($page->maxY * 0.5) }}</span>
                                    <span>{{ $page->formatShort($page->maxY * 0.25) }}</span>
                                    <span>0</span>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="relative aspect-[21/9] w-full min-h-[10rem] sm:min-h-[11rem]">
                                        <svg class="h-full w-full" viewBox="0 0 {{ $page->chartW }} {{ $page->chartH }}" preserveAspectRatio="none" aria-hidden="true">
                                            <defs>
                                                <linearGradient id="adminGrossFill" x1="0" y1="0" x2="0" y2="1">
                                                    <stop offset="0%" stop-color="rgb(16 185 129)" stop-opacity="0.22" />
                                                    <stop offset="100%" stop-color="rgb(16 185 129)" stop-opacity="0" />
                                                </linearGradient>
                                            </defs>
                                            @for ($i = 0; $i <= 3; $i++)
                                                <line x1="0" y1="{{ round($page->chartH * $i / 4, 2) }}" x2="{{ $page->chartW }}" y2="{{ round($page->chartH * $i / 4, 2) }}" stroke="rgb(241 245 249)" stroke-width="0.3" />
                                            @endfor
                                            <line x1="0" y1="{{ $page->chartH }}" x2="{{ $page->chartW }}" y2="{{ $page->chartH }}" stroke="rgb(245 158 11)" stroke-width="0.35" />
                                            <polygon points="0,{{ $page->chartH }} {{ $page->grossPoly }} {{ $page->chartW }},{{ $page->chartH }}" fill="url(#adminGrossFill)" />
                                            <polyline fill="none" stroke="rgb(16 185 129)" stroke-width="1" stroke-linecap="round" stroke-linejoin="round" vector-effect="non-scaling-stroke" points="{{ $page->grossPoly }}" />
                                            <polyline fill="none" stroke="rgb(245 158 11)" stroke-width="1" stroke-linecap="round" stroke-linejoin="round" vector-effect="non-scaling-stroke" points="{{ $page->refundPoly }}" />
                                        </svg>
                                    </div>
                                    <div class="mt-1.5 flex justify-between text-[10px] text-slate-400">
                                        @foreach ($page->xTicks as $tick)
                                            <span>{{ $tick }}</span>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            <div class="mt-3 flex flex-wrap justify-center gap-4 text-xs font-medium text-slate-600">
                                <span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-emerald-500"></span>{{ __('dashboard.txn_chart_gross') }}</span>
                                <span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-amber-500"></span>{{ __('dashboard.txn_chart_refunds') }}</span>
                            </div>
                        </div>
                        <div class="space-y-4 rounded-2xl border border-slate-100 bg-slate-50/80 p-4">
                            <div>
                                <p class="text-[11px] font-bold uppercase tracking-wide text-slate-500">{{ __('dashboard.txn_chart_gross') }}</p>
                                <p class="mt-1 text-lg font-bold tabular-nums text-slate-900">Rp {{ $page->formatMoney($page->chart['total_gross']) }}</p>
                                <p class="mt-0.5 text-xs text-emerald-600">{{ __('dashboard.txn_in_month') }}</p>
                            </div>
                            <div>
                                <p class="text-[11px] font-bold uppercase tracking-wide text-slate-500">{{ __('dashboard.txn_chart_refunds') }}</p>
                                <p class="mt-1 text-lg font-bold tabular-nums text-slate-900">Rp {{ $page->formatMoney($page->chart['total_refunds']) }}</p>
                            </div>
                            <div class="border-t border-slate-200/80 pt-4">
                                <p class="text-[11px] font-bold uppercase tracking-wide text-slate-500">{{ __('dashboard.txn_chart_net') }}</p>
                                <p class="mt-1 text-lg font-bold tabular-nums text-brand-800">Rp {{ $page->formatMoney($page->netChart) }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Transaksi terkini --}}
                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="flex items-center justify-between gap-3 border-b border-slate-100 px-5 py-4">
                        <h2 class="text-base font-bold text-slate-900">{{ __('dashboard.recent_txn_title') }}</h2>
                        <a href="{{ route('admin.finance.index') }}" class="text-sm font-semibold text-brand-700 hover:text-brand-800">{{ __('dashboard.view_all') }}</a>
                    </div>
                    @if ($page->recentPayments->isEmpty())
                        <div class="flex flex-col items-center justify-center gap-3 px-6 py-14 text-center">
                            <span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400 ring-1 ring-slate-200/80" aria-hidden="true">
                                <svg class="h-7 w-7" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" /></svg>
                            </span>
                            <p class="text-sm text-slate-600">{{ __('dashboard.recent_txn_empty') }}</p>
                        </div>
                    @else
                        <div class="w-full overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead>
                                    <tr class="border-b border-slate-100 bg-slate-50 text-left text-xs font-bold uppercase tracking-wide text-slate-500">
                                        <th class="px-4 py-3">{{ __('dashboard.recent_col_id') }}</th>
                                        <th class="px-4 py-3">{{ __('dashboard.recent_col_type') }}</th>
                                        <th class="px-4 py-3">{{ __('dashboard.recent_col_reference') }}</th>
                                        <th class="px-4 py-3 text-right">{{ __('dashboard.recent_col_amount') }}</th>
                                        <th class="px-4 py-3">{{ __('dashboard.recent_col_status') }}</th>
                                        <th class="px-4 py-3">{{ __('dashboard.recent_col_date') }}</th>
                                        <th class="px-4 py-3"><span class="sr-only">{{ __('dashboard.view_all') }}</span></th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @foreach ($page->recentPayments as $p)
                                        <tr class="hover:bg-slate-50/80">
                                            <td class="whitespace-nowrap px-4 py-3 font-mono text-xs text-slate-600">{{ \Illuminate\Support\Str::limit((string) $p->id, 8, '…') }}</td>
                                            <td class="px-4 py-3">
                                                <span class="inline-flex rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-semibold text-emerald-900 ring-1 ring-emerald-200/80">{{ __('admin.finance.txn_types.order') }}</span>
                                            </td>
                                            <td class="max-w-[10rem] truncate px-4 py-3 font-mono text-xs font-semibold text-slate-800" title="{{ $p->order_id }}">{{ $p->muthowifBooking?->booking_code ?? $p->order_id ?? '—' }}</td>
                                            <td class="whitespace-nowrap px-4 py-3 text-right font-semibold tabular-nums text-slate-900">Rp {{ $page->formatMoney($p->gross_amount) }}</td>
                                            <td class="whitespace-nowrap px-4 py-3">
                                                <span class="inline-flex rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-semibold capitalize text-emerald-800 ring-1 ring-emerald-200/70">{{ $p->status }}</span>
                                            </td>
                                            <td class="whitespace-nowrap px-4 py-3 text-slate-600">{{ $p->settled_at?->timezone(config('app.timezone'))->format('d/m/Y H:i') ?? '—' }}</td>
                                            <td class="px-4 py-3 text-right">
                                                <a href="{{ route('admin.finance.index') }}" class="inline-flex h-7 w-7 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-700" title="{{ __('dashboard.view_all') }}">
                                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

            <aside class="space-y-5 lg:col-span-4">
                {{-- Akses cepat --}}
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="text-base font-bold text-slate-900">{{ __('dashboard.admin_quick_title') }}</h2>
                    <p class="mt-1 text-sm text-slate-600">{{ __('dashboard.admin_quick_sub') }}</p>
                    <div class="mt-4 grid grid-cols-2 gap-2.5 sm:grid-cols-3 lg:grid-cols-2 xl:grid-cols-3">
                        <a href="{{ route('admin.finance.index') }}" class="group flex flex-col items-center gap-2 rounded-2xl border border-slate-200 bg-white px-3 py-4 text-center shadow-sm transition hover:border-emerald-200 hover:shadow-md">
                            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700 ring-1 ring-emerald-100" aria-hidden="true">
                                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                            </span>
                            <span class="text-xs font-semibold text-slate-900">{{ __('dashboard.finance') }}</span>
                        </a>
                        <a href="{{ route('admin.service_monitor.index') }}" class="group flex flex-col items-center gap-2 rounded-2xl border border-slate-200 bg-white px-3 py-4 text-center shadow-sm transition hover:border-red-200 hover:shadow-md">
                            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-red-50 text-red-700 ring-1 ring-red-100" aria-hidden="true">
                                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
                            </span>
                            <span class="text-xs font-semibold text-slate-900">{{ __('nav.service_monitor') }}</span>
                        </a>
                        <a href="{{ route('admin.refunds.index') }}" class="group relative flex flex-col items-center gap-2 rounded-2xl border border-slate-200 bg-white px-3 py-4 text-center shadow-sm transition hover:border-amber-200 hover:shadow-md">
                            <span class="relative flex h-10 w-10 items-center justify-center rounded-xl bg-amber-50 text-amber-700 ring-1 ring-amber-100" aria-hidden="true">
                                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21 3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5" /></svg>
                                @if ($page->pendingRefundCount > 0)
                                    <span class="absolute -right-1 -top-1 flex h-4 min-w-[1rem] items-center justify-center rounded-full bg-amber-500 px-1 text-[10px] font-bold text-white">{{ $page->pendingRefundCount > 9 ? '9+' : $page->pendingRefundCount }}</span>
                                @endif
                            </span>
                            <span class="text-xs font-semibold text-slate-900">{{ __('dashboard.refund') }}</span>
                        </a>
                        <a x-data="adminWithdrawalsBadgeLive({{ $page->pendingWithdrawCount }})" href="{{ route('admin.withdrawals.index') }}" class="group relative flex flex-col items-center gap-2 rounded-2xl border border-slate-200 bg-white px-3 py-4 text-center shadow-sm transition hover:border-sky-200 hover:shadow-md">
                            <span class="relative flex h-10 w-10 items-center justify-center rounded-xl bg-sky-50 text-sky-700 ring-1 ring-sky-100" aria-hidden="true">
                                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z" /></svg>
                                <template x-if="count > 0">
                                    <span class="absolute -right-1 -top-1 flex h-4 min-w-[1rem] items-center justify-center rounded-full bg-slate-900 px-1 text-[10px] font-bold text-white" x-text="displayLabel"></span>
                                </template>
                            </span>
                            <span class="text-xs font-semibold text-slate-900">{{ __('dashboard.withdraw') }}</span>
                        </a>
                        <a href="{{ route('admin.muthowif.index') }}" class="group relative flex flex-col items-center gap-2 rounded-2xl border border-slate-200 bg-white px-3 py-4 text-center shadow-sm transition hover:border-violet-200 hover:shadow-md">
                            <span class="relative flex h-10 w-10 items-center justify-center rounded-xl bg-violet-50 text-violet-700 ring-1 ring-violet-100" aria-hidden="true">
                                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" /></svg>
                                @if ($page->pendingMuthowifCount > 0)
                                    <span class="absolute -right-1 -top-1 flex h-4 min-w-[1rem] items-center justify-center rounded-full bg-violet-600 px-1 text-[10px] font-bold text-white">{{ $page->pendingMuthowifCount > 9 ? '9+' : $page->pendingMuthowifCount }}</span>
                                @endif
                            </span>
                            <span class="text-xs font-semibold text-slate-900">{{ __('dashboard.verify') }}</span>
                        </a>
                        <a href="{{ route('admin.referrals.index') }}" class="group flex flex-col items-center gap-2 rounded-2xl border border-slate-200 bg-white px-3 py-4 text-center shadow-sm transition hover:border-fuchsia-200 hover:shadow-md">
                            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-fuchsia-50 text-fuchsia-700 ring-1 ring-fuchsia-100" aria-hidden="true">
                                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.09 9.09 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" /></svg>
                            </span>
                            <span class="text-xs font-semibold text-slate-900">{{ __('nav.referral_monitor') }}</span>
                        </a>
                        <a href="{{ route('admin.affiliates.index') }}" class="group flex flex-col items-center gap-2 rounded-2xl border border-slate-200 bg-white px-3 py-4 text-center shadow-sm transition hover:border-orange-200 hover:shadow-md">
                            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-orange-50 text-orange-700 ring-1 ring-orange-100" aria-hidden="true">
                                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244" /></svg>
                            </span>
                            <span class="text-xs font-semibold text-slate-900">Affiliate</span>
                        </a>
                        <a href="{{ route('admin.company_approval.index') }}" class="group flex flex-col items-center gap-2 rounded-2xl border border-slate-200 bg-white px-3 py-4 text-center shadow-sm transition hover:border-indigo-200 hover:shadow-md">
                            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-50 text-indigo-700 ring-1 ring-indigo-100" aria-hidden="true">
                                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 14.15v4.25c0 1.094-.787 2.036-1.872 2.18-2.087.277-4.216.42-6.378.42s-4.291-.143-6.378-.42c-1.085-.144-1.872-1.086-1.872-2.18v-4.25m16.5 0a2.18 2.18 0 0 0 .75-1.661V8.706c0-1.081-.768-2.015-1.837-2.175a48.114 48.114 0 0 0-3.413-.387m4.5 8.006c-.194.165-.42.295-.673.38A23.978 23.978 0 0 1 12 15.75c-2.648 0-5.195-.429-7.577-1.22a2.016 2.016 0 0 1-.673-.38m0 0A2.18 2.18 0 0 1 3 12.489V8.706c0-1.081.768-2.015 1.837-2.175a48.111 48.111 0 0 1 3.413-.387m7.5 0V5.25A2.25 2.25 0 0 0 13.5 3h-3a2.25 2.25 0 0 0-2.25 2.25v.894m7.5 0a48.667 48.667 0 0 0-7.5 0M12 12.75h.008v.008H12v-.008Z" /></svg>
                            </span>
                            <span class="text-xs font-semibold text-slate-900">Perusahaan</span>
                        </a>
                        <a href="{{ route('admin.users.index') }}" class="group flex flex-col items-center gap-2 rounded-2xl border border-slate-200 bg-white px-3 py-4 text-center shadow-sm transition hover:border-cyan-200 hover:shadow-md">
                            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-cyan-50 text-cyan-700 ring-1 ring-cyan-100" aria-hidden="true">
                                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.813-2.387M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" /></svg>
                            </span>
                            <span class="text-xs font-semibold text-slate-900">{{ __('dashboard.users') }}</span>
                        </a>
                        <a href="{{ route('admin.support-tickets.index') }}" class="group flex flex-col items-center gap-2 rounded-2xl border border-slate-200 bg-white px-3 py-4 text-center shadow-sm transition hover:border-teal-200 hover:shadow-md">
                            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-teal-50 text-teal-700 ring-1 ring-teal-100" aria-hidden="true">
                                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" /></svg>
                            </span>
                            <span class="text-xs font-semibold text-slate-900">{{ __('nav.support_tickets') }}</span>
                        </a>
                        <a href="{{ route('admin.settings.index') }}" class="group flex flex-col items-center gap-2 rounded-2xl border border-slate-200 bg-white px-3 py-4 text-center shadow-sm transition hover:border-brand-200 hover:shadow-md">
                            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-brand-50 text-brand-800 ring-1 ring-brand-100" aria-hidden="true">
                                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.343 3.94c.09-.542.56-.94 1.11-.94h1.093c.55 0 1.02.398 1.11.94l.149.894c.07.424.384.764.78.93.398.164.855.142 1.205-.108l.737-.527a1.125 1.125 0 0 1 1.45.12l.773.774c.39.389.44 1.002.12 1.45l-.527.737c-.25.35-.272.806-.107 1.204.165.397.505.71.93.78l.893.15c.543.09.94.56.94 1.109v1.094c0 .55-.397 1.02-.94 1.11l-.894.149c-.424.07-.764.383-.929.78-.165.398-.143.854.107 1.204l.527.738c.32.447.269 1.06-.12 1.45l-.774.773a1.125 1.125 0 0 1-1.449.12l-.738-.527c-.35-.25-.806-.272-1.203-.107-.397.165-.71.505-.781.929l-.149.894c-.09.542-.56.94-1.11.94h-1.093c-.55 0-1.019-.398-1.11-.94l-.148-.894c-.071-.424-.384-.764-.781-.93-.398-.164-.854-.142-1.204.108l-.738.527a1.125 1.125 0 0 1-1.45-.12l-.773-.774a1.125 1.125 0 0 1-.12-1.45l.527-.737c.25-.35.273-.806.108-1.204-.165-.397-.505-.71-.93-.78l-.894-.15c-.542-.09-.94-.56-.94-1.109v-1.094c0-.55.398-1.02.94-1.11l.894-.149c.424-.07.765-.383.93-.78.165-.397.143-.854-.107-1.204l-.527-.738a1.125 1.125 0 0 1 .12-1.45l.773-.773a1.125 1.125 0 0 1 1.45-.12l.738.527c.35.25.807.272 1.204.107.397-.165.71-.505.78-.929l.15-.894Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
                            </span>
                            <span class="text-xs font-semibold text-slate-900">{{ __('nav.admin_settings') }}</span>
                        </a>
                        <a href="{{ route('log-viewer.index') }}" class="group col-span-2 flex items-center justify-center gap-2 rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-3 py-3.5 text-center transition hover:border-slate-400 hover:bg-white sm:col-span-1 lg:col-span-2 xl:col-span-1">
                            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-200/80 text-slate-700" aria-hidden="true">
                                <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25H12" /></svg>
                            </span>
                            <span class="text-xs font-semibold text-slate-800">{{ __('dashboard.logs_webhook') }}</span>
                        </a>
                    </div>
                </div>

                {{-- Tindakan admin tertunda --}}
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="text-base font-bold text-slate-900">{{ __('dashboard.pending_actions_title') }}</h2>
                    <p class="mt-1 text-sm text-slate-600">{{ __('dashboard.pending_actions_sub') }}</p>
                    <div class="mt-4 space-y-2.5">
                        @if ($page->pendingMuthowifCount > 0)
                            <a href="{{ route('admin.muthowif.index', ['status' => 'pending']) }}" class="group flex items-center gap-3 rounded-2xl bg-violet-50 px-4 py-3 ring-1 ring-violet-100 transition hover:bg-violet-100">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-white text-violet-700 ring-1 ring-violet-200/70">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" /></svg>
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="block text-sm font-semibold text-violet-950">{{ __('dashboard.pending_admin_notifications_muthowif', ['count' => $page->pendingMuthowifCount]) }}</span>
                                    <span class="block text-xs text-violet-700/80">{{ __('dashboard.pending_waiting_review') }}</span>
                                </span>
                                <svg class="h-4 w-4 shrink-0 text-violet-600 transition group-hover:translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
                            </a>
                        @endif
                        @if ($page->pendingWithdrawCount > 0)
                            <a href="{{ route('admin.withdrawals.index') }}" class="group flex items-center gap-3 rounded-2xl bg-sky-50 px-4 py-3 ring-1 ring-sky-100 transition hover:bg-sky-100">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-white text-sky-700 ring-1 ring-sky-200/70">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z" /></svg>
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="block text-sm font-semibold text-sky-950">{{ __('dashboard.pending_admin_notifications_withdraw', ['count' => $page->pendingWithdrawCount]) }}</span>
                                    <span class="block text-xs text-sky-700/80">{{ __('dashboard.pending_waiting_review') }}</span>
                                </span>
                                <svg class="h-4 w-4 shrink-0 text-sky-600 transition group-hover:translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
                            </a>
                        @endif
                        @if ($page->pendingRefundCount > 0)
                            <a href="{{ route('admin.refunds.index') }}" class="group flex items-center gap-3 rounded-2xl bg-amber-50 px-4 py-3 ring-1 ring-amber-100 transition hover:bg-amber-100">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-white text-amber-700 ring-1 ring-amber-200/70">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21 3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5" /></svg>
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="block text-sm font-semibold text-amber-950">{{ __('dashboard.pending_admin_notifications_refund', ['count' => $page->pendingRefundCount]) }}</span>
                                    <span class="block text-xs text-amber-700/80">{{ __('dashboard.pending_waiting_review') }}</span>
                                </span>
                                <svg class="h-4 w-4 shrink-0 text-amber-600 transition group-hover:translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
                            </a>
                        @endif
                        @if ($page->pendingTotal === 0)
                            <div class="flex items-center gap-3 rounded-2xl bg-emerald-50 px-4 py-3 ring-1 ring-emerald-100">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-white text-emerald-600 ring-1 ring-emerald-200/70">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                </span>
                                <span class="text-sm font-medium text-emerald-900">{{ __('dashboard.pending_actions_none') }}</span>
                            </div>
                        @endif
                    </div>
                </div>
            </aside>
        </div>

        {{-- ── Baris bawah ─────────────────────────────────────────────── --}}
        <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
            <div class="rounded-2xl border border-amber-200/80 bg-amber-50/60 p-5 shadow-sm">
                <div class="flex items-start gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-700 ring-1 ring-amber-200/80">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487 18.549 2.8a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" /></svg>
                    </span>
                    <div class="min-w-0">
                        <h3 class="font-semibold text-slate-900">{{ __('dashboard.next_steps') }}</h3>
                        <p class="mt-1.5 text-sm leading-relaxed text-slate-600">
                            {!! __('dashboard.next_steps_admin', ['menu' => '<strong class="text-slate-800">'.e(__('dashboard.next_steps_admin_menu')).'</strong>']) !!}
                        </p>
                        <a href="{{ route('admin.muthowif.index', ['status' => 'pending']) }}" class="mt-3 inline-flex items-center gap-1.5 rounded-xl bg-white px-4 py-2 text-xs font-semibold text-slate-800 shadow-sm ring-1 ring-slate-200 transition hover:bg-slate-50">
                            {{ __('dashboard.next_steps_admin_cta') }}
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" /></svg>
                        </a>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-start gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 ring-1 ring-emerald-100">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                    </span>
                    <div>
                        <h3 class="font-semibold text-slate-900">{{ __('dashboard.system_status_title') }}</h3>
                        <p class="mt-1.5 text-sm text-slate-600">{{ __('dashboard.system_status_ok') }}</p>
                        <p class="mt-3 inline-flex items-center gap-1.5 rounded-lg bg-emerald-50 px-2.5 py-1 text-[11px] font-medium text-emerald-800 ring-1 ring-emerald-100">
                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                            {{ __('dashboard.system_last_updated') }}: {{ $page->now->timezone(config('app.timezone'))->translatedFormat('d M Y, H:i') }} {{ config('app.timezone') === 'Asia/Jakarta' ? 'WIB' : '' }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-start gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-600 ring-1 ring-blue-100">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                    </span>
                    <div class="min-w-0 flex-1">
                        <h3 class="font-semibold text-slate-900">{{ __('dashboard.today_summary_title') }}</h3>
                        <dl class="mt-3 space-y-2 text-sm">
                            <div class="flex items-center justify-between gap-2">
                                <dt class="inline-flex items-center gap-2 text-slate-600"><span class="h-2 w-2 rounded-full bg-blue-500"></span>{{ __('dashboard.today_new_orders') }}</dt>
                                <dd class="font-bold tabular-nums text-slate-900">{{ $page->newBookingsToday }}</dd>
                            </div>
                            <div class="flex items-center justify-between gap-2">
                                <dt class="inline-flex items-center gap-2 text-slate-600"><span class="h-2 w-2 rounded-full bg-emerald-500"></span>{{ __('dashboard.today_settlements') }}</dt>
                                <dd class="font-bold tabular-nums text-slate-900">{{ $page->settlementsToday }}</dd>
                            </div>
                            <div class="flex items-center justify-between gap-2">
                                <dt class="inline-flex items-center gap-2 text-slate-600"><span class="h-2 w-2 rounded-full bg-amber-500"></span>{{ __('dashboard.today_refunds') }}</dt>
                                <dd class="font-bold tabular-nums text-slate-900">{{ $page->refundsToday }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </x-page-container>
</div>
