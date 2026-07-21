@php
    $profile = $page->profile;
@endphp

<x-ui.card id="ulasan" pad="lg" class="block">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-start gap-3">
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-50 text-amber-700" aria-hidden="true">
                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10.868 2.884c-.321-.772-1.415-.772-1.736 0l-1.83 4.401-4.753.381c-.833.067-1.171 1.107-.536 1.651l3.62 3.102-1.106 4.637c-.194.813.691 1.456 1.405 1.02L10 15.591l4.069 2.485c.713.436 1.598-.207 1.404-1.02l-1.106-4.637 3.62-3.102c.635-.544.297-1.584-.536-1.65l-4.752-.382-1.831-4.401z" clip-rule="evenodd" /></svg>
            </span>
            <h2 class="text-xl font-bold text-slate-900">{{ __('marketplace.show.reviews_heading') }}</h2>
        </div>
        @if ($page->reviewsCount > 0)
            <a href="#ulasan" class="text-sm font-semibold text-brand-700 hover:text-brand-800">{{ __('marketplace.show.reviews_see_all') }}</a>
        @endif
    </div>

    @if ($profile->bookingReviews->isEmpty())
        <p class="ui-section-body text-sm text-slate-600">{{ __('marketplace.show.no_reviews') }}</p>
    @else
        <div class="ui-section-body flex flex-col gap-6 lg:flex-row lg:items-start">
            <div class="shrink-0 rounded-2xl bg-slate-50 px-8 py-6 text-center ring-1 ring-slate-100 lg:min-w-[140px]">
                <p class="text-4xl font-bold text-slate-900">{{ $page->avgRating }}</p>
                <div class="mt-2 flex justify-center gap-0.5" aria-hidden="true">
                    @for ($s = 1; $s <= 5; $s++)
                        <svg class="h-4 w-4 {{ $s <= (int) round((float) $page->avgRating) ? 'text-gold' : 'text-slate-200' }}" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" /></svg>
                    @endfor
                </div>
                <p class="mt-1 text-xs text-slate-500">({{ $page->reviewsCount }} review)</p>
            </div>

            <ul class="-mx-2 flex gap-4 overflow-x-auto px-2 pb-2 snap-x snap-mandatory lg:flex-1">
                @foreach ($profile->bookingReviews as $review)
                    <li class="w-[min(100%,280px)] shrink-0 snap-start rounded-xl border border-slate-100 bg-white p-4 shadow-sm ring-1 ring-slate-100/80">
                        <div class="flex items-center gap-3">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-brand-100 text-sm font-bold text-brand-800">
                                {{ mb_substr($review->customer?->name ?? '?', 0, 1) }}
                            </span>
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-slate-900">{{ $review->customer?->name ?? __('marketplace.show.review_anonymous') }}</p>
                                <p class="text-xs font-bold text-amber-800">{{ $review->rating }} ★</p>
                            </div>
                        </div>
                        @if (filled($review->review))
                            <p class="mt-3 text-sm leading-relaxed text-slate-600">{{ Str::limit($review->review, 160) }}</p>
                        @endif
                        <p class="mt-2 text-[11px] text-slate-400">{{ $review->created_at?->diffForHumans() }}</p>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
</x-ui.card>
