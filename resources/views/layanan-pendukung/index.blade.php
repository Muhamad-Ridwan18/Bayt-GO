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
    $hasSearch = (bool) ($hasSearch ?? false);
    $startsAtInput = $startsAtInput ?? '';
    $defaultStartsAt = $startsAtInput !== ''
        ? $startsAtInput
        : now()->addDay()->setTime(9, 0)->format('Y-m-d\TH:i');
@endphp

<x-marketplace-layout :title="__('layanan_pendukung.page_title')" :meta-description="__('layanan_pendukung.hero_lead')" :full-bleed="true">
    <div class="relative min-w-0 overflow-x-hidden">
        <section class="relative left-1/2 mb-0 w-screen max-w-[100vw] -translate-x-1/2 overflow-hidden bg-welcomeCanvas pb-8 sm:pb-10">
            <div class="pointer-events-none absolute inset-0 z-0" aria-hidden="true">
                <img src="{{ $welcomeHeroBg }}" alt="" class="h-full w-full min-h-[18rem] object-cover object-[74%_30%] sm:min-h-[20rem]" loading="eager" decoding="async" />
            </div>
            <div class="pointer-events-none absolute inset-0 z-[1] bg-gradient-to-b from-welcomeCanvas via-welcomeCanvas/95 to-welcomeCanvas/60 sm:hidden" aria-hidden="true"></div>
            <div class="pointer-events-none absolute inset-0 z-[1] hidden bg-gradient-to-r from-welcomeCanvas from-[30%] via-welcomeCanvas/96 via-[58%] to-welcomeCanvas/15 sm:block lg:from-[34%] lg:via-[60%] lg:to-transparent" aria-hidden="true"></div>

            <div class="relative z-10 mx-auto w-full px-4 pt-8 sm:px-6 sm:pt-10 lg:px-8 lg:pt-12 xl:px-10">
                <div class="flex flex-col gap-8 lg:flex-row lg:items-end lg:justify-between">
                    <div class="max-w-2xl">
                        <p class="text-xs font-bold uppercase tracking-wider text-baytgo">{{ __('layanan_pendukung.hero_kicker') }}</p>
                        <h1 class="mt-2 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl lg:text-4xl">
                            {{ __('layanan_pendukung.hero_title_before') }}
                            <span class="text-baytgo">{{ __('layanan_pendukung.hero_title_highlight') }}</span>
                            {{ __('layanan_pendukung.hero_title_after') }}
                        </h1>
                        <p class="mt-3 max-w-xl text-sm leading-relaxed text-slate-700 sm:text-base">{{ __('layanan_pendukung.hero_lead') }}</p>
                        <p class="mt-2 max-w-xl text-sm font-medium text-baytgo">{{ __('layanan_pendukung.schedule_first_hint') }}</p>
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

                <form method="GET" action="{{ route('layanan-pendukung.index') }}" class="mt-8 space-y-3">
                    @if ($activeCategory)
                        <input type="hidden" name="category" value="{{ $activeCategory->value }}">
                    @endif
                    <div class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-[0_16px_40px_-12px_rgba(15,42,37,0.18)] ring-1 ring-slate-100/90">
                        <div class="flex flex-col gap-0 sm:flex-row sm:items-stretch">
                            <div class="min-w-0 flex-1 border-b border-slate-100 px-4 py-3 sm:border-b-0 sm:border-r sm:px-5 sm:py-3.5">
                                <label for="starts_at" class="block text-[11px] font-bold uppercase tracking-wide text-slate-500">{{ __('layanan_pendukung.starts_at') }}</label>
                                <input
                                    id="starts_at"
                                    type="datetime-local"
                                    name="starts_at"
                                    required
                                    value="{{ $defaultStartsAt }}"
                                    min="{{ now()->format('Y-m-d\TH:i') }}"
                                    class="mt-1 w-full border-0 bg-transparent p-0 text-sm font-semibold text-slate-900 focus:outline-none focus:ring-0 sm:text-base"
                                >
                            </div>
                            <div class="relative min-w-0 flex-[1.2] px-4 py-3 sm:px-5 sm:py-3.5">
                                <label for="q" class="block text-[11px] font-bold uppercase tracking-wide text-slate-500">{{ __('layanan_pendukung.search_label') }}</label>
                                <input
                                    id="q"
                                    type="search"
                                    name="q"
                                    value="{{ $searchQuery }}"
                                    placeholder="{{ __('layanan_pendukung.search_placeholder') }}"
                                    class="mt-1 w-full border-0 bg-transparent p-0 text-sm text-slate-900 placeholder:text-slate-400 focus:outline-none focus:ring-0 sm:text-base"
                                >
                            </div>
                            <x-submit-button class="shrink-0 rounded-none rounded-b-2xl bg-baytgo px-5 py-3.5 text-sm font-semibold text-white shadow-none hover:bg-baytgo-800 sm:rounded-b-none sm:rounded-r-2xl sm:px-8 sm:py-4">
                                {{ __('layanan_pendukung.search_submit') }}
                            </x-submit-button>
                        </div>
                    </div>
                    <p class="text-xs text-slate-600">{{ __('layanan_pendukung.starts_at_catalog_hint') }}</p>
                </form>
            </div>
        </section>

        <div class="relative mx-auto w-full space-y-6 px-4 py-8 sm:px-6 sm:py-10 lg:px-8 xl:px-10">
            @if (session('error'))
                <div class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900" role="alert">
                    {{ session('error') }}
                </div>
            @endif

            @if (! $hasSearch)
                <div class="rounded-3xl border border-dashed border-slate-300 bg-white px-6 py-14 text-center shadow-sm">
                    <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-emerald-50 text-baytgo ring-1 ring-emerald-200/80" aria-hidden="true">
                        <svg class="h-7 w-7" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </span>
                    <p class="mt-4 text-lg font-bold text-slate-900">{{ __('layanan_pendukung.pick_schedule_title') }}</p>
                    <p class="mx-auto mt-2 max-w-md text-sm text-slate-600">{{ __('layanan_pendukung.pick_schedule_sub') }}</p>
                </div>
            @else
                @if ($startsAt)
                    <div class="flex flex-wrap items-center gap-2 rounded-2xl border border-emerald-100 bg-emerald-50/80 px-4 py-3 text-sm text-emerald-950">
                        <svg class="h-4 w-4 shrink-0 text-emerald-700" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm.75-13a.75.75 0 00-1.5 0v5c0 .414.336.75.75.75h4a.75.75 0 000-1.5h-3.25V5z" clip-rule="evenodd" /></svg>
                        <span class="font-semibold">{{ __('layanan_pendukung.slot_label', ['datetime' => $startsAt->timezone(config('app.timezone'))->translatedFormat('d M Y, H:i')]) }}</span>
                        <span class="text-emerald-800/80">· {{ __('layanan_pendukung.slot_availability_note') }}</span>
                        @if ($activeCategory)
                            <span class="text-emerald-800/80">· {{ $activeCategory->label() }}</span>
                        @endif
                    </div>
                @endif

                @if ($packages->isEmpty())
                    <div class="rounded-3xl border border-dashed border-slate-300 bg-white p-12 text-center shadow-sm">
                        <p class="text-lg font-bold text-slate-900">{{ __('layanan_pendukung.no_availability_title') }}</p>
                        <p class="mx-auto mt-2 max-w-md text-sm text-slate-600">{{ __('layanan_pendukung.no_availability_sub') }}</p>
                    </div>
                @else
                    <ul class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($packages as $package)
                            @include('layanan-pendukung.partials.package-card', [
                                'package' => $package,
                                'startsAtInput' => $startsAtInput,
                            ])
                        @endforeach
                    </ul>
                    <div class="flex justify-center pt-2">{{ $packages->links() }}</div>
                @endif
            @endif

            <div class="flex items-center justify-center gap-2 rounded-2xl border border-emerald-100 bg-emerald-50/70 px-4 py-3.5 text-center text-xs font-medium text-emerald-900 sm:text-sm">
                <svg class="h-4 w-4 shrink-0 text-emerald-700" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M10 1a4.5 4.5 0 00-4.5 4.5V9H5a2 2 0 00-2 2v6a2 2 0 002 2h10a2 2 0 002-2v-6a2 2 0 00-2-2h-.5V5.5A4.5 4.5 0 0010 1zm3 8V5.5a3 3 0 10-6 0V9h6z" clip-rule="evenodd" /></svg>
                <span>{{ __('layanan_pendukung.trust_strip') }}</span>
            </div>
        </div>
    </div>
</x-marketplace-layout>
