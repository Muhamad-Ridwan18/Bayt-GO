<x-app-layout>
    <div class="relative min-h-[calc(100vh-4rem)] overflow-hidden bg-gradient-to-b from-slate-100 via-slate-50 to-white py-8 sm:py-12">
        <div class="relative mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
            <p class="text-[11px] font-semibold uppercase tracking-wider text-brand-700">{{ __('admin.articles.badge') }}</p>
            <h1 class="mt-1 text-2xl font-bold text-slate-900">{{ __('admin.articles.create_title') }}</h1>
            <p class="mt-2 text-sm text-slate-600">{{ __('admin.articles.create_sub') }}</p>

            <form action="{{ route('admin.articles.store') }}" method="post" class="mt-10 space-y-8">
                @csrf
                @include('admin.articles._form', ['article' => $article])

                <div class="flex flex-wrap gap-3">
                    <x-primary-button>{{ __('admin.articles.save') }}</x-primary-button>
                    <a href="{{ route('admin.articles.index') }}" class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50">{{ __('admin.articles.cancel') }}</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
