@php
    use App\Support\IndonesianNumber;

    $fmt = fn (float|int $n) => IndonesianNumber::formatThousands((string) (int) round((float) $n));

    $trendChip = function (?float $pct) {
        if ($pct === null) {
            return null;
        }

        $up = $pct >= 0;

        return [
            'up' => $up,
            'label' => ($up ? '↑ ' : '↓ ').number_format(abs($pct), 1, ',', '.').'%',
            'class' => $up
                ? 'bg-emerald-50 text-emerald-700 ring-emerald-200/70'
                : 'bg-red-50 text-red-700 ring-red-200/70',
        ];
    };

    // ── Line chart (7 hari) ──────────────────────────────────────────────
    $chartW = 100;
    $chartH = 40;
    $maxY = max(1, ...$chart['gross'], ...$chart['fee'], ...$chart['affiliate']);
    $n = max(1, count($chart['gross']));
    $linePoints = function (array $series) use ($chartW, $chartH, $maxY): string {
        $count = count($series);
        if ($count === 0) {
            return '0,'.$chartH;
        }
        if ($count === 1) {
            $y = $chartH - ((float) $series[0] / $maxY) * $chartH;

            return '0,'.round($y, 2).' '.$chartW.','.round($y, 2);
        }
        $pts = [];
        foreach ($series as $i => $v) {
            $x = ($i / ($count - 1)) * $chartW;
            $y = $chartH - (((float) $v) / $maxY) * $chartH;
            $pts[] = round($x, 2).','.round($y, 2);
        }

        return implode(' ', $pts);
    };
    $grossPoly = $linePoints($chart['gross']);
    $feePoly = $linePoints($chart['fee']);
    $affPoly = $linePoints($chart['affiliate']);

    // ── Donut hari ini ───────────────────────────────────────────────────
    $donutGross = (float) $todaySummary['gross'];
    $donutFee = (float) $todaySummary['fee'];
    $donutAff = (float) $todaySummary['affiliate'];
    $donutNet = (float) $todaySummary['net_muthowif'];
    $pctOf = fn (float $part) => $donutGross > 0 ? round(($part / $donutGross) * 100, 1) : 0.0;
    $feePct = $pctOf(max(0, $donutFee - $donutAff));
    $affPct = $pctOf($donutAff);
    $netPct = $donutGross > 0 ? max(0, round(100 - $feePct - $affPct, 1)) : 0.0;
    // r=15.9155 → keliling ≈ 100, jadi dasharray bisa langsung pakai persen.
    $donutSegments = $donutGross > 0
        ? [
            ['pct' => $netPct, 'color' => '#3b5bdb'],
            ['pct' => $feePct, 'color' => '#10b981'],
            ['pct' => $affPct, 'color' => '#f59e0b'],
        ]
        : [];
    $donutOffset = 25; // mulai dari jam 12
@endphp

<x-app-layout>

    <div class="ui-page-y">
        <x-page-container class="ui-stack">

            {{-- ── Header ──────────────────────────────────────────────── --}}
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900">{{ __('admin.finance.page_title') }}</h1>
                    <p class="mt-1 text-sm text-slate-600">
                        {{ __('admin.finance.intro') }}
                    </p>
                </div>
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                    <span class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm">
                        <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" /></svg>
                        {{ now()->translatedFormat('l, d F Y') }}
                    </span>
                    @if (($pendingRefundCount ?? 0) > 0)
                        <a href="{{ route('admin.refunds.index') }}" class="group flex items-center gap-3 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-2.5 shadow-sm transition hover:bg-amber-100">
                            <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-amber-100 text-amber-700 ring-1 ring-amber-200">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" /></svg>
                            </span>
                            <span class="text-left">
                                <span class="block text-xs font-semibold text-amber-900">{{ __('admin.finance.refund_cta') }}</span>
                                <span class="block text-sm font-bold text-amber-950">{{ $pendingRefundCount }} {{ __('admin.finance.refund_pending_count_suffix') }}</span>
                            </span>
                            <svg class="h-4 w-4 text-amber-700 transition group-hover:translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
                        </a>
                    @endif
                </div>
            </div>

            @if (session('status'))
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</div>
            @endif
            @if (session('error'))
                <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
            @endif

            {{-- ── Kartu statistik ─────────────────────────────────────── --}}
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                @php
                    $cards = [
                        [
                            'label' => __('admin.finance.platform_total').' (netto)',
                            'value' => 'Rp '.$fmt($totalPlatformFees),
                            'trend' => $trendChip($trends['fee'] ?? null),
                            'icon' => 'M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z',
                            'iconBg' => 'bg-emerald-50 text-emerald-600 ring-emerald-100',
                            'note' => __('admin.finance.platform_total_note'),
                        ],
                        [
                            'label' => __('admin.finance.affiliate_commission'),
                            'value' => 'Rp '.$fmt($affiliateCommissions),
                            'trend' => $trendChip($trends['affiliate'] ?? null),
                            'icon' => 'M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z',
                            'iconBg' => 'bg-amber-50 text-amber-600 ring-amber-100',
                            'note' => null,
                        ],
                        [
                            'label' => __('admin.finance.gross_volume'),
                            'value' => 'Rp '.$fmt($totalVolume),
                            'trend' => $trendChip($trends['gross'] ?? null),
                            'icon' => 'M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z',
                            'iconBg' => 'bg-blue-50 text-blue-600 ring-blue-100',
                            'note' => null,
                        ],
                        [
                            'label' => __('admin.finance.total_orders'),
                            'value' => $fmt($totalOrders ?? 0),
                            'trend' => $trendChip($trends['orders'] ?? null),
                            'icon' => 'M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007Z',
                            'iconBg' => 'bg-violet-50 text-violet-600 ring-violet-100',
                            'note' => null,
                        ],
                    ];
                @endphp
                @foreach ($cards as $card)
                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="flex items-start justify-between gap-3">
                            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl ring-1 {{ $card['iconBg'] }}">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $card['icon'] }}" /></svg>
                            </span>
                            <p class="flex-1 pt-1 text-right text-[11px] font-semibold uppercase tracking-wide text-slate-500">{{ $card['label'] }}</p>
                        </div>
                        <p class="mt-3 text-2xl font-bold tabular-nums text-slate-900">{{ $card['value'] }}</p>
                        <div class="mt-2 flex items-center gap-2">
                            @if ($card['trend'])
                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-semibold ring-1 {{ $card['trend']['class'] }}">{{ $card['trend']['label'] }}</span>
                                <span class="text-[11px] text-slate-400">{{ __('admin.finance.vs_yesterday') }}</span>
                            @elseif ($card['note'])
                                <span class="text-[11px] text-slate-400">{{ $card['note'] }}</span>
                            @else
                                <span class="text-[11px] text-slate-400">—</span>
                            @endif
                        </div>
                        @if ($card['trend'] && $card['note'])
                            <p class="mt-1 text-[11px] text-slate-400">{{ $card['note'] }}</p>
                        @endif
                    </div>
                @endforeach
            </div>

            {{-- ── Grafik & ringkasan hari ini ─────────────────────────── --}}
            <div class="grid grid-cols-1 gap-4 xl:grid-cols-3">

                {{-- Performa Pendapatan --}}
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm xl:col-span-2">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <h3 class="font-semibold text-slate-900">{{ __('admin.finance.chart_title') }}</h3>
                        <span class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-medium text-slate-600">{{ __('admin.finance.chart_range') }}</span>
                    </div>
                    <div class="mt-3 flex flex-wrap items-center gap-4 text-xs text-slate-600">
                        <span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-[#3b5bdb]"></span>{{ __('admin.finance.series_gross') }}</span>
                        <span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-emerald-500"></span>{{ __('admin.finance.series_fee') }}</span>
                        <span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-amber-500"></span>{{ __('admin.finance.series_affiliate') }}</span>
                    </div>
                    <div class="mt-4 h-48 sm:h-56">
                        <svg class="h-full w-full" viewBox="0 0 {{ $chartW }} {{ $chartH }}" preserveAspectRatio="none" aria-hidden="true">
                            <defs>
                                <linearGradient id="finGrossFill" x1="0" y1="0" x2="0" y2="1">
                                    <stop offset="0%" stop-color="#3b5bdb" stop-opacity="0.18" />
                                    <stop offset="100%" stop-color="#3b5bdb" stop-opacity="0" />
                                </linearGradient>
                            </defs>
                            @for ($i = 1; $i <= 3; $i++)
                                <line x1="0" y1="{{ round($chartH * $i / 4, 2) }}" x2="{{ $chartW }}" y2="{{ round($chartH * $i / 4, 2) }}" stroke="rgb(226 232 240)" stroke-width="0.25" />
                            @endfor
                            <line x1="0" y1="{{ $chartH }}" x2="{{ $chartW }}" y2="{{ $chartH }}" stroke="rgb(203 213 225)" stroke-width="0.35" />
                            <polygon points="0,{{ $chartH }} {{ $grossPoly }} {{ $chartW }},{{ $chartH }}" fill="url(#finGrossFill)" />
                            <polyline points="{{ $grossPoly }}" fill="none" stroke="#3b5bdb" stroke-width="0.9" stroke-linejoin="round" stroke-linecap="round" vector-effect="non-scaling-stroke" />
                            <polyline points="{{ $feePoly }}" fill="none" stroke="#10b981" stroke-width="0.9" stroke-linejoin="round" stroke-linecap="round" vector-effect="non-scaling-stroke" />
                            <polyline points="{{ $affPoly }}" fill="none" stroke="#f59e0b" stroke-width="0.9" stroke-linejoin="round" stroke-linecap="round" vector-effect="non-scaling-stroke" />
                            @foreach ($chart['gross'] as $i => $v)
                                @php
                                    $x = $n > 1 ? ($i / ($n - 1)) * $chartW : 0;
                                    $y = $chartH - (((float) $v) / $maxY) * $chartH;
                                @endphp
                                <circle cx="{{ round($x, 2) }}" cy="{{ round($y, 2) }}" r="0.8" fill="#3b5bdb" />
                            @endforeach
                        </svg>
                    </div>
                    <div class="mt-2 flex justify-between text-[10px] text-slate-400">
                        @foreach ($chart['labels'] as $label)
                            <span>{{ $label }}</span>
                        @endforeach
                    </div>
                </div>

                {{-- Ringkasan hari ini --}}
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h3 class="flex items-center gap-2 font-semibold text-slate-900">
                        <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 1 1-3 0m3 0a1.5 1.5 0 1 0-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-9.75 0h9.75" /></svg>
                        {{ __('admin.finance.today_summary') }}
                    </h3>
                    <div class="mt-4 flex items-center justify-center">
                        <div class="relative h-40 w-40">
                            <svg class="h-full w-full -rotate-90" viewBox="0 0 42 42" aria-hidden="true">
                                <circle cx="21" cy="21" r="15.9155" fill="none" stroke="rgb(241 245 249)" stroke-width="4.5" />
                                @php $acc = 0.0; @endphp
                                @foreach ($donutSegments as $seg)
                                    @if ($seg['pct'] > 0)
                                        <circle cx="21" cy="21" r="15.9155" fill="none"
                                                stroke="{{ $seg['color'] }}" stroke-width="4.5" stroke-linecap="butt"
                                                stroke-dasharray="{{ $seg['pct'] }} {{ 100 - $seg['pct'] }}"
                                                stroke-dashoffset="{{ -$acc }}" />
                                        @php $acc += $seg['pct']; @endphp
                                    @endif
                                @endforeach
                            </svg>
                            <div class="absolute inset-0 flex flex-col items-center justify-center text-center">
                                <span class="text-[10px] font-medium text-slate-500">{{ __('admin.finance.total_gross_today') }}</span>
                                <span class="text-sm font-bold text-slate-900">Rp {{ $fmt($donutGross) }}</span>
                            </div>
                        </div>
                    </div>
                    @if ($donutGross <= 0)
                        <p class="mt-4 text-center text-xs text-slate-400">{{ __('admin.finance.today_empty') }}</p>
                    @endif
                    <dl class="mt-5 space-y-2.5 text-sm">
                        <div class="flex items-center justify-between gap-2">
                            <dt class="inline-flex items-center gap-2 text-slate-600"><span class="h-2.5 w-2.5 rounded-full bg-slate-300"></span>{{ __('admin.finance.series_gross') }}</dt>
                            <dd class="font-semibold tabular-nums text-slate-900">Rp {{ $fmt($donutGross) }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-2">
                            <dt class="inline-flex items-center gap-2 text-slate-600"><span class="h-2.5 w-2.5 rounded-full bg-emerald-500"></span>{{ __('admin.finance.series_fee') }}</dt>
                            <dd class="font-semibold tabular-nums text-slate-900">Rp {{ $fmt(max(0, $donutFee - $donutAff)) }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-2">
                            <dt class="inline-flex items-center gap-2 text-slate-600"><span class="h-2.5 w-2.5 rounded-full bg-amber-500"></span>{{ __('admin.finance.series_affiliate') }}</dt>
                            <dd class="font-semibold tabular-nums text-slate-900">Rp {{ $fmt($donutAff) }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-2 border-t border-slate-100 pt-2.5">
                            <dt class="inline-flex items-center gap-2 text-slate-600"><span class="h-2.5 w-2.5 rounded-full bg-[#3b5bdb]"></span>{{ __('admin.finance.net_to_muthowif') }}</dt>
                            <dd class="font-semibold tabular-nums text-slate-900">Rp {{ $fmt($donutNet) }}</dd>
                        </div>
                    </dl>
                    <p class="mt-4 text-[11px] text-slate-400">{{ __('admin.finance.today_note') }}</p>
                </div>
            </div>

            {{-- ── Riwayat transaksi ───────────────────────────────────── --}}
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-4">
                    <h3 class="font-semibold text-slate-900">{{ __('admin.finance.history_title') }}</h3>
                    <span class="rounded-lg bg-slate-50 px-3 py-1 text-xs font-medium text-slate-500 ring-1 ring-slate-200">
                        {{ __('admin.finance.history_group_rows', ['count' => $history->total()]) }}
                    </span>
                </div>
                @if ($history->isEmpty())
                    <p class="p-8 text-center text-sm text-slate-500">{{ __('admin.finance.history_empty') }}</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            @include('admin.finance.partials.history-thead')
                            <tbody class="divide-y divide-slate-100">
                                @include('admin.finance.partials.history-groups-tbody', ['groups' => $history])
                            </tbody>
                        </table>
                    </div>
                    <div class="border-t border-slate-100 px-4 py-3">
                        {{ $history->withQueryString()->links() }}
                    </div>
                @endif
            </div>
        </x-page-container>
    </div>
</x-app-layout>
