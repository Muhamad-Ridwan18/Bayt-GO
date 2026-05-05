<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-900 leading-tight">
                    {{ __('admin.moota_webhooks.title') }}
                </h2>
                <p class="text-sm text-gray-600 mt-0.5">{{ __('admin.moota_webhooks.subtitle') }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.moota_webhooks.testing') }}" class="inline-flex items-center rounded-xl px-4 py-2 text-sm font-semibold bg-brand-50 text-brand-800 ring-1 ring-brand-200 hover:bg-brand-100 transition">
                    {{ __('admin.moota_webhooks_testing.nav_cta') }}
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8 sm:py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-5">
            @if (! $realtimeEnabled)
                <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-950">
                    {{ __('admin.moota_webhooks.realtime_off') }}
                </div>
            @endif

            @include('admin.partials.moota-webhook-live-feed', ['rows' => $rows, 'realtimeEnabled' => $realtimeEnabled])
        </div>
    </div>
</x-app-layout>
