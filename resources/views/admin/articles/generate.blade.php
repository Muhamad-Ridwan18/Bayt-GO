<x-app-layout>
    <x-ui.app-page>
        <x-page-container class="ui-stack relative">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-brand-700">{{ __('admin.article_generate.badge') }}</p>
                    <h1 class="mt-1 text-2xl font-bold text-slate-900 sm:text-3xl">{{ __('admin.article_generate.title') }}</h1>
                    <p class="mt-2 max-w-xl text-sm text-slate-600">{{ __('admin.article_generate.subtitle') }}</p>
                </div>
                <a href="{{ route('admin.articles.index') }}" class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-slate-300">
                    {{ __('admin.article_generate.back_articles') }}
                </a>
            </div>

            @if (session('status'))
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</div>
            @endif
            @if (session('error'))
                <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
            @endif

            @if (! $webhookConfigured)
                <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                    {{ __('admin.article_generate.webhook_missing') }}
                </div>
            @endif

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <form method="POST" action="{{ route('admin.articles.generate.store') }}" class="space-y-5">
                    @csrf

                    <div>
                        <x-input-label for="niche" :value="__('admin.article_generate.field_niche')" />
                        <x-text-input id="niche" name="niche" type="text" class="mt-1 block w-full" required maxlength="120" :value="old('niche', $defaults['niche'])" />
                        <x-input-error class="mt-2" :messages="$errors->get('niche')" />
                    </div>

                    <div>
                        <x-input-label for="target_audience" :value="__('admin.article_generate.field_audience')" />
                        <x-text-input id="target_audience" name="target_audience" type="text" class="mt-1 block w-full" required maxlength="255" :value="old('target_audience', $defaults['target_audience'])" />
                        <x-input-error class="mt-2" :messages="$errors->get('target_audience')" />
                    </div>

                    <div>
                        <x-input-label for="keywords" :value="__('admin.article_generate.field_keywords')" />
                        <textarea id="keywords" name="keywords" rows="3" required maxlength="2000" class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">{{ old('keywords', $defaults['keywords']) }}</textarea>
                        <x-input-error class="mt-2" :messages="$errors->get('keywords')" />
                    </div>

                    <fieldset>
                        <legend class="block text-sm font-medium text-slate-700">{{ __('admin.article_generate.field_language') }}</legend>
                        <div class="mt-2 flex flex-wrap gap-4">
                            @foreach (['Indonesia', 'English', 'Arabic'] as $lang)
                                <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                                    <input
                                        type="checkbox"
                                        name="language[]"
                                        value="{{ $lang }}"
                                        class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500"
                                        @checked(in_array($lang, old('language', $defaults['language']), true))
                                    />
                                    <span>{{ $lang }}</span>
                                </label>
                            @endforeach
                        </div>
                        <x-input-error class="mt-2" :messages="$errors->get('language')" />
                    </fieldset>

                    <div class="pt-2">
                        <button
                            type="submit"
                            class="inline-flex items-center justify-center gap-2 rounded-xl bg-baytgo px-4 py-2.5 text-sm font-semibold text-white shadow-md shadow-baytgo/20 transition hover:bg-baytgo-800 disabled:cursor-not-allowed disabled:opacity-70"
                            @disabled(! $webhookConfigured)
                        >
                            {{ __('admin.article_generate.submit') }}
                        </button>
                    </div>
                </form>
            </div>
        </x-page-container>
    </x-ui.app-page>
</x-app-layout>
