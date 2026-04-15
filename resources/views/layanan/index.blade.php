@php
    use App\Enums\MuthowifServiceType;

    $listQuery = array_filter([
        'start_date' => $startDate ?? null,
        'end_date' => $endDate ?? null,
        'q' => $searchQuery !== '' ? $searchQuery : null,
    ]);
    $listQueryString = http_build_query($listQuery);
@endphp

<x-marketplace-layout :title="__('layanan.page_title')">
    <div class="relative min-w-0 space-y-10 overflow-x-hidden">
        <div class="pointer-events-none absolute -left-24 top-0 h-72 w-72 rounded-full bg-brand-200/20 blur-3xl" aria-hidden="true"></div>
        <div class="pointer-events-none absolute -right-16 top-40 h-64 w-64 rounded-full bg-amber-200/20 blur-3xl" aria-hidden="true"></div>

        {{-- Hero --}}
        <div class="relative overflow-hidden rounded-3xl border border-slate-200/80 bg-gradient-to-br from-white via-brand-50/50 to-amber-50/40 shadow-market ring-1 ring-slate-100/80">
            <div class="pointer-events-none absolute inset-0 opacity-[0.35]" style="background-image: radial-gradient(circle at 1px 1px, rgb(148 163 184 / 0.45) 1px, transparent 0); background-size: 24px 24px;" aria-hidden="true"></div>
            <div class="pointer-events-none absolute -right-16 -top-16 h-52 w-52 rounded-full bg-brand-300/25 blur-3xl"></div>
            <div class="pointer-events-none absolute -bottom-16 -left-10 h-44 w-44 rounded-full bg-amber-200/30 blur-3xl"></div>
            <div class="relative flex flex-col gap-8 px-6 py-9 sm:px-10 sm:py-11 lg:flex-row lg:items-stretch lg:gap-10">
                <div class="min-w-0 flex-1 lg:max-w-2xl">
                    <p class="text-xs font-bold uppercase tracking-wider text-brand-700">{{ __('layanan.hero_kicker') }}</p>
                    <h1 class="mt-2 text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl lg:text-[2.5rem] lg:leading-[1.15]">{{ __('layanan.hero_title') }}</h1>
                    <p class="mt-3 text-base leading-relaxed text-slate-600 sm:text-lg">
                        {!! __('layanan.hero_lead', ['strong' => '<strong class="font-semibold text-slate-800">'.e(__('layanan.hero_lead_strong')).'</strong>']) !!}
                    </p>
                    <ul class="mt-7 flex flex-wrap gap-2">
                        <li class="inline-flex items-center gap-1.5 rounded-full bg-white/95 px-3 py-1.5 text-xs font-medium text-slate-700 shadow-sm ring-1 ring-slate-200/80 transition hover:ring-brand-200/80">
                            <span class="flex h-5 w-5 items-center justify-center rounded-full bg-emerald-100 text-emerald-700" aria-hidden="true">
                                <svg class="h-3 w-3" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" /></svg>
                            </span>
                            {{ __('layanan.chip_verified') }}
                        </li>
                        <li class="inline-flex items-center gap-1.5 rounded-full bg-white/95 px-3 py-1.5 text-xs font-medium text-slate-700 shadow-sm ring-1 ring-slate-200/80 transition hover:ring-brand-200/80">
                            <span class="flex h-5 w-5 items-center justify-center rounded-full bg-brand-100 text-brand-800" aria-hidden="true">
                                <svg class="h-3 w-3" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" /></svg>
                            </span>
                            {{ __('layanan.chip_realtime') }}
                        </li>
                        <li class="inline-flex items-center gap-1.5 rounded-full bg-white/95 px-3 py-1.5 text-xs font-medium text-slate-700 shadow-sm ring-1 ring-slate-200/80 transition hover:ring-brand-200/80">
                            <span class="flex h-5 w-5 items-center justify-center rounded-full bg-amber-100 text-amber-900" aria-hidden="true">
                                <svg class="h-3 w-3" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path d="M10.75 16.82A7.462 7.462 0 0115 8.5h1.25a.75.75 0 010 1.5H16a8 8 0 01-16 0h-.25a.75.75 0 010-1.5H1a7.462 7.462 0 015.25-8.32.75.75 0 011.5 0A7.462 7.462 0 0115 8.5h.25a.75.75 0 010 1.5H15a7.462 7.462 0 01-4.25 6.82.75.75 0 01-1.5 0z" /></svg>
                            </span>
                            {{ __('layanan.chip_group_private') }}
                        </li>
                    </ul>
                </div>
                <div class="relative shrink-0 lg:w-[min(100%,20rem)]">
                    <div class="flex h-full flex-col justify-center rounded-2xl border border-white/90 bg-white/70 p-6 shadow-lg shadow-slate-900/5 ring-1 ring-slate-200/60 backdrop-blur-md">
                        <div class="flex items-start gap-4">
                            <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-emerald-400 to-emerald-600 text-white shadow-lg shadow-emerald-600/25">
                                <svg class="h-7 w-7" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                                </svg>
                            </span>
                            <div class="min-w-0">
                                <p class="text-sm font-bold leading-snug text-slate-900">{{ __('layanan.hero_aside_title') }}</p>
                                <p class="mt-1.5 text-xs leading-relaxed text-slate-600">{{ __('layanan.hero_aside_sub') }}</p>
                            </div>
                        </div>
                        <div class="mt-5 flex items-center gap-2 rounded-xl bg-slate-50/90 px-3 py-2 text-[11px] font-medium text-slate-600 ring-1 ring-slate-100">
                            <span class="h-1.5 w-1.5 shrink-0 rounded-full bg-emerald-500 shadow-sm shadow-emerald-500/50"></span>
                            {{ __('layanan.help_results') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @include('layanan.partials.date-search-form', [
            'startDate' => $startDate,
            'endDate' => $endDate,
            'searchQuery' => $searchQuery,
        ])

        @if ($dateErrors?->isNotEmpty())
            <div class="flex gap-3 rounded-2xl border border-red-200 bg-red-50/90 px-4 py-4 text-sm text-red-900 shadow-sm ring-1 ring-red-100/80" role="alert">
                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-red-100 text-red-700">
                    <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-5a.75.75 0 01.75.75v4.5a.75.75 0 01-1.5 0v-4.5A.75.75 0 0110 5zm0 10a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                    </svg>
                </span>
                <ul class="list-inside list-disc space-y-1 pt-0.5">
                    @foreach ($dateErrors->all() as $message)
                        <li>{{ $message }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (! $hasDateSearch)
            <div class="relative overflow-hidden rounded-3xl border-2 border-dashed border-brand-300/70 bg-gradient-to-br from-brand-50/80 via-white to-amber-50/50 p-10 text-center shadow-inner">
                <div class="pointer-events-none absolute inset-0 opacity-25" style="background-image: radial-gradient(circle at 1px 1px, rgb(20 184 166 / 0.35) 1px, transparent 0); background-size: 22px 22px;" aria-hidden="true"></div>
                <div class="relative mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-white shadow-md ring-1 ring-brand-200/60">
                    <svg class="h-8 w-8 text-brand-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5a2.25 2.25 0 002.25-2.25m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5a2.25 2.25 0 012.25 2.25v7.5" />
                    </svg>
                </div>
                <p class="relative z-10 mt-6 font-semibold text-slate-900">{!! __('layanan.empty_state_title', ['strong' => '<strong class="text-brand-800">'.e(__('layanan.empty_state_title_strong')).'</strong>']) !!}</p>
                <p class="relative z-10 mx-auto mt-2 max-w-md text-sm text-slate-600">{{ __('layanan.empty_state_sub') }}</p>
            </div>
        @else
            @if ($dateErrors === null || $dateErrors->isEmpty())
                @if (filled($rangeLabel))
                    <div class="flex flex-col gap-3 rounded-2xl border border-slate-200/90 bg-white/95 px-4 py-4 shadow-md shadow-slate-900/5 ring-1 ring-slate-100/90 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                        <p class="flex flex-wrap items-center gap-3 text-sm text-slate-600">
                            <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-brand-100 to-brand-50 text-brand-700 ring-1 ring-brand-200/60 shadow-sm">
                                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M5.75 2a.75.75 0 01.75.75V4h7V2.75a.75.75 0 011.5 0V4h.25A2.75 2.75 0 0118 6.75v8.5A2.75 2.75 0 0115.25 18H4.75A2.75 2.75 0 012 15.25v-8.5A2.75 2.75 0 014.75 4H5V2.75A.75.75 0 015.75 2zm-1 5.5c-.69 0-1.25.56-1.25 1.25v6.5c0 .69.56 1.25 1.25 1.25h10.5c.69 0 1.25-.56 1.25-1.25v-6.5c0-.69-.56-1.25-1.25-1.25H4.75z" clip-rule="evenodd" />
                                </svg>
                            </span>
                            <span class="min-w-0">
                                {{ __('layanan.range_prefix') }} <span class="font-semibold text-slate-900">{{ $rangeLabel }}</span>
                                @if ($profiles->total() > 0)
                                    <span class="mx-1.5 inline-block h-1 w-1 rounded-full bg-slate-300 align-middle"></span>
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-bold text-emerald-900 ring-1 ring-emerald-200/80">
                                        {{ __('layanan.companions_ready', ['count' => $profiles->total()]) }}
                                    </span>
                                @endif
                            </span>
                        </p>
                    </div>
                @endif

                @if ($profiles->isEmpty())
                    <div class="relative overflow-hidden rounded-3xl border border-dashed border-slate-300/90 bg-gradient-to-b from-white to-slate-50/80 p-12 text-center shadow-inner">
                        <div class="pointer-events-none absolute inset-0 opacity-30" style="background-image: radial-gradient(circle at 1px 1px, rgb(203 213 225) 1px, transparent 0); background-size: 20px 20px;" aria-hidden="true"></div>
                        <div class="relative mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-slate-100 text-slate-500 shadow-inner ring-1 ring-slate-200/80">
                            <svg class="h-8 w-8" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <p class="relative mt-6 text-lg font-bold text-slate-900">{{ __('layanan.no_results_title') }}</p>
                        <p class="relative mx-auto mt-2 max-w-md text-sm leading-relaxed text-slate-600">{{ __('layanan.no_results_sub') }}</p>
                    </div>
                @else
                    <ul class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-3">
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
                            ])
                        @endforeach
                    </ul>

                    <div class="flex justify-center pt-2">
                        <div class="rounded-2xl border border-slate-200/80 bg-white/80 px-2 py-1 shadow-sm backdrop-blur-sm">
                            {{ $profiles->links() }}
                        </div>
                    </div>
                @endif
            @endif
        @endif
    </div>
</x-marketplace-layout>
