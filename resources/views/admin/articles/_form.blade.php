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

<div class="space-y-6">
    {{-- Card 1: Informasi artikel --}}
    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-base font-bold text-slate-900 mb-6">Informasi artikel</h2>
        <div class="grid gap-6 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <x-input-label for="slug" :value="__('admin.articles.field_slug')" />
                <div class="flex items-center gap-2 mt-1">
                    <x-text-input id="slug" name="slug" type="text" class="block w-full" required autofocus x-model="slug" />
                </div>
                <p class="mt-2 text-xs text-brand-600">
                    {{ url('artikel') }}/<span x-text="slug || '...'"></span> 
                    <a href="#" @click.prevent="$refs.slugInput?.focus()" class="ml-2 font-medium hover:underline">Edit slug</a>
                </p>
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

            <div class="flex flex-wrap gap-6 sm:col-span-2 mt-2">
                <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                    <input type="hidden" name="is_published" value="0" />
                    <input type="checkbox" name="is_published" value="1" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500" @checked(old('is_published', $article->is_published ?? true)) />
                    <span class="font-medium">Diterbitkan</span>
                </label>
                <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                    <input type="hidden" name="is_featured" value="0" />
                    <input type="checkbox" name="is_featured" value="1" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500" @checked(old('is_featured', $article->is_featured ?? false)) />
                    <span>Unggulan di indeks</span>
                </label>
            </div>
        </div>
    </section>

    {{-- Card 2: Detail & Bahasa --}}
    <section class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
        <div class="border-b border-slate-100 bg-slate-50/50 px-6 py-4 flex flex-wrap items-center justify-between gap-4">
            <h2 class="text-base font-bold text-slate-900">Detail & bahasa</h2>
            <div class="flex flex-wrap gap-2" role="tablist">
                @foreach (['id' => 'ID', 'en' => 'EN', 'ar' => 'AR'] as $locale => $label)
                    <button
                        type="button"
                        class="rounded-full px-4 py-1.5 text-xs font-bold transition-all duration-200"
                        :class="activeLocale === '{{ $locale }}' ? 'bg-slate-800 text-white shadow-md' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-100'"
                        @click="activeLocale = '{{ $locale }}'"
                        role="tab"
                        :aria-selected="activeLocale === '{{ $locale }}'"
                    >
                        {{ $label }}
                    </button>
                @endforeach
            </div>
        </div>

        <div class="p-6">
            @foreach (['id' => 'ID', 'en' => 'EN', 'ar' => 'AR'] as $locale => $label)
                <div
                    x-show="activeLocale === '{{ $locale }}'"
                    x-cloak
                    role="tabpanel"
                    class="space-y-6"
                >
                    <div>
                        <div class="flex items-end justify-between gap-2">
                            <x-input-label :for="'title_'.$locale" :value="'Judul (' . $label . ')'" />
                            <span class="text-[11px] font-medium text-slate-400"><span x-text="locales['{{ $locale }}']?.title?.length || 0"></span>/255</span>
                        </div>
                        <x-text-input
                            id="title_{{ $locale }}"
                            name="loc[{{ $locale }}][title]"
                            type="text"
                            class="mt-1 block w-full"
                            maxlength="255"
                            x-model="locales['{{ $locale }}'].title"
                            :required="$locale === 'id'"
                        />
                        <x-input-error class="mt-2" :messages="$errors->get('loc.'.$locale.'.title')" />
                    </div>

                    <div>
                        <div class="flex items-end justify-between gap-2">
                            <x-input-label :for="'excerpt_'.$locale" :value="'Ringkasan (' . $label . ')'" />
                            <span class="text-[11px] font-medium tabular-nums text-slate-400"><span x-text="locales['{{ $locale }}']?.excerpt?.length || 0"></span> / 500</span>
                        </div>
                        <textarea
                            id="excerpt_{{ $locale }}"
                            name="loc[{{ $locale }}][excerpt]"
                            rows="3"
                            class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                            x-model="locales['{{ $locale }}'].excerpt"
                            @required($locale === 'id')
                            maxlength="500"
                        ></textarea>
                        <x-input-error class="mt-2" :messages="$errors->get('loc.'.$locale.'.excerpt')" />
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <x-input-label :for="'cat_'.$locale" :value="__('admin.articles.field_category')" />
                            <x-text-input id="cat_{{ $locale }}" name="loc[{{ $locale }}][category]" type="text" class="mt-1 block w-full" x-model="locales['{{ $locale }}'].category" />
                        </div>
                        <div>
                            <x-input-label :for="'author_'.$locale" :value="__('admin.articles.field_author')" />
                            <x-text-input id="author_{{ $locale }}" name="loc[{{ $locale }}][author]" type="text" class="mt-1 block w-full" x-model="locales['{{ $locale }}'].author" />
                        </div>
                    </div>

                    <div>
                        <x-input-label :for="'editorjs_'.$locale" :value="'Isi artikel (' . $label . ')'" />
                        <div class="mt-2 overflow-hidden rounded-xl border border-slate-300 bg-white shadow-sm focus-within:border-emerald-500 focus-within:ring-1 focus-within:ring-emerald-500">
                            <div id="editorjs_{{ $locale }}" class="editorjs-container prose prose-sm sm:prose max-w-none text-base"></div>
                        </div>
                        <input type="hidden" id="editorjs_input_html_{{ $locale }}" name="loc[{{ $locale }}][body]" value="{{ $field($locale, 'body') }}">
                        <input type="hidden" id="editorjs_input_json_{{ $locale }}" name="loc[{{ $locale }}][body_json]" value="{{ $field($locale, 'body_json') }}">
                        <x-input-error class="mt-2" :messages="$errors->get('loc.'.$locale.'.body')" />
                        <x-input-error class="mt-2" :messages="$errors->get('loc.'.$locale.'.body_json')" />
                    </div>
                </div>
            @endforeach
        </div>
    </section>
</div>
