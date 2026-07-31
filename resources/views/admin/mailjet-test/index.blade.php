<x-app-layout>
    <div class="ui-page-y">
        <x-page-container class="ui-stack-compact">
            <div class="relative overflow-hidden rounded-3xl border border-slate-200 bg-gradient-to-br from-slate-900 via-sky-950 to-indigo-950 p-8 text-white shadow-xl ring-1 ring-white/10">
                <div class="relative">
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-sky-200/90">{{ __('admin.mailjet_test.badge') }}</p>
                    <h1 class="mt-2 text-2xl font-bold tracking-tight">{{ __('admin.mailjet_test.title') }}</h1>
                    <p class="mt-2 max-w-xl text-sm leading-relaxed text-white/80">{{ __('admin.mailjet_test.subtitle') }}</p>
                    <div class="mt-6">
                        <a href="{{ route('admin.settings.index') }}" class="inline-flex items-center rounded-xl bg-white/10 px-4 py-2 text-sm font-semibold text-white ring-1 ring-white/20 hover:bg-white/15">
                            {{ __('admin.mailjet_test.back_settings') }}
                        </a>
                    </div>
                </div>
            </div>

            @if (session('status'))
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</div>
            @endif
            @if (session('error'))
                <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
            @endif

            @if (! $configured)
                <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                    {{ __('admin.mailjet_test.keys_missing') }}
                </div>
            @else
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                    {{ __('admin.mailjet_test.keys_ok') }}
                </div>
            @endif

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-sm font-bold text-slate-900">{{ __('admin.mailjet_test.settings_heading') }}</h2>
                <p class="mt-1 text-xs text-slate-500">{{ __('admin.mailjet_test.settings_hint') }}</p>

                <form method="POST" action="{{ route('admin.mailjet-test.update') }}" class="mt-5 space-y-4">
                    @csrf

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <x-input-label for="api_key" :value="__('admin.mailjet_test.api_key')" />
                            <x-text-input
                                id="api_key"
                                name="api_key"
                                type="password"
                                class="mt-1 block w-full"
                                autocomplete="off"
                                :placeholder="$settings['api_key_set'] ? __('admin.mailjet_test.secret_placeholder_set') : __('admin.mailjet_test.api_key_placeholder')"
                            />
                            @if ($settings['api_key_set'])
                                <p class="mt-1.5 text-xs text-emerald-700">{{ __('admin.mailjet_test.api_key_set_hint') }}</p>
                            @endif
                            <x-input-error :messages="$errors->get('api_key')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="secret_key" :value="__('admin.mailjet_test.secret_key')" />
                            <x-text-input
                                id="secret_key"
                                name="secret_key"
                                type="password"
                                class="mt-1 block w-full"
                                autocomplete="off"
                                :placeholder="$settings['secret_key_set'] ? __('admin.mailjet_test.secret_placeholder_set') : __('admin.mailjet_test.secret_key_placeholder')"
                            />
                            @if ($settings['secret_key_set'])
                                <p class="mt-1.5 text-xs text-emerald-700">{{ __('admin.mailjet_test.secret_key_set_hint') }}</p>
                            @endif
                            <x-input-error :messages="$errors->get('secret_key')" class="mt-2" />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <x-input-label for="from_address" :value="__('admin.mailjet_test.from_email')" />
                            <x-text-input
                                id="from_address"
                                name="from_address"
                                type="email"
                                class="mt-1 block w-full"
                                :value="old('from_address', $settings['from_address'])"
                            />
                            <p class="mt-1.5 text-xs text-slate-500">{{ __('admin.mailjet_test.from_verified_hint') }}</p>
                            <x-input-error :messages="$errors->get('from_address')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="from_name" :value="__('admin.mailjet_test.from_name')" />
                            <x-text-input
                                id="from_name"
                                name="from_name"
                                type="text"
                                class="mt-1 block w-full"
                                :value="old('from_name', $settings['from_name'])"
                            />
                            <x-input-error :messages="$errors->get('from_name')" class="mt-2" />
                        </div>
                    </div>

                    <div class="flex justify-end pt-2">
                        <x-submit-button class="rounded-xl bg-slate-900 px-6 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">
                            {{ __('admin.mailjet_test.save_settings') }}
                        </x-submit-button>
                    </div>
                </form>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-sm font-bold text-slate-900">{{ __('admin.mailjet_test.form_heading') }}</h2>
                <p class="mt-1 text-xs text-slate-500">{{ __('admin.mailjet_test.form_hint') }}</p>

                <form method="POST" action="{{ route('admin.mailjet-test.send') }}" class="mt-5 space-y-4">
                    @csrf

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <x-input-label for="from_email" :value="__('admin.mailjet_test.from_email')" />
                            <x-text-input
                                id="from_email"
                                name="from_email"
                                type="email"
                                class="mt-1 block w-full"
                                :value="old('from_email', $settings['from_address'])"
                                required
                            />
                            <x-input-error :messages="$errors->get('from_email')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="test_from_name" :value="__('admin.mailjet_test.from_name')" />
                            <x-text-input
                                id="test_from_name"
                                name="from_name"
                                type="text"
                                class="mt-1 block w-full"
                                :value="old('from_name', $settings['from_name'])"
                            />
                            <x-input-error :messages="$errors->get('from_name')" class="mt-2" />
                        </div>
                    </div>

                    <div>
                        <x-input-label for="to_email" :value="__('admin.mailjet_test.to_email')" />
                        <x-text-input
                            id="to_email"
                            name="to_email"
                            type="email"
                            class="mt-1 block w-full"
                            :value="old('to_email')"
                            required
                        />
                        <x-input-error :messages="$errors->get('to_email')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="subject" :value="__('admin.mailjet_test.subject')" />
                        <x-text-input
                            id="subject"
                            name="subject"
                            type="text"
                            class="mt-1 block w-full"
                            :value="old('subject', __('admin.mailjet_test.default_subject', ['app' => config('app.name', 'BaytGo')]))"
                            required
                        />
                        <x-input-error :messages="$errors->get('subject')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="body" :value="__('admin.mailjet_test.body')" />
                        <textarea
                            id="body"
                            name="body"
                            rows="6"
                            required
                            class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm focus:border-brand-500 focus:ring-brand-500 text-sm"
                        >{{ old('body', __('admin.mailjet_test.default_body', ['app' => config('app.name', 'BaytGo'), 'time' => now()->format('d/m/Y H:i')])) }}</textarea>
                        <x-input-error :messages="$errors->get('body')" class="mt-2" />
                    </div>

                    <div class="flex justify-end pt-2">
                        <x-submit-button class="rounded-xl bg-brand-600 px-6 py-2.5 text-sm font-semibold text-white hover:bg-brand-700" :disabled="! $configured">
                            {{ __('admin.mailjet_test.send_button') }}
                        </x-submit-button>
                    </div>
                </form>
            </div>
        </x-page-container>
    </div>
</x-app-layout>
