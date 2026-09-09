<x-app-layout>
    <div class="ui-page-y">
        <x-page-container class="ui-stack-compact">
            <div class="relative overflow-hidden rounded-3xl border border-slate-200 bg-gradient-to-br from-slate-900 via-emerald-950 to-brand-950 p-8 text-white shadow-xl ring-1 ring-white/10">
                <div class="relative">
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-emerald-200/90">{{ __('admin.seo_meta.badge') }}</p>
                    <h1 class="mt-2 text-2xl font-bold tracking-tight">{{ __('admin.seo_meta.title') }}</h1>
                    <p class="mt-2 max-w-2xl text-sm leading-relaxed text-white/80">{{ __('admin.seo_meta.subtitle') }}</p>
                    <div class="mt-6 flex flex-wrap gap-3">
                        <a href="{{ route('admin.settings.index') }}" class="inline-flex items-center rounded-xl bg-white/10 px-5 py-2.5 text-sm font-semibold text-white ring-1 ring-white/20 hover:bg-white/20">
                            {{ __('admin.seo_meta.back_settings') }}
                        </a>
                    </div>
                </div>
            </div>

            @if (session('status'))
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-900">
                    {{ session('status') }}
                </div>
            @endif

            <div class="rounded-xl border border-sky-200 bg-sky-50 px-4 py-3 text-xs leading-relaxed text-sky-900">
                {{ __('admin.seo_meta.help', ['app' => config('app.name', 'Bayt-GO')]) }}
            </div>

            <form method="post" action="{{ route('admin.seo-meta-settings.update') }}" class="space-y-6">
                @csrf

                @foreach ($pages as $page => $meta)
                    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                        <div class="flex flex-wrap items-baseline justify-between gap-2">
                            <h2 class="text-sm font-bold text-slate-900">{{ __($meta['label']) }}</h2>
                            <a href="{{ $meta['url'] }}" target="_blank" rel="noopener" class="text-xs font-medium text-baytgo hover:underline">{{ $meta['url'] }}</a>
                        </div>

                        <div class="mt-5 grid gap-6 lg:grid-cols-2">
                            @foreach ($locales as $locale)
                                @php
                                    $titleName = \App\Support\SeoMetaOverrides::inputName($page, $locale, 'title');
                                    $descName = \App\Support\SeoMetaOverrides::inputName($page, $locale, 'description');
                                @endphp
                                <div class="space-y-4 rounded-xl border border-slate-100 bg-slate-50/60 p-4">
                                    <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500">{{ __('admin.seo_meta.locale_'.$locale) }}</p>

                                    <div>
                                        <x-input-label :for="$titleName" :value="__('admin.seo_meta.field_title')" />
                                        <x-text-input
                                            :id="$titleName"
                                            :name="$titleName"
                                            type="text"
                                            class="mt-1 block w-full"
                                            :maxlength="\App\Support\SeoMetaOverrides::TITLE_MAX"
                                            :value="old($titleName, $values[$page][$locale]['title'])"
                                            :placeholder="__('admin.seo_meta.placeholder_default')"
                                        />
                                        <p class="mt-1 text-xs text-slate-500">{{ __('admin.seo_meta.title_hint', ['max' => \App\Support\SeoMetaOverrides::TITLE_MAX]) }}</p>
                                        <x-input-error :messages="$errors->get($titleName)" class="mt-2" />
                                    </div>

                                    <div>
                                        <x-input-label :for="$descName" :value="__('admin.seo_meta.field_description')" />
                                        <textarea
                                            id="{{ $descName }}"
                                            name="{{ $descName }}"
                                            rows="3"
                                            maxlength="{{ \App\Support\SeoMetaOverrides::DESCRIPTION_MAX }}"
                                            placeholder="{{ __('admin.seo_meta.placeholder_default') }}"
                                            class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-emerald-500 focus:ring-emerald-500"
                                        >{{ old($descName, $values[$page][$locale]['description']) }}</textarea>
                                        <p class="mt-1 text-xs text-slate-500">{{ __('admin.seo_meta.description_hint', ['max' => \App\Support\SeoMetaOverrides::DESCRIPTION_MAX]) }}</p>
                                        <x-input-error :messages="$errors->get($descName)" class="mt-2" />
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach

                <div class="flex justify-end">
                    <x-primary-button>
                        {{ __('admin.seo_meta.save') }}
                    </x-primary-button>
                </div>
            </form>
        </x-page-container>
    </div>
</x-app-layout>
