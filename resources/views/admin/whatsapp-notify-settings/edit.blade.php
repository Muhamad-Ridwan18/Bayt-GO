<x-app-layout>
    <div class="ui-page-y">
        <x-page-container class="ui-stack-compact">
            <div class="relative overflow-hidden rounded-3xl border border-slate-200 bg-gradient-to-br from-slate-900 via-emerald-900 to-brand-950 p-8 text-white shadow-xl ring-1 ring-white/10">
                <div class="relative">
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-emerald-200/90">{{ __('admin.whatsapp_notify.badge') }}</p>
                    <h1 class="mt-2 text-2xl font-bold tracking-tight">{{ __('admin.whatsapp_notify.title') }}</h1>
                    <p class="mt-2 max-w-xl text-sm leading-relaxed text-white/80">{{ __('admin.whatsapp_notify.subtitle') }}</p>
                    <div class="mt-6 flex flex-wrap gap-3">
                        <a href="{{ route('admin.settings.index') }}" class="inline-flex items-center rounded-xl bg-white/10 px-5 py-2.5 text-sm font-semibold text-white ring-1 ring-white/20 hover:bg-white/20">
                            {{ __('admin.whatsapp_notify.back_settings') }}
                        </a>
                    </div>
                </div>
            </div>

            @if (session('status'))
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-900">
                    {{ session('status') }}
                </div>
            @endif

            @unless ($whatsappTransactionalConfigured)
                <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-950">
                    {{ __('admin.whatsapp_notify.token_missing') }}
                </div>
            @endunless

            @unless ($whatsappBulkConfigured)
                <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-950">
                    {{ __('admin.whatsapp_notify.bulk_token_missing') }}
                </div>
            @endunless

            <form id="wa-notify-settings-form" method="post" action="{{ route('admin.whatsapp-notify-settings.update') }}" class="space-y-6">
                @csrf

                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="text-sm font-bold text-slate-900">{{ __('admin.whatsapp_notify.gateway_transactional_heading') }}</h2>
                    <p class="mt-1 text-xs text-slate-500">{{ __('admin.whatsapp_notify.gateway_transactional_hint') }}</p>

                    <div class="mt-5 grid gap-5 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <x-input-label for="gateway_api_url" :value="__('admin.whatsapp_notify.gateway_api_url')" />
                            <input
                                id="gateway_api_url"
                                name="gateway_api_url"
                                type="url"
                                value="{{ old('gateway_api_url', $transactionalGateway['api_url']) }}"
                                placeholder="https://api.fonnte.com/send"
                                class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                            >
                            <x-input-error :messages="$errors->get('gateway_api_url')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="gateway_token" :value="__('admin.whatsapp_notify.gateway_token')" />
                            <input
                                id="gateway_token"
                                name="gateway_token"
                                type="password"
                                autocomplete="new-password"
                                placeholder="{{ $transactionalGateway['token_set'] ? __('admin.whatsapp_notify.gateway_token_placeholder_set') : __('admin.whatsapp_notify.gateway_token_placeholder') }}"
                                class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                            >
                            @if ($transactionalGateway['token_set'])
                                <p class="mt-1.5 text-xs text-emerald-700">{{ __('admin.whatsapp_notify.gateway_token_set_hint') }}</p>
                            @endif
                            <x-input-error :messages="$errors->get('gateway_token')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="gateway_session_id" :value="__('admin.whatsapp_notify.gateway_session_id')" />
                            <input
                                id="gateway_session_id"
                                name="gateway_session_id"
                                type="text"
                                value="{{ old('gateway_session_id', $transactionalGateway['session_id']) }}"
                                placeholder="—"
                                class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                            >
                            <p class="mt-1.5 text-xs text-slate-500">{{ __('admin.whatsapp_notify.gateway_session_id_hint') }}</p>
                            <x-input-error :messages="$errors->get('gateway_session_id')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="gateway_country_code" :value="__('admin.whatsapp_notify.gateway_country_code')" />
                            <input
                                id="gateway_country_code"
                                name="gateway_country_code"
                                type="text"
                                value="{{ old('gateway_country_code', $transactionalGateway['country_code']) }}"
                                placeholder="62"
                                class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                            >
                            <x-input-error :messages="$errors->get('gateway_country_code')" class="mt-2" />
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="text-sm font-bold text-slate-900">{{ __('admin.whatsapp_notify.gateway_bulk_heading') }}</h2>
                    <p class="mt-1 text-xs text-slate-500">{{ __('admin.whatsapp_notify.gateway_bulk_hint') }}</p>

                    <div class="mt-5 grid gap-5 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <x-input-label for="bulk_gateway_api_url" :value="__('admin.whatsapp_notify.gateway_api_url')" />
                            <input
                                id="bulk_gateway_api_url"
                                name="bulk_gateway_api_url"
                                type="url"
                                value="{{ old('bulk_gateway_api_url', $bulkGateway['api_url']) }}"
                                placeholder="https://whatsapp.baytgo.id/send"
                                class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                            >
                            <x-input-error :messages="$errors->get('bulk_gateway_api_url')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="bulk_gateway_token" :value="__('admin.whatsapp_notify.gateway_token')" />
                            <input
                                id="bulk_gateway_token"
                                name="bulk_gateway_token"
                                type="password"
                                autocomplete="new-password"
                                placeholder="{{ $bulkGateway['token_set'] ? __('admin.whatsapp_notify.gateway_token_placeholder_set') : __('admin.whatsapp_notify.gateway_token_placeholder') }}"
                                class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                            >
                            @if ($bulkGateway['token_set'])
                                <p class="mt-1.5 text-xs text-emerald-700">{{ __('admin.whatsapp_notify.gateway_token_set_hint') }}</p>
                            @endif
                            <x-input-error :messages="$errors->get('bulk_gateway_token')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="bulk_gateway_session_id" :value="__('admin.whatsapp_notify.gateway_session_id')" />
                            <input
                                id="bulk_gateway_session_id"
                                name="bulk_gateway_session_id"
                                type="text"
                                value="{{ old('bulk_gateway_session_id', $bulkGateway['session_id']) }}"
                                placeholder="wa-628112107021"
                                class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                            >
                            <p class="mt-1.5 text-xs text-slate-500">{{ __('admin.whatsapp_notify.gateway_session_id_hint') }}</p>
                            <x-input-error :messages="$errors->get('bulk_gateway_session_id')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="bulk_gateway_country_code" :value="__('admin.whatsapp_notify.gateway_country_code')" />
                            <input
                                id="bulk_gateway_country_code"
                                name="bulk_gateway_country_code"
                                type="text"
                                value="{{ old('bulk_gateway_country_code', $bulkGateway['country_code']) }}"
                                placeholder="62"
                                class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                            >
                            <x-input-error :messages="$errors->get('bulk_gateway_country_code')" class="mt-2" />
                        </div>

                        <div class="sm:col-span-2">
                            <x-input-label for="bulk_gateway_media_public_url" :value="__('admin.whatsapp_notify.gateway_media_public_url')" />
                            <input
                                id="bulk_gateway_media_public_url"
                                name="bulk_gateway_media_public_url"
                                type="url"
                                value="{{ old('bulk_gateway_media_public_url', $bulkGateway['media_public_url']) }}"
                                placeholder="https://baytgo.id"
                                class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                            >
                            <p class="mt-1.5 text-xs text-slate-500">{{ __('admin.whatsapp_notify.gateway_media_public_url_hint') }}</p>
                            <x-input-error :messages="$errors->get('bulk_gateway_media_public_url')" class="mt-2" />
                        </div>
                    </div>
                </div>

                @foreach ($groups as $groupKey => $groupLabel)
                    @php
                        $groupToggles = array_filter($toggles, fn ($t) => $t['group'] === $groupKey);
                    @endphp
                    @if ($groupToggles !== [])
                        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                            <h2 class="text-sm font-bold text-slate-900">{{ __($groupLabel) }}</h2>
                            <ul class="mt-4 divide-y divide-slate-100">
                                @foreach ($groupToggles as $toggleKey => $toggle)
                                    <li class="flex items-start gap-3 py-3">
                                        <input type="hidden" name="toggle_{{ $toggleKey }}" value="0">
                                        <input
                                            id="toggle_{{ $toggleKey }}"
                                            name="toggle_{{ $toggleKey }}"
                                            type="checkbox"
                                            value="1"
                                            @checked(old('toggle_'.$toggleKey, $toggleValues[$toggleKey] ?? false))
                                            class="mt-1 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500"
                                        >
                                        <label for="toggle_{{ $toggleKey }}" class="text-sm text-slate-700">
                                            {{ __($toggle['label']) }}
                                        </label>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                @endforeach

                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="text-sm font-bold text-slate-900">{{ __('admin.whatsapp_notify.admin_numbers_heading') }}</h2>
                    <p class="mt-1 text-xs text-slate-500">{{ __('admin.whatsapp_notify.admin_numbers_hint') }}</p>
                    <p class="mt-1 text-xs text-slate-500">{{ __('admin.whatsapp_notify.test_hint') }}</p>
                    <textarea
                        id="admin_numbers"
                        name="admin_numbers"
                        rows="3"
                        class="mt-4 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-emerald-500 focus:ring-emerald-500"
                        placeholder="081234567890, 6281234567890"
                    >{{ old('admin_numbers', $adminNumbers) }}</textarea>
                    <x-input-error :messages="$errors->get('admin_numbers')" class="mt-2" />
                </div>

                <div id="wa-test-result" class="hidden rounded-xl border px-4 py-3 text-sm"></div>

                <div class="flex flex-wrap justify-end gap-3">
                    <button
                        type="button"
                        data-test-gateway="transactional"
                        class="wa-test-config-btn inline-flex items-center rounded-xl border border-emerald-200 bg-white px-6 py-2.5 text-sm font-semibold text-emerald-800 shadow-sm hover:bg-emerald-50 disabled:cursor-not-allowed disabled:opacity-60"
                    >
                        {{ __('admin.whatsapp_notify.test_button_transactional') }}
                    </button>
                    <button
                        type="button"
                        data-test-gateway="bulk"
                        class="wa-test-config-btn inline-flex items-center rounded-xl border border-sky-200 bg-white px-6 py-2.5 text-sm font-semibold text-sky-800 shadow-sm hover:bg-sky-50 disabled:cursor-not-allowed disabled:opacity-60"
                    >
                        {{ __('admin.whatsapp_notify.test_button_bulk') }}
                    </button>
                    <button type="submit" class="inline-flex items-center rounded-xl bg-emerald-600 px-6 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700">
                        {{ __('admin.whatsapp_notify.save') }}
                    </button>
                </div>
            </form>

            @push('scripts')
                <script>
                    (function () {
                        const form = document.getElementById('wa-notify-settings-form');
                        const buttons = document.querySelectorAll('.wa-test-config-btn');
                        const resultBox = document.getElementById('wa-test-result');
                        if (!form || !buttons.length || !resultBox) return;

                        const labels = {
                            testing: @json(__('admin.whatsapp_notify.test_running')),
                        };

                        buttons.forEach(function (btn) {
                            const defaultLabel = btn.textContent;
                            btn.addEventListener('click', async function () {
                                const gateway = btn.getAttribute('data-test-gateway') || 'transactional';
                                buttons.forEach(function (b) { b.disabled = true; });
                                btn.textContent = labels.testing;
                                resultBox.classList.add('hidden');
                                resultBox.textContent = '';

                                try {
                                    const body = new FormData(form);
                                    body.append('test_gateway', gateway);

                                    const response = await fetch(@json(route('admin.whatsapp-notify-settings.test')), {
                                        method: 'POST',
                                        headers: {
                                            'X-CSRF-TOKEN': @json(csrf_token()),
                                            'Accept': 'application/json',
                                        },
                                        body: body,
                                    });

                                    const data = await response.json();
                                    let text = data.message || '';
                                    if (data.errors) {
                                        text = Object.values(data.errors).flat().join('\n');
                                    } else if (Array.isArray(data.results)) {
                                    const lines = data.results.map(function (row) {
                                        if (row.ok) {
                                            return '↻ ' + row.phone + (row.queued ? ' (antrian)' : '');
                                        }
                                        return '✗ ' + row.phone + (row.error ? ': ' + row.error : '');
                                    });
                                        if (lines.length) {
                                            text += (text ? '\n\n' : '') + lines.join('\n');
                                        }
                                    }

                                    resultBox.textContent = text;
                                    resultBox.style.whiteSpace = 'pre-wrap';
                                    resultBox.classList.remove('hidden');
                                    resultBox.classList.toggle('border-emerald-200', response.ok);
                                    resultBox.classList.toggle('bg-emerald-50', response.ok);
                                    resultBox.classList.toggle('text-emerald-900', response.ok);
                                    resultBox.classList.toggle('border-red-200', !response.ok);
                                    resultBox.classList.toggle('bg-red-50', !response.ok);
                                    resultBox.classList.toggle('text-red-900', !response.ok);
                                } catch (error) {
                                    resultBox.textContent = @json(__('admin.whatsapp_notify.test_request_failed'));
                                    resultBox.classList.remove('hidden');
                                    resultBox.classList.add('border-red-200', 'bg-red-50', 'text-red-900');
                                } finally {
                                    buttons.forEach(function (b) {
                                        b.disabled = false;
                                        if (b === btn) {
                                            b.textContent = defaultLabel;
                                        }
                                    });
                                }
                            });
                        });
                    })();
                </script>
            @endpush
        </x-page-container>
    </div>
</x-app-layout>
