<x-app-layout>
    <div class="ui-page-y">
        <x-page-container class="ui-stack-compact">
            <div class="relative overflow-hidden rounded-3xl border border-slate-200 bg-gradient-to-br from-slate-900 via-slate-800 to-teal-950 p-8 text-white shadow-xl ring-1 ring-white/10">
                <div class="relative">
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-teal-200/90">{{ __('admin.moota_api.badge') }}</p>
                    <h1 class="mt-2 text-2xl font-bold tracking-tight">{{ __('admin.moota_api.title') }}</h1>
                    <p class="mt-2 max-w-xl text-sm leading-relaxed text-white/80">{{ __('admin.moota_api.subtitle') }}</p>
                    <div class="mt-6 flex flex-wrap gap-3">
                        <a href="{{ route('admin.settings.index') }}" class="inline-flex items-center rounded-xl bg-white/10 px-5 py-2.5 text-sm font-semibold text-white ring-1 ring-white/20 hover:bg-white/20">
                            {{ __('admin.moota_api.back_settings') }}
                        </a>
                    </div>
                </div>
            </div>

            @if (session('status'))
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-900">
                    {{ session('status') }}
                </div>
            @endif

            @if ($envTokenSet)
                <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-950">
                    {{ __('admin.moota_api.env_override_hint') }}
                </div>
            @elseif (! $dbTokenSet)
                <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-950">
                    {{ __('admin.moota_api.token_missing') }}
                </div>
            @else
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-950">
                    {{ __('admin.moota_api.token_present') }}
                </div>
            @endif

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-sm font-bold text-slate-900">{{ __('admin.moota_api.status_heading') }}</h2>
                <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-2">
                    <div>
                        <dt class="text-xs font-medium text-slate-500">{{ __('admin.moota_api.api_base_url') }}</dt>
                        <dd class="mt-0.5 font-mono text-slate-800 break-all">{{ $apiBaseUrl }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-slate-500">{{ __('admin.moota_api.api_email') }}</dt>
                        <dd class="mt-0.5 text-slate-800">{{ $apiEmail !== '' ? $apiEmail : '—' }}</dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-xs font-medium text-slate-500">{{ __('admin.moota_api.bank_account_ids') }}</dt>
                        <dd class="mt-0.5 font-mono text-slate-800 break-all">
                            {{ $bankAccountIds !== [] ? implode(', ', $bankAccountIds) : '—' }}
                        </dd>
                    </div>
                </dl>
            </div>

            <form method="post" action="{{ route('admin.moota-api-settings.update') }}" class="space-y-6">
                @csrf

                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="text-sm font-bold text-slate-900">{{ __('admin.moota_api.token_heading') }}</h2>
                    <p class="mt-1 text-xs text-slate-500">{{ __('admin.moota_api.token_hint') }}</p>

                    <div class="mt-5">
                        <x-input-label for="access_token" :value="__('admin.moota_api.access_token')" />
                        <textarea
                            id="access_token"
                            name="access_token"
                            rows="4"
                            autocomplete="off"
                            placeholder="{{ ($envTokenSet || $dbTokenSet) ? __('admin.moota_api.access_token_placeholder_set') : __('admin.moota_api.access_token_placeholder') }}"
                            class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 font-mono text-sm text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500"
                        >{{ old('access_token') }}</textarea>
                        <x-input-error :messages="$errors->get('access_token')" class="mt-2" />
                    </div>

                    <label class="mt-4 flex items-start gap-2 text-sm text-slate-700">
                        <input type="checkbox" name="clear_token" value="1" class="mt-1 rounded border-slate-300 text-teal-700 focus:ring-teal-500" @checked(old('clear_token'))>
                        <span>{{ __('admin.moota_api.clear_token') }}</span>
                    </label>
                </div>

                <div class="flex flex-wrap gap-3">
                    <button type="submit" class="inline-flex items-center rounded-xl bg-teal-800 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-teal-700">
                        {{ __('admin.moota_api.save') }}
                    </button>
                    <a href="{{ route('admin.settings.index') }}" class="inline-flex items-center rounded-xl bg-white px-5 py-2.5 text-sm font-semibold text-slate-800 ring-1 ring-slate-200 hover:bg-slate-50">
                        {{ __('admin.moota_api.cancel') }}
                    </a>
                </div>
            </form>
        </x-page-container>
    </div>
</x-app-layout>
