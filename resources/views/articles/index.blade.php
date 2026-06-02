<x-layouts.marketing-public
    :title="__('articles.index_title')"
    :meta-description="$metaDescription"
    active-nav="articles"
>
    <section class="relative overflow-hidden border-b border-slate-100 bg-gradient-to-br from-welcomeCanvas via-white to-brand-50/30">
        <div class="pointer-events-none absolute -right-24 top-0 h-72 w-72 rounded-full bg-gold/15 blur-3xl" aria-hidden="true"></div>
        <div class="pointer-events-none absolute -left-20 bottom-0 h-56 w-56 rounded-full bg-baytgo/5 blur-3xl" aria-hidden="true"></div>
        <x-page-container class="relative py-16 sm:py-20">
            <p class="text-sm font-semibold uppercase tracking-wider text-baytgo/90">{{ __('articles.index_kicker') }}</p>
            <h1 class="mt-3 max-w-3xl text-4xl font-bold tracking-tight text-slate-900 sm:text-5xl">{{ __('articles.index_headline') }}</h1>
            <p class="mt-5 max-w-2xl text-lg leading-relaxed text-slate-600">{{ __('articles.index_sub') }}</p>
        </x-page-container>
    </section>

    <x-page-container class="py-14">
        @if ($articles->isEmpty())
            <p class="rounded-2xl border border-slate-200 bg-slate-50 px-6 py-12 text-center text-slate-600">{{ __('articles.empty') }}</p>
        @else
            @php
                $featured = $articles->firstWhere('is_featured', true) ?? $articles->first();
                $rest = $articles->filter(fn ($a) => $a->isNot($featured));
            @endphp

            @if ($featured)
                <article class="mb-14 overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-xl shadow-slate-900/5 ring-1 ring-slate-100">
                    <div class="grid gap-0 lg:grid-cols-12">
                        <div class="relative flex min-h-[14rem] flex-col justify-end bg-gradient-to-br from-baytgo via-baytgo-800 to-baytgo-950 p-8 text-white lg:col-span-5 lg:min-h-[20rem]">
                            <div class="absolute inset-0 bg-[url('data:image/svg+xml,%3Csvg width=\'60\' height=\'60\' viewBox=\'0 0 60 60\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'none\' fill-rule=\'evenodd\'%3E%3Cg fill=\'%23ffffff\' fill-opacity=\'0.06\'%3E%3Cpath d=\'M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E')] opacity-90" aria-hidden="true"></div>
                            <div class="relative">
                                <span class="inline-flex rounded-full bg-white/15 px-3 py-1 text-xs font-bold uppercase tracking-wide text-gold-light ring-1 ring-white/20">{{ $featured->localized('category') }}</span>
                                <h2 class="mt-4 text-2xl font-bold leading-tight sm:text-3xl">{{ $featured->localized('title') }}</h2>
                                <p class="mt-3 text-sm text-white/85 line-clamp-3 sm:line-clamp-none">{{ $featured->localized('excerpt') }}</p>
                                <div class="mt-6 flex flex-wrap items-center gap-3 text-xs font-medium text-white/90">
                                    <span>{{ $featured->published_at?->translatedFormat('d M Y') }}</span>
                                    <span class="h-1 w-1 rounded-full bg-white/40" aria-hidden="true"></span>
                                    <span>{{ __('articles.reading_minutes', ['count' => $featured->readingMinutes()]) }}</span>
                                </x-page-container>
                            </div>
                        </div>
                        <div class="flex flex-col justify-center p-8 lg:col-span-7 lg:p-12">
                            <p class="text-slate-600 leading-relaxed line-clamp-6 lg:line-clamp-none">{{ $featured->localized('excerpt') }}</p>
                            <a href="{{ route('articles.show', ['slug' => $featured->slug]) }}" class="mt-8 inline-flex w-fit items-center gap-2 rounded-xl bg-gold px-5 py-3 text-sm font-bold text-baytgo-950 shadow-md transition hover:bg-gold-muted">
                                {{ __('articles.read_featured') }}
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                            </a>
                        </div>
                    </div>
                </article>
            @endif

            <div class="grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($rest as $article)
                    <article class="group flex flex-col rounded-2xl border border-slate-200/90 bg-white p-6 shadow-sm transition hover:border-baytgo/25 hover:shadow-lg hover:shadow-baytgo/5">
                        <span class="text-xs font-bold uppercase tracking-wide text-baytgo/80">{{ $article->localized('category') }}</span>
                        <h2 class="mt-3 text-xl font-bold text-slate-900 group-hover:text-baytgo transition-colors leading-snug">
                            <a href="{{ route('articles.show', ['slug' => $article->slug]) }}" class="focus:outline-none focus-visible:ring-2 focus-visible:ring-baytgo/30 rounded-md">{{ $article->localized('title') }}</a>
                        </h2>
                        <p class="mt-3 flex-1 text-sm leading-relaxed text-slate-600 line-clamp-3">{{ $article->localized('excerpt') }}</p>
                        <div class="mt-5 flex items-center justify-between border-t border-slate-100 pt-5 text-xs text-slate-500">
                            <time datetime="{{ $article->published_at?->toIso8601String() }}">{{ $article->published_at?->translatedFormat('d M Y') }}</time>
                            <span>{{ __('articles.reading_minutes', ['count' => $article->readingMinutes()]) }}</span>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </div>
</x-layouts.marketing-public>
