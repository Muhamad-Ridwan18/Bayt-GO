<x-layouts.marketing-public :title="__('common.not_found_title')" :meta-description="__('common.not_found_body')">
    <x-page-container class="py-20 sm:py-28">
        <div class="mx-auto max-w-2xl text-center">
            <p class="text-sm font-bold uppercase tracking-widest text-gold">404</p>
            <h1 class="mt-4 text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">{{ __('common.not_found_headline') }}</h1>
            <p class="mt-5 text-lg leading-relaxed text-slate-600">{{ __('common.not_found_body') }}</p>

            <div class="mt-10 flex flex-wrap items-center justify-center gap-3">
                <a href="{{ route('welcome') }}" class="inline-flex items-center rounded-xl bg-baytgo px-5 py-3 text-sm font-bold text-white shadow-md transition hover:bg-baytgo-800">
                    {{ __('common.not_found_cta_home') }}
                </a>
                <a href="{{ route('layanan.index') }}" class="inline-flex items-center rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:border-baytgo/40 hover:text-baytgo">
                    {{ __('common.not_found_cta_muthowif') }}
                </a>
                <a href="{{ \App\Support\ArticleUrl::index() }}" class="inline-flex items-center rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:border-baytgo/40 hover:text-baytgo">
                    {{ __('common.not_found_cta_articles') }}
                </a>
            </div>
        </div>
    </x-page-container>
</x-layouts.marketing-public>
