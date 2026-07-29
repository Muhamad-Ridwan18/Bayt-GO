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

            @unless ($whatsappConfigured)
                <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-950">
                    {{ __('admin.whatsapp_notify.token_missing') }}
                </div>
            @endunless

            <form id="wa-notify-settings-form" method="post" action="{{ route('admin.whatsapp-notify-settings.update') }}" class="space-y-6">
                @csrf

                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="text-sm font-bold text-slate-900">{{ __('admin.whatsapp_notify.gateway_heading') }}</h2>
                    <p class="mt-1 text-xs text-slate-500">{{ __('admin.whatsapp_notify.gateway_hint') }}</p>

                    <div class="mt-5 grid gap-5 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <x-input-label for="gateway_api_url" :value="__('admin.whatsapp_notify.gateway_api_url')" />
                            <input
                                id="gateway_api_url"
                                name="gateway_api_url"
                                type="url"
                                value="{{ old('gateway_api_url', $gateway['api_url']) }}"
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
                                placeholder="{{ $gateway['token_set'] ? __('admin.whatsapp_notify.gateway_token_placeholder_set') : __('admin.whatsapp_notify.gateway_token_placeholder') }}"
                                class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                            >
                            @if ($gateway['token_set'])
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
                                value="{{ old('gateway_session_id', $gateway['session_id']) }}"
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
                                value="{{ old('gateway_country_code', $gateway['country_code']) }}"
                                placeholder="62"
                                class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                            >
                            <x-input-error :messages="$errors->get('gateway_country_code')" class="mt-2" />
                        </div>

                        <div class="sm:col-span-2">
                            <x-input-label for="gateway_media_public_url" :value="__('admin.whatsapp_notify.gateway_media_public_url')" />
                            <input
                                id="gateway_media_public_url"
                                name="gateway_media_public_url"
                                type="url"
                                value="{{ old('gateway_media_public_url', $gateway['media_public_url']) }}"
                                placeholder="https://baytgo.id"
                                class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                            >
                            <p class="mt-1.5 text-xs text-slate-500">{{ __('admin.whatsapp_notify.gateway_media_public_url_hint') }}</p>
                            <x-input-error :messages="$errors->get('gateway_media_public_url')" class="mt-2" />
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
                            @if ($groupKey === 'chat')
                                <div class="mt-5 border-t border-slate-100 pt-5">
                                    <h3 class="text-sm font-semibold text-slate-900">{{ __('admin.whatsapp_notify.chat_settings_heading') }}</h3>
                                    <p class="mt-1 text-xs text-slate-500">{{ __('admin.whatsapp_notify.chat_settings_hint') }}</p>
                                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                                        <div>
                                            <x-input-label for="chat_unreplied_threshold_minutes" :value="__('admin.whatsapp_notify.chat_unreplied_threshold_minutes')" />
                                            <input
                                                id="chat_unreplied_threshold_minutes"
                                                name="chat_unreplied_threshold_minutes"
                                                type="number"
                                                min="1"
                                                max="1440"
                                                value="{{ old('chat_unreplied_threshold_minutes', $chatSettings['threshold_minutes']) }}"
                                                class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                                            >
                                            <p class="mt-1.5 text-xs text-slate-500">{{ __('admin.whatsapp_notify.chat_unreplied_threshold_minutes_hint') }}</p>
                                            <x-input-error :messages="$errors->get('chat_unreplied_threshold_minutes')" class="mt-2" />
                                        </div>
                                        <div>
                                            <x-input-label for="chat_unreplied_daily_time" :value="__('admin.whatsapp_notify.chat_unreplied_daily_time')" />
                                            <input
                                                id="chat_unreplied_daily_time"
                                                name="chat_unreplied_daily_time"
                                                type="time"
                                                value="{{ old('chat_unreplied_daily_time', $chatSettings['daily_time']) }}"
                                                class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                                            >
                                            <p class="mt-1.5 text-xs text-slate-500">{{ __('admin.whatsapp_notify.chat_unreplied_daily_time_hint') }}</p>
                                            <x-input-error :messages="$errors->get('chat_unreplied_daily_time')" class="mt-2" />
                                        </div>
                                    </div>
                                    <div class="mt-5 rounded-xl border border-amber-200 bg-amber-50 p-4">
                                        <h4 class="text-sm font-semibold text-amber-950">{{ __('admin.whatsapp_notify.run_chat_unreplied_heading') }}</h4>
                                        <p class="mt-1 text-xs text-amber-900/80">{{ __('admin.whatsapp_notify.run_chat_unreplied_hint') }}</p>
                                        <div id="wa-run-chat-unreplied-result" class="mt-3 hidden rounded-xl border px-4 py-3 text-sm"></div>
                                        <div class="mt-3 flex flex-wrap gap-3">
                                            <button
                                                type="button"
                                                id="wa-run-chat-unreplied-dry-btn"
                                                class="inline-flex items-center rounded-xl border border-amber-300 bg-white px-5 py-2.5 text-sm font-semibold text-amber-900 shadow-sm hover:bg-amber-100 disabled:cursor-not-allowed disabled:opacity-60"
                                            >
                                                {{ __('admin.whatsapp_notify.run_chat_unreplied_dry_button') }}
                                            </button>
                                            <button
                                                type="button"
                                                id="wa-run-chat-unreplied-btn"
                                                class="inline-flex items-center rounded-xl bg-amber-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-amber-700 disabled:cursor-not-allowed disabled:opacity-60"
                                            >
                                                {{ __('admin.whatsapp_notify.run_chat_unreplied_button') }}
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-5 border-t border-slate-100 pt-5">
                                    <h3 class="text-sm font-semibold text-slate-900">{{ __('admin.whatsapp_notify.test_chat_unreplied_heading') }}</h3>
                                    <p class="mt-1 text-xs text-slate-500">{{ __('admin.whatsapp_notify.test_chat_unreplied_hint') }}</p>
                                    <div class="mt-4 grid gap-4 sm:grid-cols-3">
                                        <div>
                                            <x-input-label for="test_chat_name" :value="__('admin.whatsapp_notify.test_chat_unreplied_name')" />
                                            <input
                                                id="test_chat_name"
                                                name="test_chat_name"
                                                type="text"
                                                value="{{ old('test_chat_name', __('admin.whatsapp_notify.test_chat_unreplied_default_name')) }}"
                                                class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                                            >
                                        </div>
                                        <div>
                                            <x-input-label for="test_chat_booking_code" :value="__('admin.whatsapp_notify.test_chat_unreplied_booking_code')" />
                                            <input
                                                id="test_chat_booking_code"
                                                name="test_chat_booking_code"
                                                type="text"
                                                value="{{ old('test_chat_booking_code', 'BG-TEST') }}"
                                                class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                                            >
                                        </div>
                                        <div>
                                            <x-input-label for="test_chat_phone" :value="__('admin.whatsapp_notify.test_chat_unreplied_phone')" />
                                            <input
                                                id="test_chat_phone"
                                                name="test_chat_phone"
                                                type="text"
                                                placeholder="081234567890"
                                                class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                                            >
                                            <p class="mt-1.5 text-xs text-slate-500">{{ __('admin.whatsapp_notify.test_chat_unreplied_phone_hint') }}</p>
                                        </div>
                                    </div>
                                    <div id="wa-test-chat-unreplied-result" class="mt-4 hidden rounded-xl border px-4 py-3 text-sm"></div>
                                    <button
                                        type="button"
                                        id="wa-test-chat-unreplied-btn"
                                        class="mt-4 inline-flex items-center rounded-xl border border-emerald-200 bg-white px-5 py-2.5 text-sm font-semibold text-emerald-800 shadow-sm hover:bg-emerald-50 disabled:cursor-not-allowed disabled:opacity-60"
                                    >
                                        {{ __('admin.whatsapp_notify.test_chat_unreplied_button') }}
                                    </button>
                                </div>
                            @endif
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
                        id="wa-test-config-btn"
                        class="inline-flex items-center rounded-xl border border-emerald-200 bg-white px-6 py-2.5 text-sm font-semibold text-emerald-800 shadow-sm hover:bg-emerald-50 disabled:cursor-not-allowed disabled:opacity-60"
                    >
                        {{ __('admin.whatsapp_notify.test_button') }}
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
                        const btn = document.getElementById('wa-test-config-btn');
                        const resultBox = document.getElementById('wa-test-result');
                        if (!form || !btn || !resultBox) return;

                        const labels = {
                            testing: @json(__('admin.whatsapp_notify.test_running')),
                            defaultButton: @json(__('admin.whatsapp_notify.test_button')),
                        };

                        btn.addEventListener('click', async function () {
                            btn.disabled = true;
                            btn.textContent = labels.testing;
                            resultBox.classList.add('hidden');
                            resultBox.textContent = '';

                            try {
                                const response = await fetch(@json(route('admin.whatsapp-notify-settings.test')), {
                                    method: 'POST',
                                    headers: {
                                        'X-CSRF-TOKEN': @json(csrf_token()),
                                        'Accept': 'application/json',
                                    },
                                    body: new FormData(form),
                                });

                                const data = await response.json();
                                let text = data.message || '';
                                if (data.errors) {
                                    text = Object.values(data.errors).flat().join('\n');
                                } else if (Array.isArray(data.results)) {
                                    const lines = data.results.map(function (row) {
                                        if (row.ok) {
                                            return '✓ ' + row.phone;
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
                                btn.disabled = false;
                                btn.textContent = labels.defaultButton;
                            }
                        });

                        const chatBtn = document.getElementById('wa-test-chat-unreplied-btn');
                        const chatResultBox = document.getElementById('wa-test-chat-unreplied-result');
                        if (chatBtn && chatResultBox) {
                            const chatLabels = {
                                testing: @json(__('admin.whatsapp_notify.test_chat_unreplied_running')),
                                defaultButton: @json(__('admin.whatsapp_notify.test_chat_unreplied_button')),
                            };

                            chatBtn.addEventListener('click', async function () {
                                chatBtn.disabled = true;
                                chatBtn.textContent = chatLabels.testing;
                                chatResultBox.classList.add('hidden');
                                chatResultBox.textContent = '';

                                try {
                                    const response = await fetch(@json(route('admin.whatsapp-notify-settings.test-chat-unreplied')), {
                                        method: 'POST',
                                        headers: {
                                            'X-CSRF-TOKEN': @json(csrf_token()),
                                            'Accept': 'application/json',
                                        },
                                        body: new FormData(form),
                                    });

                                    const data = await response.json();
                                    let text = data.message || '';
                                    if (data.errors) {
                                        text = Object.values(data.errors).flat().join('\n');
                                    } else if (Array.isArray(data.results)) {
                                        const lines = data.results.map(function (row) {
                                            if (row.ok) {
                                                return '✓ ' + row.phone;
                                            }
                                            return '✗ ' + row.phone + (row.error ? ': ' + row.error : '');
                                        });
                                        if (lines.length) {
                                            text += (text ? '\n\n' : '') + lines.join('\n');
                                        }
                                    }

                                    chatResultBox.textContent = text;
                                    chatResultBox.style.whiteSpace = 'pre-wrap';
                                    chatResultBox.classList.remove('hidden');
                                    chatResultBox.classList.toggle('border-emerald-200', response.ok);
                                    chatResultBox.classList.toggle('bg-emerald-50', response.ok);
                                    chatResultBox.classList.toggle('text-emerald-900', response.ok);
                                    chatResultBox.classList.toggle('border-red-200', !response.ok);
                                    chatResultBox.classList.toggle('bg-red-50', !response.ok);
                                    chatResultBox.classList.toggle('text-red-900', !response.ok);
                                } catch (error) {
                                    chatResultBox.textContent = @json(__('admin.whatsapp_notify.test_request_failed'));
                                    chatResultBox.classList.remove('hidden');
                                    chatResultBox.classList.add('border-red-200', 'bg-red-50', 'text-red-900');
                                } finally {
                                    chatBtn.disabled = false;
                                    chatBtn.textContent = chatLabels.defaultButton;
                                }
                            });
                        }

                        async function runChatUnreplied(dryRun) {
                            const runBtn = document.getElementById('wa-run-chat-unreplied-btn');
                            const dryBtn = document.getElementById('wa-run-chat-unreplied-dry-btn');
                            const runResultBox = document.getElementById('wa-run-chat-unreplied-result');
                            if (!runBtn || !dryBtn || !runResultBox) return;

                            runBtn.disabled = true;
                            dryBtn.disabled = true;
                            runResultBox.classList.add('hidden');
                            runResultBox.textContent = '';

                            const formData = new FormData(form);
                            formData.set('dry_run', dryRun ? '1' : '0');

                            try {
                                const response = await fetch(@json(route('admin.whatsapp-notify-settings.run-chat-unreplied')), {
                                    method: 'POST',
                                    headers: {
                                        'X-CSRF-TOKEN': @json(csrf_token()),
                                        'Accept': 'application/json',
                                    },
                                    body: formData,
                                });

                                const data = await response.json();
                                let text = data.message || '';
                                if (data.errors) {
                                    text = Object.values(data.errors).flat().join('\n');
                                }

                                runResultBox.textContent = text;
                                runResultBox.style.whiteSpace = 'pre-wrap';
                                runResultBox.classList.remove('hidden');
                                runResultBox.classList.toggle('border-emerald-200', response.ok);
                                runResultBox.classList.toggle('bg-emerald-50', response.ok);
                                runResultBox.classList.toggle('text-emerald-900', response.ok);
                                runResultBox.classList.toggle('border-red-200', !response.ok);
                                runResultBox.classList.toggle('bg-red-50', !response.ok);
                                runResultBox.classList.toggle('text-red-900', !response.ok);
                            } catch (error) {
                                runResultBox.textContent = @json(__('admin.whatsapp_notify.test_request_failed'));
                                runResultBox.classList.remove('hidden');
                                runResultBox.classList.add('border-red-200', 'bg-red-50', 'text-red-900');
                            } finally {
                                runBtn.disabled = false;
                                dryBtn.disabled = false;
                            }
                        }

                        const runChatBtn = document.getElementById('wa-run-chat-unreplied-btn');
                        const runChatDryBtn = document.getElementById('wa-run-chat-unreplied-dry-btn');
                        if (runChatBtn) {
                            runChatBtn.addEventListener('click', function () {
                                runChatUnreplied(false);
                            });
                        }
                        if (runChatDryBtn) {
                            runChatDryBtn.addEventListener('click', function () {
                                runChatUnreplied(true);
                            });
                        }
                    })();
                </script>
            @endpush
        </x-page-container>
    </div>
</x-app-layout>
