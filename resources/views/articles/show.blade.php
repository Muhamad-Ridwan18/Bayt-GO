@php
    $rtl = app()->getLocale() === 'ar';
    $title = $article->localized('title');
    $author = $article->localized('author');

    $articleSchema = [
        '@context' => 'https://schema.org',
        '@type' => 'BlogPosting',
        'headline' => $title,
        'description' => $metaDescription,
        'datePublished' => $article->published_at?->toIso8601String(),
        'author' => [
            '@type' => 'Person',
            'name' => $author ?: 'Tim Bayt-GO',
        ],
        'publisher' => [
            '@type' => 'Organization',
            'name' => config('app.name', 'Bayt-GO'),
            'logo' => [
                '@type' => 'ImageObject',
                'url' => asset('images/logo.png'),
            ]
        ]
    ];
@endphp
<x-layouts.marketing-public
    :title="$title"
    :meta-description="$metaDescription"
    :schema="$articleSchema"
    active-nav="articles"
>
    <article class="border-b border-slate-100 bg-gradient-to-b from-welcomeCanvas to-white">
        <x-page-container class="py-10 sm:py-14">
            <nav class="text-sm text-slate-500" aria-label="{{ __('articles.breadcrumb_aria') }}">
                <ol class="flex flex-wrap items-center gap-2">
                    <li><a href="{{ route('welcome') }}" class="font-medium hover:text-baytgo">{{ __('nav.home') }}</a></li>
                    <li aria-hidden="true" class="text-slate-300">/</li>
                    <li><a href="{{ route('articles.index') }}" class="font-medium hover:text-baytgo">{{ __('articles.index_title') }}</a></li>
                    <li aria-hidden="true" class="text-slate-300">/</li>
                    <li class="font-semibold text-slate-700 max-w-[12rem] truncate sm:max-w-none" title="{{ $title }}">{{ $title }}</li>
                </ol>
            </nav>

            <header class="mt-8">
                <span class="inline-flex rounded-full bg-baytgo/10 px-3 py-1 text-xs font-bold uppercase tracking-wide text-baytgo">{{ $article->localized('category') }}</span>
                <h1 class="mt-4 text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl sm:leading-tight">{{ $title }}</h1>
                <p class="mt-4 text-lg leading-relaxed text-slate-600">{{ $article->localized('excerpt') }}</p>
                <div class="mt-8 flex flex-wrap items-center gap-4 border-y border-slate-200/80 py-5 text-sm text-slate-600">
                    @if ($author !== '')
                        <span class="font-medium text-slate-800">{{ __('articles.by_author', ['name' => $author]) }}</span>
                        <span class="hidden h-4 w-px bg-slate-200 sm:block" aria-hidden="true"></span>
                    @endif
                    <time datetime="{{ $article->published_at?->toIso8601String() }}">{{ $article->published_at?->translatedFormat('d M Y') }}</time>
                    <span class="hidden h-4 w-px bg-slate-200 sm:block" aria-hidden="true"></span>
                    <span>{{ __('articles.reading_minutes', ['count' => $article->readingMinutes()]) }}</span>
                </div>
            </header>
        </div>
    </article>

    <x-page-container class="py-12">
        <div class="article-prose {{ $rtl ? 'text-right' : '' }}" dir="{{ $rtl ? 'rtl' : 'ltr' }}">
            {!! $article->localized('body') !!}
        </div>

        @if(isset($relatedServices) && $relatedServices->isNotEmpty())
            <section class="mt-16 rounded-3xl border border-slate-200 bg-white p-8 shadow-sm">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-[0.24em] text-baytgo">{{ __('articles.related_services_title') }}</p>
                        <h2 class="mt-3 text-2xl font-bold text-slate-900">{{ __('articles.related_services_subtitle') }}</h2>
                    </div>
                    <a href="{{ route('layanan.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-baytgo hover:text-baytgo-700">
                        {{ __('articles.view_all_services') }}
                        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M10.293 15.707a1 1 0 010-1.414L13.586 11H4a1 1 0 110-2h9.586l-3.293-3.293a1 1 0 111.414-1.414l5 5a1 1 0 010 1.414l-5 5a1 1 0 01-1.414 0z" clip-rule="evenodd" /></svg>
                    </a>
                </div>
                <div class="mt-8 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    @foreach($relatedServices as $service)
                        <x-marketplace.profile-card :profile="$service" />
                    @endforeach
                </x-page-container>
            </section>
        @endif

        <div class="mt-16 rounded-2xl border border-slate-200 bg-welcomeCanvas p-8 text-center">
            <p class="text-lg font-semibold text-slate-900">{{ __('articles.cta_title') }}</p>
            <p class="mt-2 text-sm text-slate-600">{{ __('articles.cta_sub') }}</p>
            <div class="mt-6 flex flex-wrap justify-center gap-3">
                <a href="{{ route('layanan.index') }}" class="inline-flex items-center justify-center rounded-xl bg-baytgo px-5 py-3 text-sm font-bold text-white shadow-md shadow-baytgo/25 transition hover:bg-baytgo-800">{{ __('articles.cta_browse') }}</a>
                <a href="{{ route('articles.index') }}" class="inline-flex items-center justify-center rounded-xl border-2 border-slate-200 bg-white px-5 py-3 text-sm font-bold text-slate-800 transition hover:border-baytgo/30">{{ __('articles.back_to_list') }}</a>
            </x-page-container>
        </div>
    </div>
</x-layouts.marketing-public>
