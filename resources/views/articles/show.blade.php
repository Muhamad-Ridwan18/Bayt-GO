@php
    $rtl = app()->getLocale() === 'ar';
    $title = $title ?? $article->localized('title');
    $author = $article->localized('author');
    $coverImage = $ogImage ?? $article->coverImageUrl();
    $category = $article->localized('category');
    $excerpt = $article->localized('excerpt');
@endphp

@if ($coverImage)
    @push('head')
        <link rel="preload" as="image" href="{{ $coverImage }}" fetchpriority="high">
    @endpush
@endif

<x-layouts.marketing-public
    :title="$title"
    :meta-description="$metaDescription"
    :schema="$schema"
    :image="$coverImage"
    :canonical="$canonical"
    :alternates="$alternates"
    type="article"
    active-nav="articles"
>
    <article>
        <header class="relative overflow-hidden border-b border-slate-100 bg-gradient-to-b from-welcomeCanvas via-welcomeCanvas/80 to-white">
            <div class="pointer-events-none absolute -right-16 top-0 h-64 w-64 rounded-full bg-gold/10 blur-3xl" aria-hidden="true"></div>
            <div class="pointer-events-none absolute -left-20 bottom-0 h-48 w-48 rounded-full bg-baytgo/[0.06] blur-3xl" aria-hidden="true"></div>

            <x-page-container class="relative py-8 sm:py-12">
                <nav class="text-sm text-slate-500" aria-label="{{ __('articles.breadcrumb_aria') }}">
                    <ol class="flex flex-wrap items-center gap-2">
                        <li><a href="{{ route('welcome') }}" class="font-medium transition hover:text-baytgo">{{ __('nav.home') }}</a></li>
                        <li aria-hidden="true" class="text-slate-300">/</li>
                        <li><a href="{{ \App\Support\ArticleUrl::index() }}" class="font-medium transition hover:text-baytgo">{{ __('articles.index_title') }}</a></li>
                        <li aria-hidden="true" class="text-slate-300">/</li>
                        <li class="max-w-[14rem] truncate font-semibold text-slate-700 sm:max-w-md" title="{{ $title }}">{{ $title }}</li>
                    </ol>
                </nav>

                <div class="mx-auto mt-8 max-w-3xl {{ $rtl ? 'text-right' : '' }}">
                    @if ($category !== '')
                        <span class="inline-flex rounded-full bg-baytgo/10 px-3 py-1 text-xs font-bold uppercase tracking-wide text-baytgo ring-1 ring-baytgo/10">{{ $category }}</span>
                    @endif

                    <h1 class="mt-4 text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl sm:leading-[1.15] lg:text-[2.75rem]">{{ $title }}</h1>

                    @if ($excerpt !== '')
                        <p class="mt-5 text-lg leading-relaxed text-slate-600 sm:text-xl sm:leading-relaxed">{{ $excerpt }}</p>
                    @endif

                    <div class="mt-8 flex flex-wrap items-center gap-x-3 gap-y-2 border-t border-slate-200/80 pt-5 text-sm text-slate-600">
                        @if ($author !== '')
                            <span class="font-semibold text-slate-800">{{ __('articles.by_author', ['name' => $author]) }}</span>
                            <span class="hidden text-slate-300 sm:inline" aria-hidden="true">·</span>
                        @endif
                        <time datetime="{{ $article->published_at?->toIso8601String() }}">{{ $article->published_at?->translatedFormat('d M Y') }}</time>
                        <span class="text-slate-300" aria-hidden="true">·</span>
                        <span>{{ __('articles.reading_minutes', ['count' => $article->readingMinutes()]) }}</span>
                    </div>
                </div>
            </x-page-container>

            @if ($coverImage)
                <div class="relative mx-auto w-full max-w-5xl px-4 pb-10 sm:px-6 lg:px-8 xl:px-10">
                    <figure class="overflow-hidden rounded-2xl border border-slate-200/70 bg-slate-100 shadow-lg shadow-slate-900/5 sm:rounded-3xl">
                        <img
                            src="{{ $coverImage }}"
                            alt="{{ $title }}"
                            class="aspect-[16/9] w-full object-cover"
                            width="1200"
                            height="675"
                            loading="eager"
                            decoding="async"
                            fetchpriority="high"
                        >
                    </figure>
                </div>
            @endif
        </header>

        <x-page-container class="py-10 sm:py-14">
            <div class="grid gap-10 lg:grid-cols-[minmax(0,1fr)_16rem] lg:items-start xl:grid-cols-[minmax(0,1fr)_18rem] xl:gap-14">
                <div class="min-w-0">
                    <div class="article-prose mx-auto max-w-3xl {{ $rtl ? 'text-right' : '' }}" dir="{{ $rtl ? 'rtl' : 'ltr' }}">
                        {!! $article->localized('body') !!}
                    </div>
                </div>

                <aside class="hidden lg:block" aria-label="{{ __('articles.aside_kicker') }}">
                    <div class="sticky top-28 space-y-4">
                        <div class="overflow-hidden rounded-2xl border border-baytgo/15 bg-gradient-to-br from-welcomeCanvas via-white to-brand-50/50 p-5 shadow-sm shadow-slate-900/5">
                            <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-baytgo">{{ __('articles.aside_kicker') }}</p>
                            <p class="mt-3 text-base font-bold leading-snug text-slate-900">{{ __('articles.aside_title') }}</p>
                            <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ __('articles.aside_sub') }}</p>
                            <a
                                href="{{ route('layanan.index') }}"
                                class="mt-5 inline-flex w-full items-center justify-center gap-2 rounded-xl bg-baytgo px-4 py-2.5 text-sm font-bold text-white shadow-md shadow-baytgo/20 transition hover:bg-baytgo-800"
                            >
                                {{ __('articles.aside_cta') }}
                                <span aria-hidden="true">→</span>
                            </a>
                        </div>
                    </div>
                </aside>
            </div>

            @if (isset($relatedArticles) && $relatedArticles->isNotEmpty())
                <section class="mt-16 border-t border-slate-200 pt-12" aria-labelledby="related-articles-heading">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <p class="text-sm font-semibold uppercase tracking-[0.24em] text-baytgo">{{ __('articles.related_articles_kicker') }}</p>
                            <h2 id="related-articles-heading" class="mt-2 text-2xl font-bold text-slate-900">{{ __('articles.related_articles_title') }}</h2>
                        </div>
                        <a href="{{ \App\Support\ArticleUrl::index() }}" class="text-sm font-semibold text-baytgo transition hover:text-baytgo-700">{{ __('articles.back_to_list') }}</a>
                    </div>
                    <div class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                        @foreach ($relatedArticles as $related)
                            @php $relatedCover = $related->coverImageUrl(); @endphp
                            <article class="group flex flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:border-baytgo/30 hover:shadow-md">
                                @if ($relatedCover)
                                    <a href="{{ \App\Support\ArticleUrl::show($related) }}" class="block aspect-[16/10] overflow-hidden bg-slate-100" tabindex="-1" aria-hidden="true">
                                        <img
                                            src="{{ $relatedCover }}"
                                            alt=""
                                            class="h-full w-full object-cover transition duration-300 group-hover:scale-[1.03]"
                                            width="320"
                                            height="200"
                                            loading="lazy"
                                            decoding="async"
                                        >
                                    </a>
                                @endif
                                <div class="flex flex-1 flex-col p-5">
                                    <span class="text-xs font-bold uppercase tracking-wide text-baytgo/80">{{ $related->localized('category') }}</span>
                                    <h3 class="mt-2 text-base font-bold leading-snug text-slate-900 transition group-hover:text-baytgo">
                                        <a href="{{ \App\Support\ArticleUrl::show($related) }}" class="rounded-md focus:outline-none focus-visible:ring-2 focus-visible:ring-baytgo/30">
                                            {{ $related->localized('title') }}
                                        </a>
                                    </h3>
                                    <p class="mt-2 flex-1 text-sm leading-relaxed text-slate-600 line-clamp-3">{{ $related->localized('excerpt') }}</p>
                                    <time class="mt-4 text-xs text-slate-500" datetime="{{ $related->published_at?->toIso8601String() }}">
                                        {{ $related->published_at?->translatedFormat('d M Y') }}
                                    </time>
                                </div>
                            </article>
                        @endforeach
                    </div>
                </section>
            @endif

            @if (isset($relatedServices) && $relatedServices->isNotEmpty())
                <section class="mt-16 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <p class="text-sm font-semibold uppercase tracking-[0.24em] text-baytgo">{{ __('articles.related_services_title') }}</p>
                            <h2 class="mt-3 text-2xl font-bold text-slate-900">{{ __('articles.related_services_subtitle') }}</h2>
                        </div>
                        <a href="{{ route('layanan.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-baytgo transition hover:text-baytgo-700">
                            {{ __('articles.view_all_services') }}
                            <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M10.293 15.707a1 1 0 010-1.414L13.586 11H4a1 1 0 110-2h9.586l-3.293-3.293a1 1 0 111.414-1.414l5 5a1 1 0 010 1.414l-5 5a1 1 0 01-1.414 0z" clip-rule="evenodd" /></svg>
                        </a>
                    </div>
                    <div class="mt-8 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                        @foreach ($relatedServices as $service)
                            <x-marketplace.profile-card :profile="$service" />
                        @endforeach
                    </div>
                </section>
            @endif

            <div class="mt-16 overflow-hidden rounded-3xl border border-baytgo/15 bg-gradient-to-br from-baytgo via-baytgo-800 to-baytgo-950 p-8 text-center text-white shadow-lg shadow-baytgo/20 sm:p-10">
                <p class="text-xl font-bold tracking-tight sm:text-2xl">{{ __('articles.cta_title') }}</p>
                <p class="mx-auto mt-3 max-w-xl text-sm leading-relaxed text-white/80 sm:text-base">{{ __('articles.cta_sub') }}</p>
                <div class="mt-8 flex flex-wrap justify-center gap-3">
                    <a href="{{ route('layanan.index') }}" class="inline-flex items-center justify-center rounded-xl bg-gold px-5 py-3 text-sm font-bold text-baytgo-950 shadow-md transition hover:bg-gold-muted">{{ __('articles.cta_browse') }}</a>
                    <a href="{{ \App\Support\ArticleUrl::index() }}" class="inline-flex items-center justify-center rounded-xl border border-white/25 bg-white/10 px-5 py-3 text-sm font-bold text-white backdrop-blur transition hover:bg-white/20">{{ __('articles.back_to_list') }}</a>
                </div>
            </div>
        </x-page-container>
    </article>
</x-layouts.marketing-public>
