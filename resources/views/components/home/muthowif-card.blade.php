@props([
    'card',
])

<article {{ $attributes->class(['w-[15rem] shrink-0 snap-start overflow-hidden rounded-2xl border border-slate-100 bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md sm:w-[16.5rem]']) }}>
    <a href="{{ $card['href'] }}" class="block h-full focus:outline-none focus-visible:ring-2 focus-visible:ring-baytgo focus-visible:ring-offset-2">
        <div class="relative aspect-[4/5] overflow-hidden bg-slate-100">
            <img src="{{ $card['photo'] }}" alt="" class="h-full w-full object-cover object-top" loading="lazy" decoding="async" />
            <span class="absolute left-2.5 top-2.5 inline-flex items-center gap-1 rounded-full bg-white/95 px-2 py-0.5 text-[10px] font-bold text-emerald-700 shadow-sm">
                <svg class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" /></svg>
                {{ __('dashboard.customer_verified_badge') }}
            </span>
        </div>
        <div class="p-4">
            <h3 class="line-clamp-1 font-bold text-slate-900">{{ $card['name'] }}</h3>
            <div class="mt-1.5 flex flex-wrap items-center gap-x-1.5 gap-y-0.5 text-xs text-slate-600">
                <span class="inline-flex items-center gap-0.5 font-semibold text-slate-800">
                    <svg class="h-3.5 w-3.5 text-amber-500" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                    {{ $card['rating'] }}
                </span>
                @if (! empty($card['location']))
                    <span class="text-slate-400">·</span>
                    <span class="line-clamp-1">{{ $card['location'] }}</span>
                @endif
            </div>
            @if (! empty($card['tags']))
                <div class="mt-2.5 flex flex-wrap gap-1.5">
                    @foreach ($card['tags'] as $tag)
                        <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-semibold text-emerald-800">{{ $tag }}</span>
                    @endforeach
                </div>
            @endif
            <p class="mt-3 border-t border-slate-100 pt-3 text-xs text-slate-500">
                {{ __('welcome.landing_from_label') }}
                <span class="font-bold text-baytgo">{{ $card['price'] }}</span>
                <span class="text-slate-400">/{{ __('welcome.landing_per_day') }}</span>
            </p>
        </div>
    </a>
</article>
