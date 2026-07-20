@props(['page'])

<section id="customer-articles" class="home-section-pad scroll-mt-24 border-t border-slate-100 py-10 sm:py-12" aria-labelledby="customer-articles-heading">
    <x-home.section-heading
        :title="__('dashboard.customer_articles_title')"
        title-id="customer-articles-heading"
        :href="$page->articlesIndexUrl"
        :link-label="__('dashboard.customer_articles_see_all')"
    />

    @if ($page->hasArticles())
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($page->articleCards as $article)
                <article class="overflow-hidden rounded-2xl border border-slate-100 bg-white shadow-sm transition hover:shadow-md">
                    <a href="{{ $article['href'] }}" class="block">
                        <div class="aspect-[16/10] overflow-hidden bg-slate-100">
                            @if ($article['thumbnail'])
                                <img src="{{ $article['thumbnail'] }}" alt="" class="h-full w-full object-cover" loading="lazy" />
                            @endif
                        </div>
                        <div class="p-4">
                            <p class="line-clamp-2 text-sm font-bold text-slate-900">{{ $article['title'] }}</p>
                            <p class="mt-2 line-clamp-2 text-xs text-slate-600">{{ $article['excerpt'] }}</p>
                        </div>
                    </a>
                </article>
            @endforeach
        </div>
    @elseif ($page->hasGuideCards())
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            @foreach ($page->guideCards as $card)
                <a href="{{ route('welcome') }}#{{ $card['fragment'] ?? '' }}" class="group overflow-hidden rounded-2xl border border-slate-100 bg-white shadow-sm transition hover:shadow-md">
                    <div class="p-5">
                        <span class="inline-flex rounded-lg bg-gold-light/35 px-2 py-1 text-[10px] font-bold uppercase tracking-wider text-baytgo ring-1 ring-gold/25">{{ $card['read'] ?? '' }}</span>
                        <p class="mt-3 font-bold leading-snug text-baytgo group-hover:text-baytgo-800">{{ $card['title'] ?? '' }}</p>
                        <p class="mt-2 text-xs leading-relaxed text-slate-600">{{ $card['desc'] ?? '' }}</p>
                    </div>
                </a>
            @endforeach
        </div>
    @else
        <p class="rounded-2xl border border-dashed border-slate-200 bg-white py-10 text-center text-sm text-slate-600">{{ __('welcome.popular_empty') }}</p>
    @endif
</section>
