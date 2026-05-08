<x-app-layout>
    @include('admin.articles._editor_config', ['article' => $article])
    <div
        class="admin-articles-page relative min-h-[calc(100vh-4rem)] overflow-hidden bg-gradient-to-b from-slate-100 via-slate-50 to-white py-8 sm:py-12"
        x-data="articleAdminEditor(@js($articleEditorConfig))"
    >
        <div class="relative mx-auto max-w-[1600px] px-4 sm:px-6 lg:px-8">
            <div class="grid gap-8 xl:grid-cols-[minmax(0,1fr)_minmax(300px,400px)] 2xl:grid-cols-[minmax(0,1fr)_440px]">
                <div class="min-w-0">
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-brand-700">{{ __('admin.articles.badge') }}</p>
                    <h1 class="mt-1 text-2xl font-bold text-slate-900">{{ __('admin.articles.edit_title') }}</h1>
                    <p class="mt-2 text-sm text-slate-600">{{ __('admin.articles.edit_sub', ['slug' => $article->slug]) }}</p>

                    @if ($article->is_published && $article->published_at && $article->published_at->isPast())
                        <p class="mt-4">
                            <a href="{{ route('articles.show', ['slug' => $article->slug]) }}" target="_blank" rel="noopener" class="text-sm font-semibold text-brand-700 hover:underline">{{ __('admin.articles.preview_public') }}</a>
                        </p>
                    @endif

                    <form id="article-admin-form" action="{{ route('admin.articles.update', $article) }}" method="post" class="mt-10 space-y-8">
                        @csrf
                        @method('PUT')
                        @include('admin.articles._form', ['article' => $article])
                    </form>
                </div>
                @include('admin.articles._preview')
            </div>
        </div>
    </div>
    @include('admin.articles._ckeditor')
</x-app-layout>
