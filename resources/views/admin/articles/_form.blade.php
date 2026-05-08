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
    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <x-input-label for="slug" :value="__('admin.articles.field_slug')" />
            <x-text-input id="slug" name="slug" type="text" class="mt-1 block w-full" :value="old('slug', $article->slug)" required autofocus />
            <p class="mt-1 text-xs text-slate-500">{{ __('admin.articles.slug_hint') }}</p>
            <x-input-error class="mt-2" :messages="$errors->get('slug')" />
        </div>
        <div>
            <x-input-label for="sort_order" :value="__('admin.articles.field_sort')" />
            <x-text-input id="sort_order" name="sort_order" type="number" min="0" max="99999" class="mt-1 block w-full" :value="old('sort_order', $article->sort_order ?? 0)" required />
            <x-input-error class="mt-2" :messages="$errors->get('sort_order')" />
        </div>
        <div class="sm:col-span-2">
            <x-input-label for="published_at" :value="__('admin.articles.field_published_at')" />
            <x-text-input id="published_at" name="published_at" type="datetime-local" class="mt-1 block w-full max-w-md" :value="old('published_at', $article->published_at?->format('Y-m-d\TH:i') ?? '')" />
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

    <div class="rounded-2xl border border-slate-200 bg-slate-50/80 p-4 text-sm text-slate-600">
        <p class="font-semibold text-slate-800">{{ __('admin.articles.editor_hint_title') }}</p>
        <p class="mt-1">{{ __('admin.articles.editor_hint_body') }}</p>
    </div>

    @foreach (['id' => 'ID', 'en' => 'EN', 'ar' => 'AR'] as $locale => $label)
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-lg font-semibold text-slate-900">{{ __('admin.articles.locale_heading', ['locale' => $label]) }}</h3>
            <div class="mt-4 grid gap-4">
                <div>
                    <x-input-label :for="'title_'.$locale" :value="__('admin.articles.field_title')" />
                    <x-text-input :id="'title_'.$locale" :name="'loc['.$locale.'][title]'" type="text" class="mt-1 block w-full" :value="$field($locale, 'title')" required />
                    <x-input-error class="mt-2" :messages="$errors->get('loc.'.$locale.'.title')" />
                </div>
                <div>
                    <x-input-label :for="'excerpt_'.$locale" :value="__('admin.articles.field_excerpt')" />
                    <textarea :id="'excerpt_'.$locale" :name="'loc['.$locale.'][excerpt]'" rows="3" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" required>{{ $field($locale, 'excerpt') }}</textarea>
                    <x-input-error class="mt-2" :messages="$errors->get('loc.'.$locale.'.excerpt')" />
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <x-input-label :for="'cat_'.$locale" :value="__('admin.articles.field_category')" />
                        <x-text-input :id="'cat_'.$locale" :name="'loc['.$locale.'][category]'" type="text" class="mt-1 block w-full" :value="$field($locale, 'category')" required />
                        <x-input-error class="mt-2" :messages="$errors->get('loc.'.$locale.'.category')" />
                    </div>
                    <div>
                        <x-input-label :for="'author_'.$locale" :value="__('admin.articles.field_author')" />
                        <x-text-input :id="'author_'.$locale" :name="'loc['.$locale.'][author]'" type="text" class="mt-1 block w-full" :value="$field($locale, 'author')" required />
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
</div>
