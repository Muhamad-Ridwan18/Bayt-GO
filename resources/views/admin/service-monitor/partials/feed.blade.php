@php
    use App\Services\Admin\AdminServiceMonitorService;
    use App\Support\IndonesianNumber;

    $monitor = app(AdminServiceMonitorService::class);
    $fmt = fn (float|int $n) => IndonesianNumber::formatThousands((string) (int) round((float) $n));

    $completedDelta = (int) ($stats['completed_today'] ?? 0) - (int) ($stats['completed_yesterday'] ?? 0);

    $phaseBadge = fn (?string $key) => match ($key) {
        'in_service' => 'bg-indigo-50 text-indigo-800 ring-indigo-200/70',
        'post_service' => 'bg-emerald-50 text-emerald-800 ring-emerald-200/70',
        'pre_service' => 'bg-slate-100 text-slate-700 ring-slate-200/70',
        default => 'bg-slate-100 text-slate-500 ring-slate-200/70',
    };
@endphp
<div
    class="space-y-5"
    x-data="{
        q: '',
        phase: '',
        escrow: '',
        visible: {{ $bookings->count() }},
        match(el) {
            const q = this.q.trim().toLowerCase();
            if (q !== '' && !(el.dataset.search || '').includes(q)) return false;
            if (this.phase !== '' && el.dataset.phase !== this.phase) return false;
            if (this.escrow !== '' && el.dataset.escrow !== this.escrow) return false;
            return true;
        },
    }"
    x-effect="visible = Array.from($refs.list?.querySelectorAll('[data-row]') ?? []).filter((el) => match(el)).length"
>
    {{-- ── Kartu statistik ─────────────────────────────────────────── --}}
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <a
            href="{{ route('admin.service_monitor.index', ['filter' => AdminServiceMonitorService::FILTER_ACTIVE]) }}"
            data-monitor-filter="{{ AdminServiceMonitorService::FILTER_ACTIVE }}"
            class="rounded-2xl border bg-white p-5 text-left shadow-sm transition {{ $filter === AdminServiceMonitorService::FILTER_ACTIVE ? 'border-brand-300 ring-2 ring-brand-200' : 'border-slate-200 hover:border-slate-300' }}"
        >
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">{{ __('admin.service_monitor.tab_active') }}</p>
                    <p class="mt-1 text-3xl font-bold text-slate-900">{{ $counts[AdminServiceMonitorService::FILTER_ACTIVE] ?? 0 }}</p>
                </div>
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-emerald-50 text-emerald-600 ring-1 ring-emerald-100">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5m-9-6h.008v.008H12v-.008ZM12 15h.008v.008H12V15Zm0 2.25h.008v.008H12v-.008ZM9.75 15h.008v.008H9.75V15Zm0 2.25h.008v.008H9.75v-.008ZM7.5 15h.008v.008H7.5V15Zm0 2.25h.008v.008H7.5v-.008Zm6.75-4.5h.008v.008h-.008v-.008Zm0 2.25h.008v.008h-.008V15Zm0 2.25h.008v.008h-.008v-.008Zm2.25-4.5h.008v.008H16.5v-.008Zm0 2.25h.008v.008H16.5V15Z" /></svg>
                </span>
            </div>
            <p class="mt-2 flex items-center gap-1.5 text-xs text-slate-500">
                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                {{ __('admin.service_monitor.stat_active_caption') }}
            </p>
        </a>

        <a
            href="{{ route('admin.service_monitor.index', ['filter' => AdminServiceMonitorService::FILTER_IN_SERVICE]) }}"
            data-monitor-filter="{{ AdminServiceMonitorService::FILTER_IN_SERVICE }}"
            class="rounded-2xl border bg-white p-5 text-left shadow-sm transition {{ $filter === AdminServiceMonitorService::FILTER_IN_SERVICE ? 'border-brand-300 ring-2 ring-brand-200' : 'border-slate-200 hover:border-slate-300' }}"
        >
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">{{ __('admin.service_monitor.tab_in_service') }}</p>
                    <p class="mt-1 text-3xl font-bold text-slate-900">{{ $counts[AdminServiceMonitorService::FILTER_IN_SERVICE] ?? 0 }}</p>
                </div>
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-amber-50 text-amber-600 ring-1 ring-amber-100">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h3l2.25-6 4.5 12 2.25-6h4.5" /></svg>
                </span>
            </div>
            <p class="mt-2 flex items-center gap-1.5 text-xs text-slate-500">
                <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                {{ __('admin.service_monitor.stat_in_service_caption') }}
            </p>
        </a>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">{{ __('admin.service_monitor.stat_completed_today') }}</p>
                    <p class="mt-1 text-3xl font-bold text-slate-900">{{ $stats['completed_today'] ?? 0 }}</p>
                </div>
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-blue-50 text-blue-600 ring-1 ring-blue-100">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                </span>
            </div>
            <p class="mt-2 flex items-center gap-1.5 text-xs {{ $completedDelta >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                <span>{{ $completedDelta >= 0 ? '↑' : '↓' }} {{ abs($completedDelta) }}</span>
                <span class="text-slate-500">{{ __('admin.service_monitor.from_yesterday') }}</span>
            </p>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">{{ __('admin.service_monitor.stat_escrow') }}</p>
                    <p class="mt-1 text-2xl font-bold text-slate-900">Rp {{ $fmt($stats['escrow_held'] ?? 0) }}</p>
                </div>
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-violet-50 text-violet-600 ring-1 ring-violet-100">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" /></svg>
                </span>
            </div>
            <p class="mt-2 flex items-center gap-1.5 text-xs text-slate-500">
                <span class="h-1.5 w-1.5 rounded-full bg-violet-500"></span>
                {{ __('admin.service_monitor.stat_escrow_caption') }}
            </p>
        </div>
    </div>

    {{-- ── Filter bar ──────────────────────────────────────────────── --}}
    <div class="flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white p-3 shadow-sm sm:flex-row sm:items-center">
        <div class="relative flex-1">
            <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" /></svg>
            <input
                type="search"
                x-model.debounce.200ms="q"
                placeholder="{{ __('admin.service_monitor.search_placeholder') }}"
                class="w-full rounded-xl border-slate-200 bg-slate-50/60 pl-9 text-sm placeholder:text-slate-400 focus:border-brand-500 focus:ring-brand-500"
            >
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <select x-model="phase" class="rounded-xl border-slate-200 bg-white text-sm text-slate-700 focus:border-brand-500 focus:ring-brand-500">
                <option value="">{{ __('admin.service_monitor.filter_all_phases') }}</option>
                <option value="pre_service">{{ __('admin.service_monitor.phase_pre_service') }}</option>
                <option value="in_service">{{ __('admin.service_monitor.phase_in_service') }}</option>
                <option value="post_service">{{ __('admin.service_monitor.phase_post_service') }}</option>
            </select>
            <select x-model="escrow" class="rounded-xl border-slate-200 bg-white text-sm text-slate-700 focus:border-brand-500 focus:ring-brand-500">
                <option value="">{{ __('admin.service_monitor.filter_all_escrow') }}</option>
                <option value="held">{{ __('admin.service_monitor.escrow_held') }}</option>
                <option value="released">{{ __('admin.service_monitor.escrow_released') }}</option>
            </select>
        </div>
    </div>

    {{-- ── Daftar layanan ──────────────────────────────────────────── --}}
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-wrap items-center gap-2 border-b border-slate-100 px-5 py-3.5">
            <h3 class="font-semibold text-slate-900">{{ __('admin.service_monitor.list_title') }}</h3>
            <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-600" x-text="visible + ' / {{ $bookings->count() }} {{ __('admin.service_monitor.list_count_suffix') }}'"></span>
        </div>

        <div x-ref="list" class="divide-y divide-slate-100">
            @forelse ($bookings as $booking)
                @php
                    $escrow = $monitor->escrowLabel($booking);
                    $phaseKey = $monitor->servicePhaseKey($booking);
                    $progress = $monitor->serviceProgress($booking);
                    $customerName = $booking->customer?->name ?? '—';
                    $muthowifName = $booking->muthowifProfile?->user?->name ?? '—';
                    $searchIndex = strtolower(trim(($booking->booking_code ?? '').' '.$customerName.' '.$muthowifName));
                    $barColor = ($progress['pct'] ?? 0) >= 75 && $phaseKey === 'in_service' ? 'bg-amber-500' : 'bg-emerald-500';
                @endphp
                <div
                    data-row
                    data-search="{{ $searchIndex }}"
                    data-phase="{{ $phaseKey ?? '' }}"
                    data-escrow="{{ $escrow }}"
                    x-show="match($el)"
                    class="grid grid-cols-2 gap-x-4 gap-y-3 px-5 py-4 transition hover:bg-slate-50/70 sm:grid-cols-3 lg:grid-cols-12 lg:items-center"
                >
                    {{-- Pesanan --}}
                    <div class="col-span-2 sm:col-span-3 lg:col-span-3">
                        <div class="flex items-start gap-3">
                            <span class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-500 ring-1 ring-slate-200">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" /></svg>
                            </span>
                            <div class="min-w-0">
                                <p class="truncate font-mono text-sm font-bold text-slate-900">{{ $booking->booking_code }}</p>
                                <p class="mt-0.5 text-xs font-medium {{ $phaseKey === 'in_service' ? 'text-emerald-700' : 'text-slate-500' }}">{{ $monitor->serviceDayLabel($booking) }}</p>
                                @if ($booking->created_at)
                                    <p class="mt-0.5 text-[11px] text-slate-400">{{ __('admin.service_monitor.order_created', ['date' => $booking->created_at->timezone(config('app.timezone'))->format('d M Y H:i')]) }}</p>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Jamaah --}}
                    <div class="lg:col-span-2">
                        <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">{{ __('admin.service_monitor.col_customer') }}</p>
                        <div class="mt-1 flex items-center gap-2">
                            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-brand-100 text-[11px] font-bold text-brand-800">{{ mb_substr($customerName, 0, 1) }}</span>
                            <p class="truncate text-sm font-medium text-slate-900">{{ $customerName }}</p>
                        </div>
                    </div>

                    {{-- Muthowif --}}
                    <div class="lg:col-span-2">
                        <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">{{ __('admin.service_monitor.col_muthowif') }}</p>
                        <div class="mt-1 flex items-center gap-2">
                            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-slate-200 text-[11px] font-bold text-slate-700">{{ mb_substr($muthowifName, 0, 1) }}</span>
                            <p class="truncate text-sm font-medium text-slate-900">{{ $muthowifName }}</p>
                        </div>
                    </div>

                    {{-- Periode --}}
                    <div class="lg:col-span-2">
                        <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">{{ __('admin.service_monitor.col_period') }}</p>
                        <p class="mt-1 whitespace-nowrap text-sm font-medium text-slate-900">
                            {{ $booking->starts_on?->format('d M Y') }} <span class="text-slate-400">–</span> {{ $booking->ends_on?->format('d M Y') }}
                        </p>
                        @if ($progress !== null)
                            <p class="text-[11px] text-slate-400">{{ __('admin.service_monitor.duration_days', ['days' => $progress['total']]) }}</p>
                        @endif
                    </div>

                    {{-- Progress --}}
                    <div class="lg:col-span-1">
                        <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">{{ __('admin.service_monitor.col_progress') }}</p>
                        @if ($progress !== null)
                            <div class="mt-1.5 flex items-center gap-2">
                                <div class="h-1.5 w-full min-w-[3rem] overflow-hidden rounded-full bg-slate-100">
                                    <div class="h-full rounded-full {{ $barColor }}" style="width: {{ $progress['pct'] }}%"></div>
                                </div>
                                <span class="text-xs font-semibold tabular-nums text-slate-700">{{ $progress['pct'] }}%</span>
                            </div>
                        @else
                            <p class="mt-1 text-xs text-slate-400">—</p>
                        @endif
                    </div>

                    {{-- Fase --}}
                    <div class="lg:col-span-1">
                        <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">{{ __('admin.service_monitor.col_phase') }}</p>
                        <span class="mt-1 inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 {{ $phaseBadge($phaseKey) }}">
                            {{ $phaseKey ? __('admin.service_monitor.phase_'.$phaseKey) : '—' }}
                        </span>
                    </div>

                    {{-- Escrow --}}
                    <div class="lg:col-span-1">
                        <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">{{ __('admin.service_monitor.col_escrow') }}</p>
                        @if ($escrow === 'released')
                            <p class="mt-1 inline-flex items-center gap-1 text-xs font-semibold text-emerald-700">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                                {{ __('admin.service_monitor.escrow_released') }}
                            </p>
                        @else
                            <p class="mt-1 inline-flex items-center gap-1 text-xs font-semibold text-slate-600">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" /></svg>
                                {{ __('admin.service_monitor.escrow_held') }}
                            </p>
                            <p class="text-[11px] text-slate-400">{{ __('admin.service_monitor.stat_escrow_caption') }}</p>
                        @endif
                    </div>
                </div>
            @empty
                <p class="px-5 py-12 text-center text-sm text-slate-500">{{ __('admin.service_monitor.empty') }}</p>
            @endforelse

            @if ($bookings->isNotEmpty())
                <p class="px-5 py-10 text-center text-sm text-slate-500" x-show="visible === 0" x-cloak>
                    {{ __('admin.service_monitor.no_results') }}
                </p>
            @endif
        </div>

        <div class="flex flex-wrap items-center justify-between gap-2 border-t border-slate-100 px-5 py-3">
            <p class="text-xs text-slate-500" x-text="'{{ __('admin.service_monitor.showing_prefix') }}' + visible + ' {{ __('admin.service_monitor.showing_suffix', ['total' => $bookings->count()]) }}'"></p>

            <p class="text-xs text-slate-400">{{ __('admin.service_monitor.footer_hint') }}</p>
        </div>
    </div>
</div>
