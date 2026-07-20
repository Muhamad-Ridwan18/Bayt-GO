@props(['page'])

<section id="faq" class="home-section-pad scroll-mt-24 border-t border-slate-100 py-10 sm:py-12" aria-labelledby="welcome-faq-heading">
    <h2 id="welcome-faq-heading" class="mb-6 text-center text-xl font-bold text-baytgo sm:text-2xl">{{ __('welcome.faq_title') }}</h2>
    <div class="mx-auto max-w-3xl space-y-3" x-data="homeFaq()">
        @foreach ($page->faqItems as $i => $item)
            <div class="overflow-hidden rounded-2xl border border-slate-100 bg-white shadow-sm">
                <button type="button" class="flex w-full items-center justify-between gap-3 px-5 py-4 text-left" @click="toggle({{ $i }})">
                    <span class="text-sm font-semibold text-slate-900">{{ $item['q'] ?? '' }}</span>
                    <svg class="h-4 w-4 shrink-0 text-slate-400 transition" :class="open === {{ $i }} && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                </button>
                <div x-show="open === {{ $i }}" x-cloak class="border-t border-slate-100 px-5 pb-4 pt-3 text-sm leading-relaxed text-slate-600">{{ $item['a'] ?? '' }}</div>
            </div>
        @endforeach
    </div>
</section>
