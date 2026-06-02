<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-900 leading-tight">
                    {{ __('admin.service_monitor.title') }}
                </h2>
                <p class="text-sm text-gray-600 mt-0.5">{{ __('admin.service_monitor.subtitle') }}</p>
            </div>
            <div class="flex items-center gap-2 text-xs">
                @if ($realtimeEnabled)
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1 font-semibold text-emerald-800 ring-1 ring-emerald-200">
                        <span class="h-2 w-2 animate-pulse rounded-full bg-emerald-500" aria-hidden="true"></span>
                        {{ __('admin.service_monitor.live_on') }}
                    </span>
                @else
                    <span class="rounded-full bg-amber-50 px-3 py-1 font-semibold text-amber-900 ring-1 ring-amber-200">
                        {{ __('admin.service_monitor.live_off') }}
                    </span>
                @endif
            </div>
        </div>
    </x-slot>

    <div
        class="py-8 sm:py-10"
        x-data="adminServiceMonitorLive({
            fragmentUrl: @js(route('admin.service_monitor.fragment')),
            filter: @js($filter),
            realtimeEnabled: @js($realtimeEnabled),
        })"
    >
        <x-page-container class="space-y-5">
            <div x-ref="liveRoot">
                @include('admin.service-monitor.partials.feed', [
                    'counts' => $counts,
                    'bookings' => $bookings,
                    'filter' => $filter,
                ])
            </x-page-container>
        </div>
    </div>
</x-app-layout>
