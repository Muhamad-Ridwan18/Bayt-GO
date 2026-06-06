@php
    use App\Enums\EmergencyReportStatus;
    $b = $report->muthowifBooking;
@endphp
<x-app-layout>
    <x-page-container class="py-8">
        <a href="{{ route('admin.emergency.index') }}" class="mb-4 inline-block text-sm font-semibold text-brand-700">← {{ __('emergency.admin.index_title') }}</a>
        <h1 class="text-2xl font-bold text-slate-900">{{ __('emergency.admin.show_title') }}</h1>

        <div
            class="mt-6"
            x-data="reverbFragmentLive({
                fragmentUrl: @js(route('admin.emergency.show.fragment', $report)),
                listeners: [
                    { channel: 'admin.emergency-reports', event: '.emergency.report.updated', match: { field: 'report_id', value: @js($report->getKey()) } },
                ],
            })"
        >
            <div x-ref="liveRoot">
                @include('admin.emergency.partials.show-live', ['report' => $report, 'manualCandidates' => $manualCandidates])
            </div>
        </div>
    </x-page-container>
</x-app-layout>
