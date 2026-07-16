@php
    $welcomeHeroBg = null;
    foreach (['webp', 'png', 'jpg', 'jpeg'] as $ext) {
        if (file_exists(public_path('images/bg-welcome.'.$ext))) {
            $welcomeHeroBg = asset('images/bg-welcome.'.$ext);
            break;
        }
    }
    if ($welcomeHeroBg === null) {
        $welcomeHeroBg = file_exists(public_path('images/bg-01.jpeg'))
            ? asset('images/bg-01.jpeg')
            : 'https://images.unsplash.com/photo-1519817914152-22d216bb9170?q=85&w=2160&auto=format&fit=crop';
    }

    $stats = $catalogStats ?? ['packages' => 0, 'muthowifs' => 0, 'avg_rating' => 0];
    $allQuery = array_filter(['q' => ($searchQuery ?? '') !== '' ? $searchQuery : null]);
@endphp

<x-marketplace-layout :title="__('layanan_pendukung.page_title')" :meta-description="__('layanan_pendukung.hero_lead')" :full-bleed="true">
    <div class="relative min-w-0 overflow-x-hidden">
        <section class="relative left-1/2 mb-0 w-screen max-w-[100vw] -translate-x-1/2 overflow-hidden bg-welcomeCanvas pb-8 sm:pb-10">
            <div class="pointer-events-none absolute inset-0 z-0" aria-hidden="true">
                <img src="{{ $welcomeHeroBg }}" alt="" class="h-full w-full min-h-[18rem] object-cover object-[74%_30%] sm:min-h-[20rem]" loading="eager" decoding="async" />
            </div>
            <div class="pointer-events-none absolute inset-0 z-[1] bg-gradient-to-b from-welcomeCanvas via-welcomeCanvas/95 to-welcomeCanvas/60 sm:hidden" aria-hidden="true"></div>
            <div class="pointer-events-none absolute inset-0 z-[1] hidden bg-gradient-to-r from-welcomeCanvas from-[30%] via-welcomeCanvas/96 via-[58%] to-welcomeCanvas/15 sm:block lg:from-[34%] lg:via-[60%] lg:to-transparent" aria-hidden="true"></div>

            <div class="relative z-10 mx-auto max-w-6xl px-4 pt-8 sm:px-6 sm:pt-10 lg:pt-12">
                <div class="flex flex-col gap-8 lg:flex-row lg:items-end lg:justify-between">
                    <div class="max-w-2xl">
                        <p class="text-xs font-bold uppercase tracking-wider text-baytgo">{{ __('layanan_pendukung.hero_kicker') }}</p>
                        <h1 class="mt-2 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl lg:text-4xl">
                            {{ __('layanan_pendukung.hero_title_before') }}
                            <span class="text-baytgo">{{ __('layanan_pendukung.hero_title_highlight') }}</span>
                            {{ __('layanan_pendukung.hero_title_after') }}
                        </h1>
                        <p class="mt-3 max-w-xl text-sm leading-relaxed text-slate-700 sm:text-base">{{ __('layanan_pendukung.hero_lead') }}</p>
                    </div>

                    <dl class="grid grid-cols-3 gap-3 sm:gap-4 lg:min-w-[18rem]">
                        <div class="rounded-2xl border border-white/80 bg-white/90 px-3 py-3 text-center shadow-sm ring-1 ring-slate-100/80 backdrop-blur-sm sm:px-4">
                            <dt class="text-[10px] font-semibold uppercase tracking-wide text-slate-500 sm:text-[11px]">{{ __('layanan_pendukung.stat_packages') }}</dt>
                            <dd class="mt-1 text-lg font-bold text-slate-900 sm:text-xl">{{ number_format((int) $stats['packages']) }}+</dd>
                        </div>
                        <div class="rounded-2xl border border-white/80 bg-white/90 px-3 py-3 text-center shadow-sm ring-1 ring-slate-100/80 backdrop-blur-sm sm:px-4">
                            <dt class="text-[10px] font-semibold uppercase tracking-wide text-slate-500 sm:text-[11px]">{{ __('layanan_pendukung.stat_muthowifs') }}</dt>
                            <dd class="mt-1 text-lg font-bold text-slate-900 sm:text-xl">{{ number_format((int) $stats['muthowifs']) }}+</dd>
                        </div>
                        <div class="rounded-2xl border border-white/80 bg-white/90 px-3 py-3 text-center shadow-sm ring-1 ring-slate-100/80 backdrop-blur-sm sm:px-4">
                            <dt class="text-[10px] font-semibold uppercase tracking-wide text-slate-500 sm:text-[11px]">{{ __('layanan_pendukung.stat_rating') }}</dt>
                            <dd class="mt-1 inline-flex items-center justify-center gap-1 text-lg font-bold text-slate-900 sm:text-xl">
                                @if ((float) $stats['avg_rating'] > 0)
                                    <svg class="h-4 w-4 text-amber-500" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                    {{ number_format((float) $stats['avg_rating'], 1) }}
                                @else
                                    —
                                @endif
                            </dd>
                        </div>
                    </dl>
                </div>

                <form method="GET" action="{{ route('layanan-pendukung.index') }}" class="mt-8">
                    @if ($activeCategory)
                        <input type="hidden" name="category" value="{{ $activeCategory->value }}">
                    @endif
                    <div class="flex overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-[0_16px_40px_-12px_rgba(15,42,37,0.18)] ring-1 ring-slate-100/90">
                        <div class="relative min-w-0 flex-1">
                            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400" aria-hidden="true">
                                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" /></svg>
                            </span>
                            <input
                                type="search"
                                name="q"
                                value="{{ $searchQuery }}"
                                placeholder="{{ __('layanan_pendukung.search_placeholder') }}"
                                class="w-full border-0 bg-transparent py-3.5 pl-12 pr-3 text-sm text-slate-900 placeholder:text-slate-400 focus:outline-none focus:ring-0 sm:py-4 sm:text-base"
                            >
                        </div>
                        <x-submit-button class="shrink-0 rounded-none rounded-r-2xl bg-baytgo px-5 py-3.5 text-sm font-semibold text-white shadow-none hover:bg-baytgo-800 sm:px-8 sm:py-4">
                            {{ __('layanan_pendukung.search_submit') }}
                        </x-submit-button>
                    </div>
                </form>

                <div class="mt-6 grid grid-cols-2 gap-2 sm:grid-cols-3 sm:gap-3 lg:grid-cols-5">
                    @foreach ($categories as $cat)
                        @php
                            $tileQuery = array_filter([
                                'q' => ($searchQuery ?? '') !== '' ? $searchQuery : null,
                                'category' => $cat->value,
                            ]);
                            $tileActive = ($activeCategory ?? null) === $cat;
                        @endphp
                        <a href="{{ route('layanan-pendukung.index', $tileQuery) }}"
                           @class([
                               'group flex flex-col items-center gap-2 rounded-2xl border px-3 py-3.5 text-center transition sm:py-4',
                               $tileActive
                                   ? 'border-baytgo bg-baytgo text-white shadow-md shadow-baytgo/20'
                                   : 'border-white/80 bg-white/90 text-slate-700 shadow-sm ring-1 ring-slate-100/80 hover:border-baytgo/30 hover:bg-white',
                           ])>
                            <span @class([
                                'flex h-10 w-10 items-center justify-center rounded-xl transition',
                                $tileActive ? 'bg-white/15 text-white' : 'bg-emerald-50 text-baytgo group-hover:bg-emerald-100',
                            ]) aria-hidden="true">
                                @include('layanan-pendukung.partials.category-icon', ['category' => $cat])
                            </span>
                            <span class="text-[11px] font-bold leading-tight sm:text-xs">{{ __('layanan_pendukung.quick.'.$cat->value) }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        </section>

        <div class="relative mx-auto max-w-6xl space-y-6 px-4 py-8 sm:px-6 sm:py-10">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('layanan_pendukung.category_filter') }}</p>
                <div class="mt-2.5 flex flex-wrap gap-2">
                    <a href="{{ route('layanan-pendukung.index', $allQuery) }}"
                       @class([
                           'inline-flex rounded-full px-3.5 py-1.5 text-xs font-semibold ring-1 transition',
                           ($activeCategory ?? null) === null
                               ? 'bg-baytgo text-white ring-baytgo'
                               : 'bg-white text-slate-700 ring-slate-200 hover:bg-slate-50',
                       ])>
                        {{ __('layanan_pendukung.category_all') }}
                    </a>
                    @foreach ($categories as $cat)
                        @php
                            $catQuery = array_filter([
                                'q' => ($searchQuery ?? '') !== '' ? $searchQuery : null,
                                'category' => $cat->value,
                            ]);
                        @endphp
                        <a href="{{ route('layanan-pendukung.index', $catQuery) }}"
                           @class([
                               'inline-flex rounded-full px-3.5 py-1.5 text-xs font-semibold ring-1 transition',
                               ($activeCategory ?? null) === $cat
                                   ? 'bg-baytgo text-white ring-baytgo'
                                   : 'bg-white text-slate-700 ring-slate-200 hover:bg-slate-50',
                           ])>
                            {{ $cat->label() }}
                        </a>
                    @endforeach
                </div>
            </div>

            @if ($packages->isEmpty())
                <div class="rounded-3xl border border-dashed border-slate-300 bg-white p-12 text-center shadow-sm">
                    <p class="text-lg font-bold text-slate-900">{{ __('layanan_pendukung.no_results_title') }}</p>
                    <p class="mx-auto mt-2 max-w-md text-sm text-slate-600">{{ __('layanan_pendukung.no_results_sub') }}</p>
                </div>
            @else
                <ul class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($packages as $package)
                        @include('layanan-pendukung.partials.package-card', ['package' => $package])
                    @endforeach
                </ul>
                <div class="flex justify-center pt-2">{{ $packages->links() }}</div>
            @endif

            <div class="flex items-center justify-center gap-2 rounded-2xl border border-emerald-100 bg-emerald-50/70 px-4 py-3.5 text-center text-xs font-medium text-emerald-900 sm:text-sm">
                <svg class="h-4 w-4 shrink-0 text-emerald-700" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M10 1a4.5 4.5 0 00-4.5 4.5V9H5a2 2 0 00-2 2v6a2 2 0 002 2h10a2 2 0 002-2v-6a2 2 0 00-2-2h-.5V5.5A4.5 4.5 0 0010 1zm3 8V5.5a3 3 0 10-6 0V9h6z" clip-rule="evenodd" /></svg>
                <span>{{ __('layanan_pendukung.trust_strip') }}</span>
            </div>
        </div>
    </div>
</x-marketplace-layout>
