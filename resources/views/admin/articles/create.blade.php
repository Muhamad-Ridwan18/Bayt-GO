<x-app-layout>
    <div
        class="admin-articles-page relative min-h-[calc(100vh-4rem)] bg-gradient-to-b from-slate-50 to-white"
        x-data="articleAdminEditor(@js($articleEditorConfig))"
    >
        {{-- Header Bar --}}
        <div class="sticky top-0 z-20 border-b border-slate-200 bg-white/80 backdrop-blur-xl px-4 py-4 sm:px-6 lg:px-8">
            <div class="mx-auto flex max-w-[1600px] flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-xl font-bold tracking-tight text-slate-900">{{ __('admin.articles.create_title') }}</h1>
                    <p class="text-sm text-slate-500">{{ __('admin.articles.create_sub') }}</p>
                </div>
                <div class="flex items-center gap-3">
                    <span class="text-sm font-medium text-emerald-600 flex items-center gap-1">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                        Draft disimpan
                    </span>
                    <button type="button" class="inline-flex items-center justify-center gap-2 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50" @click="setPublished(false)">
                        <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                        Pratinjau
                    </button>
                    <button type="button" class="inline-flex items-center justify-center rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-500" @click="setPublished(true)">
                        Terbitkan artikel
                    </button>
                </div>
            </div>
        </div>

        <div class="mx-auto max-w-[1600px] px-4 py-8 sm:px-6 lg:px-8">
            <div class="grid gap-8 xl:grid-cols-[minmax(0,1fr)_minmax(300px,400px)] 2xl:grid-cols-[minmax(0,1fr)_440px]">
                <div class="min-w-0">
                    <form id="article-admin-form" action="{{ route('admin.articles.store') }}" method="post" class="space-y-6" novalidate>
                        @csrf
                        @include('admin.articles._form', ['article' => $article])
                    </form>
                </div>
                @include('admin.articles._preview')
            </div>
        </div>
    </div>
    @include('admin.articles._editorjs')
</x-app-layout>
