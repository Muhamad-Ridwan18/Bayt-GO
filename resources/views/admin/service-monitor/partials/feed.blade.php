@php
    use App\Services\Admin\AdminServiceMonitorService;
    $monitor = app(AdminServiceMonitorService::class);
@endphp
<div class="space-y-5">
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
        @foreach ([
            AdminServiceMonitorService::FILTER_ACTIVE => __('admin.service_monitor.tab_active'),
            AdminServiceMonitorService::FILTER_IN_SERVICE => __('admin.service_monitor.tab_in_service'),
        ] as $tab => $label)
            <a
                href="{{ route('admin.service_monitor.index', ['filter' => $tab]) }}"
                data-monitor-filter="{{ $tab }}"
                class="rounded-2xl border px-4 py-3 text-left shadow-sm transition {{ $filter === $tab ? 'border-brand-300 bg-brand-50 ring-1 ring-brand-200' : 'border-slate-200 bg-white hover:border-slate-300' }}"
            >
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $label }}</p>
                <p class="mt-1 text-2xl font-bold text-slate-900">{{ $counts[$tab] ?? 0 }}</p>
            </a>
        @endforeach
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase text-slate-500">
                    <tr>
                        <th class="px-4 py-3">{{ __('admin.service_monitor.col_booking') }}</th>
                        <th class="px-4 py-3">{{ __('admin.service_monitor.col_parties') }}</th>
                        <th class="px-4 py-3">{{ __('admin.service_monitor.col_period') }}</th>
                        <th class="px-4 py-3">{{ __('admin.service_monitor.col_phase') }}</th>
                        <th class="px-4 py-3">{{ __('admin.service_monitor.col_escrow') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($bookings as $booking)
                        @php
                            $escrow = $monitor->escrowLabel($booking);
                            $phaseKey = $monitor->servicePhaseKey($booking);
                        @endphp
                        <tr class="hover:bg-slate-50/80">
                            <td class="px-4 py-3 whitespace-nowrap">
                                <p class="font-mono text-xs font-semibold text-slate-900">{{ $booking->booking_code }}</p>
                                <p class="mt-0.5 text-[10px] text-slate-500">{{ $monitor->serviceDayLabel($booking) }}</p>
                            </td>
                            <td class="px-4 py-3">
                                <p class="font-medium text-slate-900">{{ $booking->customer?->name ?? '—' }}</p>
                                <p class="text-xs text-slate-600">{{ $booking->muthowifProfile?->user?->name ?? '—' }}</p>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-xs text-slate-700">
                                {{ $booking->starts_on?->format('d M Y') }}
                                <span class="text-slate-400">–</span>
                                {{ $booking->ends_on?->format('d M Y') }}
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-800">
                                    {{ $phaseKey ? __('admin.service_monitor.phase_'.$phaseKey) : '—' }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                @if ($escrow === 'released')
                                    <span class="text-xs font-semibold text-emerald-700">{{ __('admin.service_monitor.escrow_released') }}</span>
                                @else
                                    <span class="text-xs font-semibold text-slate-600">{{ __('admin.service_monitor.escrow_held') }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-12 text-center text-slate-500">
                                {{ __('admin.service_monitor.empty') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <p class="text-xs text-slate-500">{{ __('admin.service_monitor.footer_hint') }}</p>
</div>
