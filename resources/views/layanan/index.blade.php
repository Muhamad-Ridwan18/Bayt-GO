@php
    use App\Enums\MuthowifServiceType;

    $listQuery = array_filter([
        'start_date' => $startDate ?? null,
        'end_date' => $endDate ?? null,
        'q' => $searchQuery !== '' ? $searchQuery : null,
    ]);
    $listQueryString = http_build_query($listQuery);

    $welcomeHeroBg = null;
    foreach (['webp', 'png', 'jpg', 'jpeg'] as $ext) {
        if (file_exists(public_path('images/bg-welcome.'.$ext))) {
            $welcomeHeroBg = asset('images/bg-welcome.'.$ext);
            break;
        }
    }
    if ($welcomeHeroBg === null && is_dir(public_path('images/bg-welcome'))) {
        $entries = array_diff(scandir(public_path('images/bg-welcome')) ?: [], ['.', '..']);
        sort($entries, SORT_NATURAL | SORT_FLAG_CASE);
        foreach ($entries as $name) {
            if (preg_match('/\.(jpe?g|png|webp)$/i', $name)) {
                $welcomeHeroBg = asset('images/bg-welcome/'.$name);
                break;
            }
        }
    }
    if ($welcomeHeroBg === null) {
        $welcomeHeroBg = file_exists(public_path('images/welcome-hero.jpg'))
            ? asset('images/welcome-hero.jpg')
            : 'https://images.unsplash.com/photo-1519817914152-22d216bb9170?q=85&w=2160&auto=format&fit=crop';
    }

    $seoTitle = __('layanan.page_title').' | Jasa Tour Guide Umroh & Haji Terpercaya';
    if (filled($searchQuery ?? '')) {
        $seoTitle = "Cari Jasa Tour Guide Umroh/Haji '".e($searchQuery)."' — Muthowif Terverifikasi";
    }
    $seoDesc = 'Temukan dan sewa jasa Muthowif profesional terverifikasi serta asisten tour guide ibadah Umroh & Haji terbaik di Bayt-GO. Bandingkan tarif harian, rating, dan ulasan.';

    $hasActiveFilters = filled($startDate ?? '') || filled($searchQuery ?? '');
@endphp

<x-marketplace-layout :title="$seoTitle" :meta-description="$seoDesc" :full-bleed="true">
    <div class="relative min-w-0 overflow-x-hidden">
        {{-- Hero full-bleed + form mengambang --}}
        <section class="relative left-1/2 mb-0 w-screen max-w-[100vw] -translate-x-1/2 overflow-hidden bg-welcomeCanvas pb-10 sm:pb-12">
            <div class="pointer-events-none absolute inset-0 z-0" aria-hidden="true">
                <img src="{{ $welcomeHeroBg }}" alt="" class="h-full w-full min-h-[16rem] object-cover object-[74%_30%] sm:min-h-[18rem] lg:min-h-[20rem]" loading="eager" decoding="async" />
            </div>
            <div class="pointer-events-none absolute inset-0 z-[1] bg-gradient-to-b from-welcomeCanvas via-welcomeCanvas/95 to-welcomeCanvas/55 sm:hidden" aria-hidden="true"></div>
            <div class="pointer-events-none absolute inset-0 z-[1] hidden bg-gradient-to-r from-welcomeCanvas from-[28%] via-welcomeCanvas/96 via-[55%] to-welcomeCanvas/10 sm:block lg:from-[32%] lg:via-[58%] lg:to-transparent" aria-hidden="true"></div>

            <div class="relative z-10 mx-auto max-w-6xl px-4 pt-8 sm:px-6 sm:pt-10 lg:pt-12">
                <p class="text-xs font-bold uppercase tracking-wider text-baytgo">{{ __('layanan.hero_kicker') }}</p>
                <h1 class="mt-2 max-w-2xl text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl lg:text-4xl">{{ __('layanan.hero_title') }}</h1>
                <p class="mt-3 max-w-xl text-base leading-relaxed text-slate-700 sm:text-lg">{{ __('layanan.hero_lead') }}</p>
                <ul class="mt-6 flex flex-wrap gap-2">
                    @foreach ([
                        ['key' => 'chip_verified', 'icon' => 'check'],
                        ['key' => 'chip_realtime', 'icon' => 'clock'],
                        ['key' => 'chip_secure', 'icon' => 'lock'],
                    ] as $chip)
                        <li class="inline-flex items-center gap-1.5 rounded-full border border-white/80 bg-white/95 px-3 py-1.5 text-xs font-semibold text-slate-800 shadow-sm ring-1 ring-slate-100/90">
                            <span class="flex h-5 w-5 items-center justify-center rounded-full bg-emerald-50 text-emerald-700" aria-hidden="true">
                                @if ($chip['icon'] === 'check')
                                    <svg class="h-3 w-3" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" /></svg>
                                @elseif ($chip['icon'] === 'clock')
                                    <svg class="h-3 w-3" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm.75-13a.75.75 0 00-1.5 0v5c0 .414.336.75.75.75h4a.75.75 0 000-1.5h-3.25V5z" clip-rule="evenodd" /></svg>
                                @else
                                    <svg class="h-3 w-3" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 1a4.5 4.5 0 00-4.5 4.5V9H5a2 2 0 00-2 2v6a2 2 0 002 2h10a2 2 0 002-2v-6a2 2 0 00-2-2h-.5V5.5A4.5 4.5 0 0010 1zm3 8V5.5a3 3 0 10-6 0V9h6z" clip-rule="evenodd" /></svg>
                                @endif
                            </span>
                            {{ __('layanan.'.$chip['key']) }}
                        </li>
                    @endforeach
                </ul>
            </div>

            <div class="relative z-20 mx-auto mt-8 max-w-6xl px-4 sm:mt-10 sm:px-6" id="marketplace-search">
                <div class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-[0_20px_50px_-12px_rgba(15,42,37,0.18)] ring-1 ring-slate-100/90 sm:rounded-3xl">
                    @include('layanan.partials.date-search-form', [
                        'startDate' => $startDate,
                        'endDate' => $endDate,
                        'searchQuery' => $searchQuery,
                        'showHeaderBanner' => false,
                        'marketplaceMode' => true,
                    ])
                </div>
            </div>
        </section>

        <div class="relative mx-auto max-w-6xl ui-stack px-4 py-8 sm:px-6 sm:py-10">
            @if ($dateErrors?->isNotEmpty())
                <div class="flex gap-3 rounded-2xl border border-red-200 bg-red-50/90 px-4 py-4 text-sm text-red-900 shadow-sm ring-1 ring-red-100/80" role="alert">
                    <ul class="list-inside list-disc space-y-1">
                        @foreach ($dateErrors->all() as $message)
                            <li>{{ $message }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if (! $hasDateSearch)
                <div class="rounded-3xl border-2 border-dashed border-baytgo/30 bg-gradient-to-br from-emerald-50/50 via-white to-white p-10 text-center">
                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-white text-baytgo shadow-md ring-1 ring-emerald-100">
                        <svg class="h-7 w-7" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5a2.25 2.25 0 002.25-2.25m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5a2.25 2.25 0 012.25 2.25v7.5" /></svg>
                    </div>
                    <p class="mt-6 font-semibold text-slate-900">{!! __('layanan.empty_state_title', ['strong' => '<strong class="text-baytgo">'.e(__('layanan.empty_state_title_strong')).'</strong>']) !!}</p>
                    <p class="mx-auto mt-2 max-w-md text-sm text-slate-600">{{ __('layanan.empty_state_sub') }}</p>
                </div>
            @else
                @if ($dateErrors === null || $dateErrors->isEmpty())
                    @if (filled($rangeLabel))
                        <div class="flex flex-col gap-4 rounded-2xl border border-slate-200/90 bg-white px-4 py-4 shadow-sm ring-1 ring-slate-100/90 sm:flex-row sm:items-center sm:justify-between sm:px-5">
                            <div class="min-w-0">
                                <p class="flex flex-wrap items-center gap-2 text-sm font-semibold text-slate-900">
                                    <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-emerald-50 text-baytgo">
                                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.75 2a.75.75 0 01.75.75V4h7V2.75a.75.75 0 011.5 0V4h.25A2.75 2.75 0 0118 6.75v8.5A2.75 2.75 0 0115.25 18H4.75A2.75 2.75 0 012 15.25v-8.5A2.75 2.75 0 014.75 4H5V2.75A.75.75 0 015.75 2zm-1 5.5c-.69 0-1.25.56-1.25 1.25v6.5c0 .69.56 1.25 1.25 1.25h10.5c.69 0 1.25-.56 1.25-1.25v-6.5c0-.69-.56-1.25-1.25-1.25H4.75z" clip-rule="evenodd" /></svg>
                                    </span>
                                    @if ($profiles->total() > 0)
                                        {{ __('layanan.results_count', ['count' => $profiles->total()]) }}
                                    @else
                                        {{ __('layanan.companions_ready', ['count' => 0]) }}
                                    @endif
                                    <span class="font-normal text-slate-500">{{ __('layanan.results_for_range', ['range' => $rangeLabel]) }}</span>
                                </p>
                                <div class="mt-3 flex flex-wrap items-center gap-2">
                                    <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700">{{ $rangeLabel }}</span>
                                    @if (filled($searchQuery))
                                        <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700">{{ $searchQuery }}</span>
                                    @endif
                                    <span class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-900 ring-1 ring-emerald-100">{{ __('layanan.filter_active_verified') }}</span>
                                    @if ($hasActiveFilters)
                                        <a href="{{ route('layanan.index') }}" class="text-xs font-semibold text-baytgo hover:text-baytgo-800">{{ __('layanan.filter_clear_all') }}</a>
                                    @endif
                                </div>
                            </div>
                            <p class="shrink-0 text-sm text-slate-600">
                                <span class="font-medium text-slate-800">{{ __('layanan.sort_label') }}</span>
                                <span class="font-semibold text-baytgo">{{ __('layanan.sort_recommended') }}</span>
                            </p>
                        </div>
                    @endif

                    @if ($profiles->isEmpty())
                        <div class="rounded-3xl border border-dashed border-slate-300 bg-white p-12 text-center shadow-sm">
                            <p class="text-lg font-bold text-slate-900">{{ __('layanan.no_results_title') }}</p>
                            <p class="mx-auto mt-2 max-w-md text-sm text-slate-600">{{ __('layanan.no_results_sub') }}</p>
                            <a href="#marketplace-search" class="mt-6 inline-flex items-center justify-center rounded-xl bg-baytgo px-5 py-2.5 text-sm font-semibold text-white shadow-md hover:bg-baytgo-800">{{ __('layanan.submit_search') }}</a>
                        </div>
                    @else
                        <ul class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3 lg:gap-6">
                            @foreach ($profiles as $profile)
                                @php
                                    $group = $profile->services->firstWhere('type', MuthowifServiceType::Group);
                                    $private = $profile->services->firstWhere('type', MuthowifServiceType::PrivateJamaah);
                                @endphp
                                @include('layanan.partials.muthowif-card', [
                                    'profile' => $profile,
                                    'group' => $group,
                                    'private' => $private,
                                    'listQueryString' => $listQueryString,
                                    'rangeLabel' => $rangeLabel,
                                ])
                            @endforeach
                        </ul>

                        <div class="flex justify-center pt-4">
                            {{ $profiles->links() }}
                        </div>
                    @endif
                @endif
            @endif

            @include('layanan.partials.marketplace-trust-strip')
        </div>
    </div>
</x-marketplace-layout>
