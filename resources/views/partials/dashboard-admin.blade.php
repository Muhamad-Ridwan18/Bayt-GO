@php
    use App\Enums\MuthowifVerificationStatus;
    use App\Models\BookingPayment;
    use App\Models\MuthowifBooking;
    use App\Models\MuthowifProfile;
    use App\Support\AdminFinanceSummary;
    use App\Support\IndonesianNumber;
    use Illuminate\Support\Carbon;

    $fmt = fn (float|int $n) => IndonesianNumber::formatThousands((string) (int) round((float) $n));

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

    $now = Carbon::now();
    $thisMonthStart = $now->copy()->startOfMonth();
    $lastMonthStart = $now->copy()->subMonth()->startOfMonth();
    $lastMonthEnd = $now->copy()->subMonth()->endOfMonth();

    $platformFeePrev = AdminFinanceSummary::platformFeePaymentsSumBetween($lastMonthStart, $lastMonthEnd);
    $platformFeeThis = AdminFinanceSummary::platformFeePaymentsSumBetween($thisMonthStart, $now);
    $grossThisMonth = AdminFinanceSummary::grossVolumeBetween($thisMonthStart, $now);
    $grossPrevMonth = AdminFinanceSummary::grossVolumeBetween($lastMonthStart, $lastMonthEnd);

    $settledThisMonth = BookingPayment::query()
        ->whereIn('status', ['settlement', 'capture'])
        ->whereNotNull('settled_at')
        ->whereBetween('settled_at', [$thisMonthStart, $now->copy()->endOfDay()])
        ->count();
    $settledLastMonth = BookingPayment::query()
        ->whereIn('status', ['settlement', 'capture'])
        ->whereNotNull('settled_at')
        ->whereBetween('settled_at', [$lastMonthStart, $lastMonthEnd->copy()->endOfDay()])
        ->count();

    $totalBookings = (int) MuthowifBooking::query()->count();
    $bookingsThisMonth = (int) MuthowifBooking::query()->where('created_at', '>=', $thisMonthStart)->count();
    $bookingsLastMonth = (int) MuthowifBooking::query()
        ->whereBetween('created_at', [$lastMonthStart, $lastMonthEnd->copy()->endOfDay()])
        ->count();

    $activeMuthowif = (int) MuthowifProfile::query()
        ->where('verification_status', MuthowifVerificationStatus::Approved)
        ->count();

    $pendingWithdrawCount = (int) \App\Models\MuthowifWithdrawal::query()
        ->where('status', 'pending_approval')
        ->count();
    $pendingRefundCount = (int) \App\Models\BookingRefundRequest::query()
        ->where('status', \App\Enums\BookingChangeRequestStatus::Pending)
        ->count();
    $pendingMuthowifCount = (int) MuthowifProfile::query()
        ->where('verification_status', MuthowifVerificationStatus::Pending)
        ->count();

    $chart = AdminFinanceSummary::chartDailySeriesForMonth($now);

    $recentPayments = BookingPayment::query()
        ->whereIn('status', ['settlement', 'capture'])
        ->orderByDesc('settled_at')
        ->limit(8)
        ->with(['muthowifBooking:id,booking_code'])
        ->get(['id', 'order_id', 'gross_amount', 'status', 'settled_at', 'muthowif_booking_id']);

    $momPct = function (float $cur, float $prev): ?int {
        if ($prev <= 0 && $cur <= 0) {
            return null;
        }
        if ($prev <= 0) {
            return $cur > 0 ? 100 : 0;
        }

        return (int) round(100 * ($cur - $prev) / $prev);
    };

    $pctPlatform = $momPct($platformFeeThis, $platformFeePrev);
    $pctGross = $momPct((float) $grossThisMonth, (float) $grossPrevMonth);
    $pctSettled = $momPct((float) $settledThisMonth, (float) $settledLastMonth);
    $pctBookings = $momPct((float) $bookingsThisMonth, (float) $bookingsLastMonth);

    $netChart = max(0, $chart['total_gross'] - $chart['total_refunds']);

    $maxY = max(1, ...$chart['gross'], ...$chart['refunds']);
    $chartW = 100;
    $chartH = 44;
    $n = max(1, count($chart['gross']));
    $linePoints = function (array $series) use ($chartW, $chartH, $maxY, $n): string {
        $pts = [];
        $count = count($series);
        if ($count === 0) {
            return '0,'.$chartH;
        }
        if ($count === 1) {
            $y = $chartH - ((int) $series[0] / $maxY) * $chartH;

            return '0,'.round($y, 2).' '.$chartW.','.round($y, 2);
        }
        foreach ($series as $i => $v) {
            $x = ($i / ($count - 1)) * $chartW;
            $y = $chartH - (((int) $v) / $maxY) * $chartH;
            $pts[] = round($x, 2).','.round($y, 2);
        }

        return implode(' ', $pts);
    };
    $grossPoly = $linePoints($chart['gross']);
    $refundPoly = $linePoints($chart['refunds']);
@endphp

<div class="space-y-0 pb-10 sm:pb-12">
    <section class="relative left-1/2 w-screen max-w-[100vw] -translate-x-1/2 overflow-hidden bg-welcomeCanvas min-h-[14rem] sm:min-h-[16rem] lg:min-h-[18rem]">
        <div class="pointer-events-none absolute inset-0 z-0" aria-hidden="true">
            <img
                src="{{ $welcomeHeroBg }}"
                alt=""
                class="h-full min-h-[14rem] w-full object-cover object-[70%_30%] sm:min-h-[16rem] sm:object-[72%_28%] lg:min-h-[18rem] lg:object-[75%_26%]"
                loading="eager"
                decoding="async"
            />
        </div>
        <div class="pointer-events-none absolute inset-0 z-[1] bg-gradient-to-b from-welcomeCanvas via-welcomeCanvas/88 to-welcomeCanvas/30 sm:hidden" aria-hidden="true"></div>
        <div class="pointer-events-none absolute inset-0 z-[1] hidden bg-gradient-to-r from-welcomeCanvas from-[40%] via-welcomeCanvas/90 via-[62%] to-welcomeCanvas/20 sm:block lg:from-[44%] lg:via-[64%]" aria-hidden="true"></div>
        <div class="pointer-events-none absolute inset-x-0 bottom-0 z-[1] h-16 bg-gradient-to-t from-welcomeCanvas via-welcomeCanvas/50 to-transparent sm:h-20" aria-hidden="true"></div>

        <x-page-container class="relative z-10 pb-8 pt-10 sm:pb-10 sm:pt-12 lg:pb-11 lg:pt-14">
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-800/90">{{ __('dashboard.admin_label') }}</p>
            <h1 class="mt-2 max-w-2xl text-2xl font-bold leading-tight tracking-tight text-slate-900 sm:text-3xl lg:text-4xl">
                {!! __('dashboard.admin_hero_welcome', ['name' => e(Auth::user()->name)]) !!}
            </h1>
            <p class="mt-3 max-w-xl text-sm leading-relaxed text-slate-800 sm:text-base">
                {{ __('dashboard.admin_hero_lead') }}
            </p>
        </x-page-container>
    </section>

    @if ($pendingMuthowifCount > 0 || $pendingWithdrawCount > 0 || $pendingRefundCount > 0)
        <x-page-container>
            <div class="overflow-hidden rounded-3xl border border-slate-200/90 bg-white p-4 shadow-sm sm:p-5">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex min-w-0 items-start gap-4">
                        <div class="flex h-14 w-14 items-center justify-center rounded-3xl bg-slate-100 text-slate-900 shadow-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="h-7 w-7" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 8v4" />
                                <path d="M12 16h.01" />
                                <path d="M4 6h16M4 18h16" />
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-slate-900">{{ __('dashboard.pending_admin_notifications_title') }}</p>
                            <p class="mt-1 text-sm leading-relaxed text-slate-600">{{ __('dashboard.pending_admin_notifications_body') }}</p>
                            <div class="mt-3 flex flex-wrap gap-2 text-sm">
                                @if ($pendingMuthowifCount > 0)
                                    <span class="inline-flex items-center rounded-full bg-violet-50 px-3 py-1 text-violet-700">{{ __('dashboard.pending_admin_notifications_muthowif', ['count' => $pendingMuthowifCount]) }}</span>
                                @endif
                                @if ($pendingWithdrawCount > 0)
                                    <span class="inline-flex items-center rounded-full bg-sky-50 px-3 py-1 text-sky-700">{{ __('dashboard.pending_admin_notifications_withdraw', ['count' => $pendingWithdrawCount]) }}</span>
                                @endif
                                @if ($pendingRefundCount > 0)
                                    <span class="inline-flex items-center rounded-full bg-amber-50 px-3 py-1 text-amber-700">{{ __('dashboard.pending_admin_notifications_refund', ['count' => $pendingRefundCount]) }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        @if ($pendingMuthowifCount > 0)
                            <a href="{{ route('admin.muthowif.index', ['status' => 'pending']) }}" class="inline-flex items-center justify-center rounded-2xl bg-violet-700 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-violet-800">
                                {{ __('dashboard.pending_admin_notifications_cta_muthowif') }}
                            </a>
                        @endif
                        @if ($pendingWithdrawCount > 0)
                            <a href="{{ route('admin.withdrawals.index') }}" class="inline-flex items-center justify-center rounded-2xl bg-sky-700 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-sky-800">
                                {{ __('dashboard.pending_admin_notifications_cta_withdraw') }}
                            </a>
                        @endif
                        @if ($pendingRefundCount > 0)
                            <a href="{{ route('admin.refunds.index') }}" class="inline-flex items-center justify-center rounded-2xl bg-amber-700 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-amber-800">
                                {{ __('dashboard.pending_admin_notifications_cta_refund') }}
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </x-page-container>
    @endif

    <x-page-container class="ui-stack">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
            @foreach ([
                [
                    'label' => __('dashboard.stat_platform_fee'),
                    'value' => 'Rp '.$fmt($platformFeeThis),
                    'pct' => $pctPlatform,
                    'wrap' => 'border-emerald-200/80 bg-white ring-emerald-100/70',
                    'icon' => 'bg-emerald-100 text-emerald-700 ring-emerald-200/80',
                ],
                [
                    'label' => __('dashboard.stat_gross_volume'),
                    'value' => 'Rp '.$fmt($grossThisMonth),
                    'pct' => $pctGross,
                    'wrap' => 'border-violet-200/80 bg-white ring-violet-100/60',
                    'icon' => 'bg-violet-100 text-violet-700 ring-violet-200/70',
                ],
                [
                    'label' => __('dashboard.stat_settlements_period'),
                    'value' => (string) $settledThisMonth,
                    'pct' => $pctSettled,
                    'wrap' => 'border-amber-200/80 bg-white ring-amber-100/70',
                    'icon' => 'bg-amber-100 text-amber-800 ring-amber-200/80',
                ],
                [
                    'label' => __('dashboard.stat_total_bookings'),
                    'value' => (string) $totalBookings,
                    'pct' => $pctBookings,
                    'wrap' => 'border-sky-200/80 bg-white ring-sky-100/60',
                    'icon' => 'bg-sky-100 text-sky-800 ring-sky-200/70',
                ],
                [
                    'label' => __('dashboard.stat_active_muthowif'),
                    'value' => (string) $activeMuthowif,
                    'pct' => null,
                    'wrap' => 'border-teal-200/80 bg-white ring-teal-100/60',
                    'icon' => 'bg-teal-100 text-teal-800 ring-teal-200/70',
                ],
            ] as $card)
                <div class="rounded-2xl border p-5 shadow-sm ring-1 {{ $card['wrap'] }}">
                    <div class="flex items-start justify-between gap-2">
                        <p class="text-[11px] font-bold uppercase tracking-wide text-slate-500">{{ $card['label'] }}</p>
                        <span class="shrink-0 rounded-xl p-2 ring-1 {{ $card['icon'] }}" aria-hidden="true">
                            <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </span>
                    </div>
                    <p class="mt-3 text-2xl font-bold tabular-nums text-slate-900">{{ $card['value'] }}</p>
                    @if ($card['pct'] !== null)
                        <p class="mt-2 text-xs font-semibold {{ $card['pct'] >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                            {{ $card['pct'] >= 0 ? '+' : '' }}{{ $card['pct'] }}% {{ __('dashboard.stat_vs_last_month') }}
                        </p>
                    @else
                        <p class="mt-2 text-xs font-medium text-slate-400">{{ __('dashboard.stat_live_snapshot') }}</p>
                    @endif
                </div>
            @endforeach
        </div>

        <div class="grid grid-cols-1 items-start gap-8 lg:grid-cols-12">
            <div class="min-w-0 ui-stack-compact lg:col-span-8">
                <div class="overflow-hidden rounded-3xl border border-slate-200/90 bg-white shadow-md shadow-slate-900/5 ring-1 ring-slate-100/90">
                    <div class="flex flex-col gap-4 border-b border-slate-100 bg-slate-50/90 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h2 class="text-base font-bold text-slate-900">{{ __('dashboard.txn_overview_title') }}</h2>
                            <p class="mt-0.5 text-xs text-slate-500">{{ $chart['month_label'] }}</p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <span class="inline-flex items-center rounded-full bg-white px-3 py-1 text-xs font-semibold text-slate-700 ring-1 ring-slate-200/90">{{ __('dashboard.txn_period_this_month') }}</span>
                            <span class="inline-flex items-center rounded-full bg-white px-3 py-1 text-xs font-semibold text-slate-600 ring-1 ring-slate-200/90">{{ __('dashboard.txn_period_vs_prev') }}</span>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-6 p-5 lg:grid-cols-[1fr_minmax(11rem,14rem)]">
                        <div class="min-w-0">
                            <div class="relative aspect-[21/9] w-full min-h-[11rem] sm:min-h-[12rem]">
                                <svg class="h-full w-full" viewBox="0 0 {{ $chartW }} {{ $chartH }}" preserveAspectRatio="none" aria-hidden="true">
                                    <defs>
                                        <linearGradient id="adminGrossFill" x1="0" y1="0" x2="0" y2="1">
                                            <stop offset="0%" stop-color="rgb(16 185 129)" stop-opacity="0.25" />
                                            <stop offset="100%" stop-color="rgb(16 185 129)" stop-opacity="0" />
                                        </linearGradient>
                                    </defs>
                                    <line x1="0" y1="{{ $chartH }}" x2="{{ $chartW }}" y2="{{ $chartH }}" stroke="rgb(226 232 240)" stroke-width="0.4" />
                                    <polyline fill="none" stroke="rgb(16 185 129)" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" points="{{ $grossPoly }}" />
                                    <polyline fill="none" stroke="rgb(245 158 11)" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" points="{{ $refundPoly }}" />
                                </svg>
                                <div class="mt-2 flex flex-wrap justify-center gap-4 text-xs font-medium text-slate-600">
                                    <span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-emerald-500"></span>{{ __('dashboard.txn_chart_gross') }}</span>
                                    <span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-amber-500"></span>{{ __('dashboard.txn_chart_refunds') }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="space-y-4 rounded-2xl border border-slate-100 bg-slate-50/80 p-4 ring-1 ring-slate-100/80">
                            <div>
                                <p class="text-[11px] font-bold uppercase tracking-wide text-slate-500">{{ __('dashboard.txn_chart_gross') }}</p>
                                <p class="mt-1 text-lg font-bold text-slate-900">Rp {{ $fmt($chart['total_gross']) }}</p>
                                <p class="mt-0.5 text-xs text-emerald-600">{{ __('dashboard.txn_in_month') }}</p>
                            </div>
                            <div>
                                <p class="text-[11px] font-bold uppercase tracking-wide text-slate-500">{{ __('dashboard.txn_chart_refunds') }}</p>
                                <p class="mt-1 text-lg font-bold text-slate-900">Rp {{ $fmt($chart['total_refunds']) }}</p>
                            </div>
                            <div class="border-t border-slate-200/80 pt-4">
                                <p class="text-[11px] font-bold uppercase tracking-wide text-slate-500">{{ __('dashboard.txn_chart_net') }}</p>
                                <p class="mt-1 text-lg font-bold text-brand-800">Rp {{ $fmt($netChart) }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="overflow-hidden rounded-3xl border border-slate-200/90 bg-white shadow-md shadow-slate-900/5 ring-1 ring-slate-100/90">
                    <div class="flex items-center justify-between gap-3 border-b border-slate-100 bg-slate-50/90 px-5 py-4">
                        <h2 class="text-base font-bold text-slate-900">{{ __('dashboard.recent_txn_title') }}</h2>
                        <a href="{{ route('admin.finance.index') }}" class="text-sm font-semibold text-brand-700 hover:text-brand-800">{{ __('dashboard.view_all') }}</a>
                    </div>
                    @if ($recentPayments->isEmpty())
                        <div class="flex flex-col items-center justify-center gap-3 px-6 py-14 text-center">
                            <span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400 ring-1 ring-slate-200/80" aria-hidden="true">
                                <svg class="h-7 w-7" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" /></svg>
                            </span>
                            <p class="text-sm text-slate-600">{{ __('dashboard.recent_txn_empty') }}</p>
                        </div>
                    @else
                        <div class="w-full overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead>
                                    <tr class="border-b border-slate-200 bg-white text-left text-xs font-bold uppercase tracking-wide text-slate-500">
                                        <th class="px-4 py-3">{{ __('dashboard.recent_col_id') }}</th>
                                        <th class="px-4 py-3">{{ __('dashboard.recent_col_type') }}</th>
                                        <th class="px-4 py-3">{{ __('dashboard.recent_col_reference') }}</th>
                                        <th class="px-4 py-3 text-right">{{ __('dashboard.recent_col_amount') }}</th>
                                        <th class="px-4 py-3">{{ __('dashboard.recent_col_status') }}</th>
                                        <th class="px-4 py-3">{{ __('dashboard.recent_col_date') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @foreach ($recentPayments as $p)
                                        <tr class="hover:bg-slate-50/80">
                                            <td class="whitespace-nowrap px-4 py-3 font-mono text-xs text-slate-600">{{ \Illuminate\Support\Str::limit((string) $p->id, 8, '…') }}</td>
                                            <td class="px-4 py-3">
                                                <span class="inline-flex rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-semibold text-emerald-900 ring-1 ring-emerald-200/80">{{ __('admin.finance.txn_types.order') }}</span>
                                            </td>
                                            <td class="max-w-[10rem] truncate px-4 py-3 font-mono text-xs text-slate-700" title="{{ $p->order_id }}">{{ $p->muthowifBooking?->booking_code ?? $p->order_id ?? '—' }}</td>
                                            <td class="whitespace-nowrap px-4 py-3 text-right font-semibold text-slate-900">Rp {{ $fmt($p->gross_amount) }}</td>
                                            <td class="whitespace-nowrap px-4 py-3 text-slate-700">{{ $p->status }}</td>
                                            <td class="whitespace-nowrap px-4 py-3 text-slate-600">{{ $p->settled_at?->timezone(config('app.timezone'))->format('d/m/Y H:i') ?? '—' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

            <aside class="space-y-5 lg:col-span-4">
                <div class="rounded-3xl border border-slate-200/90 bg-white p-5 shadow-md shadow-slate-900/5 ring-1 ring-slate-100/90">
                    <h2 class="text-base font-bold text-slate-900">{{ __('dashboard.admin_quick_title') }}</h2>
                    <p class="mt-1 text-sm text-slate-600">{{ __('dashboard.admin_quick_sub') }}</p>
                    <p class="mt-2 text-xs text-slate-500">
                        {!! __('dashboard.admin_pending_counts', ['withdraw' => '<span class="font-semibold text-slate-800">'.$pendingWithdrawCount.'</span>', 'refund' => '<span class="font-semibold text-slate-800">'.$pendingRefundCount.'</span>']) !!}
                    </p>
                    <div class="mt-4 grid grid-cols-2 gap-2.5">
                        <a href="{{ route('admin.finance.index') }}" class="group flex flex-col items-center gap-2 rounded-2xl border border-slate-200/90 bg-slate-50 px-3 py-4 text-center shadow-sm transition hover:border-brand-200 hover:bg-white hover:shadow-md">
                            <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-100 text-emerald-800 ring-1 ring-emerald-200/80 transition group-hover:bg-emerald-200" aria-hidden="true">
                                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m0 0H21" /></svg>
                            </span>
                            <span class="text-xs font-semibold text-slate-900">{{ __('dashboard.finance') }}</span>
                        </a>
                        <a href="{{ route('admin.service_monitor.index') }}" class="group flex flex-col items-center gap-2 rounded-2xl border border-red-200/90 bg-red-50/80 px-3 py-4 text-center shadow-sm transition hover:border-red-300 hover:shadow-md">
                            <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-red-100 text-red-900 ring-1 ring-red-200/80" aria-hidden="true">
                                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                            </span>
                            <span class="text-xs font-semibold text-slate-900">{{ __('nav.service_monitor') }}</span>
                        </a>
                        <a href="{{ route('admin.refunds.index') }}" class="group relative flex flex-col items-center gap-2 rounded-2xl border border-amber-200/90 bg-amber-50/90 px-3 py-4 text-center transition hover:border-amber-300 hover:shadow-md">
                            <span class="relative flex h-11 w-11 items-center justify-center rounded-xl bg-amber-100 text-amber-900 ring-1 ring-amber-200/80" aria-hidden="true">
                                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5" /></svg>
                                @if ($pendingRefundCount > 0)
                                    <span class="absolute -right-1 -top-1 flex h-4 min-w-[1rem] items-center justify-center rounded-full bg-amber-500 px-1 text-[10px] font-bold text-white">{{ $pendingRefundCount > 9 ? '9+' : $pendingRefundCount }}</span>
                                @endif
                            </span>
                            <span class="text-xs font-semibold text-slate-900">{{ __('dashboard.refund') }}</span>
                        </a>
                        <a x-data="adminWithdrawalsBadgeLive({{ $pendingWithdrawCount }})" href="{{ route('admin.withdrawals.index') }}" class="group relative flex flex-col items-center gap-2 rounded-2xl border border-slate-200/90 bg-white px-3 py-4 text-center shadow-sm transition hover:border-slate-300 hover:shadow-md">
                            <span class="relative flex h-11 w-11 items-center justify-center rounded-xl bg-sky-100 text-sky-900 ring-1 ring-sky-200/70" aria-hidden="true">
                                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z" /></svg>
                                <template x-if="count > 0">
                                    <span class="absolute -right-1 -top-1 flex h-4 min-w-[1rem] items-center justify-center rounded-full bg-slate-900 px-1 text-[10px] font-bold text-white" x-text="displayLabel"></span>
                                </template>
                            </span>
                            <span class="text-xs font-semibold text-slate-900">{{ __('dashboard.withdraw') }}</span>
                        </a>
                        <a href="{{ route('admin.muthowif.index') }}" class="group flex flex-col items-center gap-2 rounded-2xl border border-slate-200/90 bg-white px-3 py-4 text-center shadow-sm transition hover:border-violet-200 hover:shadow-md">
                            <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-violet-100 text-violet-800 ring-1 ring-violet-200/70" aria-hidden="true">
                                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" /></svg>
                            </span>
                            <span class="text-xs font-semibold text-slate-900">{{ __('dashboard.verify') }}</span>
                        </a>
                        <a href="{{ route('admin.referrals.index') }}" class="group flex flex-col items-center gap-2 rounded-2xl border border-slate-200/90 bg-white px-3 py-4 text-center shadow-sm transition hover:border-fuchsia-200 hover:shadow-md">
                            <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-fuchsia-100 text-fuchsia-800 ring-1 ring-fuchsia-200/70" aria-hidden="true">
                                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.09 9.09 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" /></svg>
                            </span>
                            <span class="text-xs font-semibold text-slate-900">{{ __('nav.referral_monitor') }}</span>
                        </a>
                        <a href="{{ route('admin.company_approval.index') }}" class="group flex flex-col items-center gap-2 rounded-2xl border border-slate-200/90 bg-white px-3 py-4 text-center shadow-sm transition hover:border-indigo-200 hover:shadow-md">
                            <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-indigo-100 text-indigo-800 ring-1 ring-indigo-200/70" aria-hidden="true">
                                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 14.15v4.25c0 1.094-.787 2.036-1.872 2.18-2.087.277-4.216.42-6.378.42s-4.291-.143-6.378-.42c-1.085-.144-1.872-1.086-1.872-2.18v-4.25m16.5 0a2.18 2.18 0 00.75-1.661V8.706c0-1.081-.768-2.015-1.837-2.175a48.114 48.114 0 00-3.413-.387m4.5 8.006c-.194.165-.42.295-.673.38A23.978 23.978 0 0112 15.75c-2.648 0-5.195-.429-7.577-1.22a2.016 2.016 0 01-.673-.38m0 0A2.18 2.18 0 013 12.489V8.706c0-1.081.768-2.015 1.837-2.175a48.111 48.111 0 013.413-.387m7.5 0V5.25A2.25 2.25 0 0013.5 3h-3a2.25 2.25 0 00-2.25 2.25v.894m7.5 0a48.667 48.667 0 00-7.5 0M12 12.75h.008v.008H12v-.008z" /></svg>
                            </span>
                            <span class="text-xs font-semibold text-slate-900">Perusahaan</span>
                        </a>
                        <a href="{{ route('admin.users.index') }}" class="group flex flex-col items-center gap-2 rounded-2xl border border-slate-200/90 bg-white px-3 py-4 text-center shadow-sm transition hover:border-cyan-200 hover:shadow-md">
                            <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-cyan-100 text-cyan-900 ring-1 ring-cyan-200/70" aria-hidden="true">
                                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.813-2.387M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" /></svg>
                            </span>
                            <span class="text-xs font-semibold text-slate-900">{{ __('dashboard.users') }}</span>
                        </a>
                        <a href="{{ route('admin.support-tickets.index') }}" class="group flex flex-col items-center gap-2 rounded-2xl border border-slate-200/90 bg-white px-3 py-4 text-center shadow-sm transition hover:border-teal-200 hover:shadow-md">
                            <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-teal-100 text-teal-900 ring-1 ring-teal-200/70" aria-hidden="true">
                                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" /></svg>
                            </span>
                            <span class="text-xs font-semibold text-slate-900">{{ __('nav.support_tickets') }}</span>
                        </a>
                        <a href="{{ route('admin.settings.index') }}" class="group flex flex-col items-center gap-2 rounded-2xl border border-slate-200/90 bg-white px-3 py-4 text-center shadow-sm transition hover:border-brand-200 hover:shadow-md">
                            <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-brand-100 text-brand-900 ring-1 ring-brand-200/70" aria-hidden="true">
                                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.343 3.94c.09-.542.56-.94 1.11-.94h1.093c.55 0 1.02.398 1.11.94l.149.894c.07.424.384.764.78.93.398.164.855.142 1.205-.108l.737-.527a1.125 1.125 0 011.45.12l.773.774c.39.389.44 1.002.12 1.45l-.527.737c-.25.35-.272.806-.107 1.204.165.397.505.71.93.78l.893.15c.543.09.94.56.94 1.109v1.094c0 .55-.397 1.02-.94 1.11l-.894.149c-.424.07-.764.383-.929.78-.165.398-.143.854.107 1.204l.527.738c.32.447.269 1.06-.12 1.45l-.774.773a1.125 1.125 0 01-1.449.12l-.738-.527c-.35-.25-.806-.272-1.203-.107-.397.165-.71.505-.781.929l-.149.894c-.09.542-.56.94-1.11.94h-1.093c-.55 0-1.019-.398-1.11-.94l-.148-.894c-.071-.424-.384-.764-.781-.93-.398-.164-.854-.142-1.204.108l-.738.527a1.125 1.125 0 01-1.45-.12l-.773-.774a1.125 1.125 0 01-.12-1.45l.527-.737c.25-.35.273-.806.108-1.204-.165-.397-.505-.71-.93-.78l-.894-.15c-.542-.09-.94-.56-.94-1.109v-1.094c0-.55.398-1.02.94-1.11l.894-.149c.424-.07.765-.383.93-.78.165-.397.143-.854-.107-1.204l-.527-.738a1.125 1.125 0 01.12-1.45l.773-.773a1.125 1.125 0 011.45-.12l.738.527c.35.25.807.272 1.204.107.397-.165.71-.505.78-.929l.15-.894z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                            </span>
                            <span class="text-xs font-semibold text-slate-900">{{ __('nav.admin_settings') }}</span>
                        </a>
                        <a href="{{ route('log-viewer.index') }}" class="group flex flex-col items-center gap-2 rounded-2xl border border-dashed border-slate-300/90 bg-slate-50 px-3 py-4 text-center transition hover:border-slate-400 hover:bg-white">
                            <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-slate-200/80 text-slate-700" aria-hidden="true">
                                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25H12" /></svg>
                            </span>
                            <span class="text-xs font-semibold text-slate-800">{{ __('dashboard.logs_webhook') }}</span>
                        </a>
                    </div>
                </div>
            </aside>
        </div>

        <div class="mt-10 border-t border-slate-200/70 pt-8">
            @include('partials.dashboard-next-profile-row')
        </div>
    </x-page-container>
</div>
