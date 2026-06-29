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

            <form method="post" action="{{ route('admin.whatsapp-notify-settings.update') }}" class="space-y-6">
                @csrf

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
                    <textarea
                        id="admin_numbers"
                        name="admin_numbers"
                        rows="3"
                        class="mt-4 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-emerald-500 focus:ring-emerald-500"
                        placeholder="081234567890, 6281234567890"
                    >{{ old('admin_numbers', $adminNumbers) }}</textarea>
                    <x-input-error :messages="$errors->get('admin_numbers')" class="mt-2" />
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="inline-flex items-center rounded-xl bg-emerald-600 px-6 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700">
                        {{ __('admin.whatsapp_notify.save') }}
                    </button>
                </div>
            </form>
        </x-page-container>
    </div>
</x-app-layout>
