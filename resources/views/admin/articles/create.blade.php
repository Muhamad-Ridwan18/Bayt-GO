<x-app-layout>
    <div
        class="admin-articles-page relative min-h-[calc(100vh-4rem)] overflow-hidden bg-gradient-to-b from-slate-100 via-slate-50 to-white py-8 sm:py-12"
        x-data="articleAdminEditor(@js($articleEditorConfig))"
    >
        <div class="relative mx-auto max-w-[1600px] px-4 sm:px-6 lg:px-8">
            <div class="grid gap-8 xl:grid-cols-[minmax(0,1fr)_minmax(300px,400px)] 2xl:grid-cols-[minmax(0,1fr)_440px]">
                <div class="min-w-0">
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-brand-700">{{ __('admin.articles.badge') }}</p>
                    <h1 class="mt-1 text-2xl font-bold text-slate-900">{{ __('admin.articles.create_title') }}</h1>
                    <p class="mt-2 text-sm text-slate-600">{{ __('admin.articles.create_sub') }}</p>

                    <form id="article-admin-form" action="{{ route('admin.articles.store') }}" method="post" class="mt-10 space-y-8" novalidate>
                        @csrf
                        @include('admin.articles._form', ['article' => $article])
                    </form>
                </div>
                @include('admin.articles._preview')
            </div>
        </div>
    </div>
    @include('admin.articles._ckeditor')
</x-app-layout>
