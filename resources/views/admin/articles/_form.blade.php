@php
    $field = static function (string $lc, string $key) use ($article): string {
        $oldKey = 'loc.'.$lc.'.'.$key;
        $old = request()->old($oldKey);
        if ($old !== null) {
            return (string) $old;
        }
        if (! $article->exists) {
            return '';
        }

        return (string) ($article->translationBlock($lc)[$key] ?? '');
    };
@endphp

<div class="space-y-8">
    {{-- Metadata --}}
    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-base font-semibold text-slate-900">{{ __('admin.articles.section_meta') }}</h2>
        <div class="mt-5 grid gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <x-input-label for="slug" :value="__('admin.articles.field_slug')" />
                <x-text-input id="slug" name="slug" type="text" class="mt-1 block w-full" required autofocus
                    x-model="slug" />
                <p class="mt-1 text-xs text-slate-500">{{ __('admin.articles.slug_hint') }}</p>
                <x-input-error class="mt-2" :messages="$errors->get('slug')" />
            </div>
            <div>
                <x-input-label for="sort_order" :value="__('admin.articles.field_sort')" />
                <x-text-input id="sort_order" name="sort_order" type="number" min="0" max="99999" class="mt-1 block w-full" required
                    :value="old('sort_order', $article->sort_order ?? 0)" />
                <x-input-error class="mt-2" :messages="$errors->get('sort_order')" />
            </div>
            <div>
                <x-input-label for="published_at" :value="__('admin.articles.field_published_at')" />
                <x-text-input id="published_at" name="published_at" type="datetime-local" class="mt-1 block w-full"
                    x-model="publishedAtValue" />
                <p class="mt-1 text-xs text-slate-500">{{ __('admin.articles.published_at_hint') }}</p>
                <x-input-error class="mt-2" :messages="$errors->get('published_at')" />
            </div>
            <div class="flex flex-wrap gap-6 sm:col-span-2">
                <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                    <input type="hidden" name="is_published" value="0" />
                    <input type="checkbox" name="is_published" value="1" class="rounded border-slate-300 text-brand-600 focus:ring-brand-500" @checked(old('is_published', $article->is_published ?? true)) />
                    <span>{{ __('admin.articles.field_published') }}</span>
                </label>
                <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                    <input type="hidden" name="is_featured" value="0" />
                    <input type="checkbox" name="is_featured" value="1" class="rounded border-slate-300 text-brand-600 focus:ring-brand-500" @checked(old('is_featured', $article->is_featured ?? false)) />
                    <span>{{ __('admin.articles.field_featured') }}</span>
                </label>
            </div>
        </div>
    </section>

    <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
        <p class="text-sm font-semibold text-slate-800">{{ __('admin.articles.section_content') }}</p>
        <div class="flex flex-wrap gap-2" role="tablist" aria-label="{{ __('admin.articles.locale_tabs_aria') }}">
            @foreach (['id' => 'ID', 'en' => 'EN', 'ar' => 'AR'] as $locale => $label)
                <button
                    type="button"
                    class="rounded-full px-3 py-1.5 text-xs font-bold transition"
                    :class="activeLocale === '{{ $locale }}' ? 'bg-baytgo text-white shadow-sm shadow-baytgo/25' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'"
                    @click="activeLocale = '{{ $locale }}'"
                    role="tab"
                    :aria-selected="activeLocale === '{{ $locale }}'"
                >
                    {{ $label }}
                </button>
            @endforeach
        </div>
    </div>

    <div class="rounded-2xl border border-emerald-200/70 bg-emerald-50/50 px-4 py-3 text-sm text-emerald-950">
        <p class="font-semibold text-emerald-900">{{ __('admin.articles.editor_hint_title') }}</p>
        <p class="mt-1 text-emerald-900/90">{{ __('admin.articles.editor_hint_body') }}</p>
        <p class="mt-2 text-xs text-emerald-800/80">{{ __('admin.articles.preview_sync_hint') }}</p>
    </div>

    @foreach (['id' => 'ID', 'en' => 'EN', 'ar' => 'AR'] as $locale => $label)
        <section
            class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"
            x-show="activeLocale === '{{ $locale }}'"
            x-cloak
            role="tabpanel"
        >
            <h3 class="text-lg font-semibold text-slate-900">{{ __('admin.articles.locale_heading', ['locale' => $label]) }}</h3>
            <div class="mt-4 grid gap-4">
                <div>
                    <div class="flex items-end justify-between gap-2">
                        <x-input-label :for="'title_'.$locale" :value="__('admin.articles.field_title')" />
                        <span class="text-[11px] font-medium text-slate-400"><span x-text="locales['{{ $locale }}'].title.length"></span>/255</span>
                    </div>
                    <x-text-input
                        :id="'title_'.$locale"
                        :name="'loc['.$locale.'][title]'"
                        type="text"
                        class="mt-1 block w-full"
                        maxlength="255"
                        x-model="locales['{{ $locale }}'].title"
                        required
                    />
                    <x-input-error class="mt-2" :messages="$errors->get('loc.'.$locale.'.title')" />
                </div>
                <div>
                    <div class="flex items-end justify-between gap-2">
                        <x-input-label :for="'excerpt_'.$locale" :value="__('admin.articles.field_excerpt')" />
                        <span class="text-[11px] font-medium tabular-nums text-slate-400"><span x-text="locales['{{ $locale }}'].excerpt.length"></span> {{ __('admin.articles.chars_unit') }}</span>
                    </div>
                    <textarea
                        :id="'excerpt_'.$locale"
                        :name="'loc['.$locale.'][excerpt]'"
                        rows="4"
                        class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500"
                        x-model="locales['{{ $locale }}'].excerpt"
                        required
                    ></textarea>
                    <x-input-error class="mt-2" :messages="$errors->get('loc.'.$locale.'.excerpt')" />
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <x-input-label :for="'cat_'.$locale" :value="__('admin.articles.field_category')" />
                        <x-text-input
                            :id="'cat_'.$locale"
                            :name="'loc['.$locale.'][category]'"
                            type="text"
                            class="mt-1 block w-full"
                            x-model="locales['{{ $locale }}'].category"
                            required
                        />
                        <x-input-error class="mt-2" :messages="$errors->get('loc.'.$locale.'.category')" />
                    </div>
                    <div>
                        <x-input-label :for="'author_'.$locale" :value="__('admin.articles.field_author')" />
                        <x-text-input
                            :id="'author_'.$locale"
                            :name="'loc['.$locale.'][author]'"
                            type="text"
                            class="mt-1 block w-full"
                            x-model="locales['{{ $locale }}'].author"
                            required
                        />
                        <x-input-error class="mt-2" :messages="$errors->get('loc.'.$locale.'.author')" />
                    </div>
                </div>
                <div>
                    <x-input-label :for="'ckeditor_body_'.$locale" :value="__('admin.articles.field_body_html')" />
                    <textarea
                        :id="'ckeditor_body_'.$locale"
                        :name="'loc['.$locale.'][body]'"
                        rows="12"
                        class="mt-1 block w-full rounded-lg border-slate-300 font-mono text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500"
                    >{{ $field($locale, 'body') }}</textarea>
                    <x-input-error class="mt-2" :messages="$errors->get('loc.'.$locale.'.body')" />
                </div>
            </div>
        </section>
    @endforeach

    <div class="flex flex-col gap-3 border-t border-slate-200 pt-6 sm:flex-row sm:flex-wrap sm:items-center sm:justify-between">
        <p class="text-xs text-slate-500">{{ __('admin.articles.footer_save_hint') }}</p>
        <div class="flex flex-wrap gap-2">
            <button type="button" class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-800 shadow-sm hover:bg-slate-50" @click="setPublished(false)">
                {{ __('admin.articles.save_draft') }}
            </button>
            <button type="button" class="inline-flex items-center justify-center rounded-xl bg-baytgo px-4 py-2.5 text-sm font-bold text-white shadow-md shadow-baytgo/25 hover:bg-baytgo-800" @click="setPublished(true)">
                {{ __('admin.articles.publish_article') }}
            </button>
            <a href="{{ route('admin.articles.index') }}" class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">{{ __('admin.articles.cancel') }}</a>
        </div>
    </div>
</div>
