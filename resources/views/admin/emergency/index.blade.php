<x-app-layout>
    <x-page-container class="py-8">
        <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
            <h1 class="text-2xl font-bold text-slate-900">{{ __('emergency.admin.index_title') }}</h1>
            <div class="flex flex-wrap gap-2 text-sm">
                <a href="{{ route('admin.emergency.index') }}" class="rounded-lg px-3 py-1.5 {{ ! $statusFilter ? 'bg-brand-100 font-semibold text-brand-900' : 'bg-slate-100 text-slate-700' }}">{{ __('emergency.admin.filter_all') }}</a>
                @foreach (\App\Enums\EmergencyReportStatus::cases() as $st)
                    <a href="{{ route('admin.emergency.index', ['status' => $st->value]) }}" class="rounded-lg px-3 py-1.5 {{ $statusFilter === $st->value ? 'bg-brand-100 font-semibold text-brand-900' : 'bg-slate-100 text-slate-700' }}">{{ $st->label() }}</a>
                @endforeach
            </div>
        </div>

        <div
            x-data="reverbFragmentLive({
                fragmentUrl: @js(route('admin.emergency.index.live-fragment')),
                appendQuery: true,
                listeners: [
                    { channel: 'admin.emergency-reports', event: '.emergency.report.updated' },
                ],
            })"
        >
        <div x-ref="liveRoot">
        @if ($reports->isEmpty())
            <p class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-10 text-center text-sm text-slate-600">{{ __('emergency.admin.no_reports') }}</p>
        @else
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-4 py-3">{{ __('emergency.admin.booking_code') }}</th>
                            <th class="px-4 py-3">{{ __('emergency.admin.customer') }}</th>
                            <th class="px-4 py-3">{{ __('emergency.admin.case') }}</th>
                            <th class="px-4 py-3">{{ __('emergency.admin.status') }}</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($reports as $report)
                            <tr>
                                <td class="px-4 py-3 font-mono text-xs">{{ $report->muthowifBooking?->booking_code }}</td>
                                <td class="px-4 py-3">{{ $report->muthowifBooking?->customer?->name }}</td>
                                <td class="px-4 py-3">{{ $report->case_type->label() }}</td>
                                <td class="px-4 py-3">{{ $report->status->label() }}</td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('admin.emergency.show', $report) }}" class="font-semibold text-brand-700 hover:text-brand-800">Detail</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $reports->links() }}</div>
        @endif
        </div>
        </div>
    </x-page-container>
</x-app-layout>
