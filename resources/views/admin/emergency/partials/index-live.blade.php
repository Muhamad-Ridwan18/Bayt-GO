@if ($reports->isEmpty())
    <p class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-10 text-center text-sm text-slate-600">{{ __('emergency.admin.no_reports') }}</p>
@else
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
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
        <div class="border-t border-slate-100 px-4 py-3">{{ $reports->withQueryString()->links() }}</div>
    </div>
@endif
