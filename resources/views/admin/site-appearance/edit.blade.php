<x-app-layout>
    <div class="py-8 sm:py-12">
        <x-page-container class="space-y-6">
            <div class="relative overflow-hidden rounded-3xl border border-slate-200 bg-gradient-to-br from-slate-900 via-brand-900 to-amber-950 p-8 text-white shadow-xl ring-1 ring-white/10">
                <div class="relative">
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-brand-200/90">{{ __('admin.appearance.badge') }}</p>
                    <h1 class="mt-2 text-2xl font-bold tracking-tight">{{ __('admin.appearance.title') }}</h1>
                    <p class="mt-2 max-w-xl text-sm leading-relaxed text-white/80">{{ __('admin.appearance.subtitle') }}</p>
                    <div class="mt-6 flex flex-wrap gap-3">
                        <a href="{{ route('dashboard') }}" class="inline-flex items-center rounded-xl bg-white px-5 py-2.5 text-sm font-semibold text-slate-900 shadow-sm hover:bg-brand-50">
                            {{ __('admin.appearance.back_dashboard') }}
                        </a>
                    </x-page-container>
                </div>
            </div>

            @if (session('status'))
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">
                    {{ session('status') }}
                </div>
            @endif
            @if (session('error'))
                <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900">
                    {{ session('error') }}
                </div>
            @endif

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-sm font-semibold text-slate-900">{{ __('admin.appearance.current_heading') }}</h2>

                @if ($logoUrl)
                    <div class="mt-4 flex items-center gap-4 rounded-xl bg-slate-50 p-4">
                        <img src="{{ $logoUrl }}" alt="" class="h-16 w-auto max-w-[200px] object-contain">
                    </div>
                @else
                    <p class="mt-3 text-sm text-slate-600">{{ __('admin.appearance.none') }}</p>
                @endif

                <p class="mt-4 text-xs leading-relaxed text-slate-500">{{ __('admin.appearance.storage_note') }}</p>

                <form method="post" action="{{ route('admin.site-appearance.update') }}" enctype="multipart/form-data" class="mt-6 space-y-5">
                    @csrf

                    <div>
                        <x-input-label for="logo" :value="__('admin.appearance.field_logo')" />
                        <input
                            id="logo"
                            name="logo"
                            type="file"
                            accept=".png,.jpg,.jpeg,.webp,.svg,image/png,image/jpeg,image/webp,image/svg+xml"
                            class="mt-2 block w-full text-sm text-slate-600 file:mr-4 file:rounded-lg file:border-0 file:bg-brand-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-brand-800 hover:file:bg-brand-100"
                        >
                        <p class="mt-1.5 text-xs text-slate-500">{{ __('admin.appearance.hint_format', ['max' => 2048]) }}</p>
                        <x-input-error :messages="$errors->get('logo')" class="mt-2" />
                    </div>

                    @if ($logoUrl)
                        <div class="flex items-start gap-3 rounded-xl border border-amber-100 bg-amber-50/80 p-4">
                            <input id="remove_logo" name="remove_logo" type="checkbox" value="1" class="mt-1 rounded border-amber-300 text-brand-600 focus:ring-brand-500">
                            <label for="remove_logo" class="text-sm text-amber-950">{{ __('admin.appearance.remove_label') }}</label>
                        </div>
                    @endif

                    <hr class="border-slate-200">

                    <div>
                        <h2 class="text-sm font-semibold text-slate-900">{{ __('admin.appearance.welcome_heading') }}</h2>
                        <p class="mt-1 text-xs leading-relaxed text-slate-500">{{ __('admin.appearance.welcome_intro') }}</p>

                        <div class="mt-4 overflow-hidden rounded-xl border border-slate-200 bg-slate-900/90">
                            <img
                                src="{{ $welcomeHeroPreviewUrl }}"
                                alt=""
                                class="h-44 w-full object-cover opacity-95 sm:h-52"
                                loading="lazy"
                                decoding="async"
                            >
                        </div>
                        <p class="mt-2 text-xs leading-relaxed text-slate-500">{{ __('admin.appearance.welcome_preview_note') }}</p>

                        <div class="mt-5 space-y-1.5">
                            <x-input-label for="welcome_hero_external_url" :value="__('admin.appearance.welcome_field_url')" />
                            <input
                                id="welcome_hero_external_url"
                                name="welcome_hero_external_url"
                                type="url"
                                autocomplete="off"
                                placeholder="https://…"
                                value="{{ old('welcome_hero_external_url', $welcomeHeroExternalUrl) }}"
                                class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-brand-500 focus:ring-brand-500"
                            >
                            <p class="text-xs text-slate-500">{{ __('admin.appearance.welcome_url_hint') }}</p>
                            <x-input-error :messages="$errors->get('welcome_hero_external_url')" class="mt-1" />
                        </div>

                        <div class="mt-5">
                            <x-input-label for="welcome_hero_image" :value="__('admin.appearance.welcome_field_upload')" />
                            <input
                                id="welcome_hero_image"
                                name="welcome_hero_image"
                                type="file"
                                accept=".png,.jpg,.jpeg,.webp,image/png,image/jpeg,image/webp"
                                class="mt-2 block w-full text-sm text-slate-600 file:mr-4 file:rounded-lg file:border-0 file:bg-brand-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-brand-800 hover:file:bg-brand-100"
                            >
                            <p class="mt-1.5 text-xs text-slate-500">{{ __('admin.appearance.welcome_upload_hint', ['max' => 5120]) }}</p>
                            <x-input-error :messages="$errors->get('welcome_hero_image')" class="mt-2" />
                        </div>

                        <div class="mt-5 grid gap-4 sm:grid-cols-3">
                            @php
                                $wp = config('welcome.hero.object_position');
                            @endphp
                            <div>
                                <x-input-label for="welcome_hero_object_position_base" :value="__('admin.appearance.welcome_field_pos_base')" />
                                <input
                                    id="welcome_hero_object_position_base"
                                    name="welcome_hero_object_position_base"
                                    type="text"
                                    maxlength="48"
                                    placeholder="{{ $wp['base'] ?? '' }}"
                                    value="{{ old('welcome_hero_object_position_base', $welcomePositionsBase) }}"
                                    class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm placeholder:text-slate-400 focus:border-brand-500 focus:ring-brand-500"
                                >
                                <x-input-error :messages="$errors->get('welcome_hero_object_position_base')" class="mt-1" />
                            </div>
                            <div>
                                <x-input-label for="welcome_hero_object_position_sm" :value="__('admin.appearance.welcome_field_pos_sm')" />
                                <input
                                    id="welcome_hero_object_position_sm"
                                    name="welcome_hero_object_position_sm"
                                    type="text"
                                    maxlength="48"
                                    placeholder="{{ $wp['sm'] ?? '' }}"
                                    value="{{ old('welcome_hero_object_position_sm', $welcomePositionsSm) }}"
                                    class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm placeholder:text-slate-400 focus:border-brand-500 focus:ring-brand-500"
                                >
                                <x-input-error :messages="$errors->get('welcome_hero_object_position_sm')" class="mt-1" />
                            </div>
                            <div class="sm:col-span-1">
                                <x-input-label for="welcome_hero_object_position_lg" :value="__('admin.appearance.welcome_field_pos_lg')" />
                                <input
                                    id="welcome_hero_object_position_lg"
                                    name="welcome_hero_object_position_lg"
                                    type="text"
                                    maxlength="48"
                                    placeholder="{{ $wp['lg'] ?? '' }}"
                                    value="{{ old('welcome_hero_object_position_lg', $welcomePositionsLg) }}"
                                    class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm placeholder:text-slate-400 focus:border-brand-500 focus:ring-brand-500"
                                >
                                <x-input-error :messages="$errors->get('welcome_hero_object_position_lg')" class="mt-1" />
                            </div>
                        </div>
                        <p class="mt-3 text-xs text-slate-500">{{ __('admin.appearance.welcome_pos_hint') }}</p>

                        @if (($welcomeHeroExternalUrl !== '') || $welcomeHeroHasUpload)
                            <div class="mt-6 flex items-start gap-3 rounded-xl border border-amber-100 bg-amber-50/80 p-4">
                                <input id="remove_welcome_hero_custom" name="remove_welcome_hero_custom" type="checkbox" value="1" class="mt-1 rounded border-amber-300 text-brand-600 focus:ring-brand-500">
                                <label for="remove_welcome_hero_custom" class="text-sm text-amber-950">{{ __('admin.appearance.welcome_remove_label') }}</label>
                            </div>
                        @endif
                    </div>

                    <div class="flex flex-wrap gap-3">
                        <x-primary-button type="submit">{{ __('admin.appearance.save') }}</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
