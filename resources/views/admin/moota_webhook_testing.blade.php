<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-900 leading-tight">
                    {{ __('admin.moota_webhooks_testing.title') }}
                </h2>
                <p class="text-sm text-gray-600 mt-0.5">{{ __('admin.moota_webhooks_testing.subtitle') }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.moota_webhooks.live') }}" class="inline-flex items-center rounded-xl px-4 py-2 text-sm font-semibold bg-white text-slate-800 ring-1 ring-slate-200 hover:bg-slate-50 transition">
                    {{ __('admin.moota_webhooks_testing.link_production_feed') }}
                </a>
                <a href="{{ route('docs.moota_webhook') }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center rounded-xl px-4 py-2 text-sm font-semibold bg-slate-900 text-white hover:bg-slate-800 transition">
                    {{ __('admin.moota_webhooks_testing.link_docs') }}
                </a>
            </div>
        </div>
    </x-slot>

    @php($webhookUrl = route('webhooks.moota', absolute: true))

    <div class="py-8 sm:py-10 space-y-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            @if (! $realtimeEnabled)
                <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-950">
                    {{ __('admin.moota_webhooks.realtime_off') }}
                </div>
            @endif

            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm ring-1 ring-slate-900/5 space-y-4">
                <h3 class="text-sm font-bold text-slate-900">{{ __('admin.moota_webhooks_testing.checklist_title') }}</h3>
                <ol class="list-decimal list-inside space-y-2 text-sm text-slate-700 leading-relaxed">
                    <li>{{ __('admin.moota_webhooks_testing.step_sandbox') }}</li>
                    <li>{{ __('admin.moota_webhooks_testing.step_tunnel') }}</li>
                    <li>{{ __('admin.moota_webhooks_testing.step_app_url') }}</li>
                    <li>{{ __('admin.moota_webhooks_testing.step_ip_whitelist') }}</li>
                    <li>{{ __('admin.moota_webhooks_testing.step_signing_optional') }}</li>
                    <li>{{ __('admin.moota_webhooks_testing.step_reverb') }}</li>
                </ol>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm ring-1 ring-slate-900/5 space-y-4"
                x-data="{
                    url: @js($webhookUrl),
                    copied: false,
                    async copy() {
                        try {
                            await navigator.clipboard.writeText(this.url);
                            this.copied = true;
                            window.setTimeout(() => { this.copied = false; }, 2200);
                        } catch (_) {}
                    },
                }"
            >
                <h3 class="text-sm font-bold text-slate-900">{{ __('admin.moota_webhooks_testing.webhook_url_heading') }}</h3>
                <p class="text-sm text-slate-600">{{ __('admin.moota_webhooks_testing.webhook_url_hint') }}</p>
                <div class="flex flex-col sm:flex-row sm:items-center gap-3">
                    <code class="flex-1 break-all rounded-xl bg-slate-900 px-4 py-3 text-xs text-brand-50">{{ $webhookUrl }}</code>
                    <button
                        type="button"
                        x-on:click="copy()"
                        class="inline-flex justify-center shrink-0 rounded-xl px-4 py-2.5 text-sm font-semibold bg-brand-600 text-white hover:bg-brand-500 transition ring-1 ring-brand-700/30"
                    >
                        <span x-show="!copied">{{ __('admin.moota_webhooks_testing.copy') }}</span>
                        <span x-show="copied" x-cloak class="text-white">{{ __('admin.moota_webhooks_testing.copied') }}</span>
                    </button>
                </div>
            </section>

            <section aria-labelledby="live-feed-heading">
                <h3 id="live-feed-heading" class="sr-only">{{ __('admin.moota_webhooks_testing.live_section_title') }}</h3>
                @include('admin.partials.moota-webhook-live-feed', [
                    'rows' => $rows,
                    'realtimeEnabled' => $realtimeEnabled,
                    'feedHint' => __('admin.moota_webhooks_testing.feed_hint'),
                ])
            </section>
        </div>
    </div>
</x-app-layout>
