<x-app-layout>

    <div
        class="ui-page-y"
        x-data="adminServiceMonitorLive({
            fragmentUrl: @js(route('admin.service_monitor.fragment')),
            filter: @js($filter),
            realtimeEnabled: @js($realtimeEnabled),
            lastUpdatedLabel: @js(now()->timezone(config('app.timezone'))->translatedFormat('d M Y, H:i:s')),
        })"
    >
        <x-page-container class="space-y-5">

            {{-- ── Header ──────────────────────────────────────────────── --}}
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900">{{ __('admin.service_monitor.title') }}</h1>
                    <p class="mt-1 text-sm text-slate-600">{{ __('admin.service_monitor.subtitle') }}</p>
                </div>
                <div class="flex items-center gap-3">
                    <div class="text-right">
                        @if ($realtimeEnabled)
                            <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-emerald-700">
                                <span class="h-2 w-2 animate-pulse rounded-full bg-emerald-500" aria-hidden="true"></span>
                                {{ __('admin.service_monitor.live_on') }}
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-amber-800">
                                <span class="h-2 w-2 rounded-full bg-amber-500" aria-hidden="true"></span>
                                {{ __('admin.service_monitor.live_off') }}
                            </span>
                        @endif
                        <p class="mt-0.5 text-[11px] text-slate-500">
                            {{ __('admin.service_monitor.last_updated') }}: <span x-text="lastUpdatedLabel"></span> {{ config('app.timezone') === 'Asia/Jakarta' ? 'WIB' : '' }}
                        </p>
                    </div>
                    <button
                        type="button"
                        @click="refreshFragment()"
                        :disabled="refreshing"
                        class="flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 shadow-sm transition hover:bg-slate-50 disabled:opacity-50"
                        :title="@js(__('admin.service_monitor.refresh'))"
                    >
                        <svg class="h-4 w-4" :class="refreshing ? 'animate-spin' : ''" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" /></svg>
                    </button>
                </div>
            </div>

            <div x-ref="liveRoot">
                @include('admin.service-monitor.partials.feed', [
                    'counts' => $counts,
                    'stats' => $stats,
                    'bookings' => $bookings,
                    'filter' => $filter,
                ])
            </div>
        </x-page-container>
    </div>
</x-app-layout>
