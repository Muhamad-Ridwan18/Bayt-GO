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
    <div class="space-y-10">
        {{-- Hero marketplace --}}
        <div class="relative overflow-hidden rounded-3xl border border-slate-200/80 bg-gradient-to-br from-white via-brand-50/40 to-amber-50/30 px-6 py-8 sm:px-10 sm:py-10 shadow-market ring-1 ring-slate-100/80">
            <div class="pointer-events-none absolute -right-16 -top-16 h-48 w-48 rounded-full bg-brand-200/30 blur-3xl"></div>
            <div class="pointer-events-none absolute -bottom-20 -left-10 h-40 w-40 rounded-full bg-amber-200/25 blur-3xl"></div>
            <div class="relative max-w-3xl">
                <p class="text-xs font-bold uppercase tracking-wider text-brand-700">{{ __('layanan.hero_kicker') }}</p>
                <h1 class="mt-2 text-3xl sm:text-4xl font-bold tracking-tight text-slate-900">{{ __('layanan.hero_title') }}</h1>
                <p class="mt-3 text-base text-slate-600 leading-relaxed">
                    {!! __('layanan.hero_lead', ['strong' => '<strong>'.e(__('layanan.hero_lead_strong')).'</strong>']) !!}
                </p>
                <ul class="mt-6 flex flex-wrap gap-2">
                    <li class="inline-flex items-center gap-1.5 rounded-full bg-white/90 px-3 py-1.5 text-xs font-medium text-slate-700 shadow-sm ring-1 ring-slate-200/80">
                        <span class="flex h-5 w-5 items-center justify-center rounded-full bg-emerald-100 text-emerald-700" aria-hidden="true">
                            <svg class="h-3 w-3" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" /></svg>
                        </span>
                        {{ __('layanan.chip_verified') }}
                    </li>
                    <li class="inline-flex items-center gap-1.5 rounded-full bg-white/90 px-3 py-1.5 text-xs font-medium text-slate-700 shadow-sm ring-1 ring-slate-200/80">
                        <span class="flex h-5 w-5 items-center justify-center rounded-full bg-brand-100 text-brand-800" aria-hidden="true">
                            <svg class="h-3 w-3" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" /></svg>
                        </span>
                        {{ __('layanan.chip_realtime') }}
                    </li>
                    <li class="inline-flex items-center gap-1.5 rounded-full bg-white/90 px-3 py-1.5 text-xs font-medium text-slate-700 shadow-sm ring-1 ring-slate-200/80">
                        <span class="flex h-5 w-5 items-center justify-center rounded-full bg-amber-100 text-amber-900" aria-hidden="true">
                            <svg class="h-3 w-3" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path d="M10.75 16.82A7.462 7.462 0 0115 8.5h1.25a.75.75 0 010 1.5H16a8 8 0 01-16 0h-.25a.75.75 0 010-1.5H1a7.462 7.462 0 015.25-8.32.75.75 0 011.5 0A7.462 7.462 0 0115 8.5h.25a.75.75 0 010 1.5H15a7.462 7.462 0 01-4.25 6.82.75.75 0 01-1.5 0z" /></svg>
                        </span>
                        {{ __('layanan.chip_group_private') }}
                    </li>
                </ul>
            </div>
        </div>

        @include('layanan.partials.date-search-form', [
            'startDate' => $startDate,
            'endDate' => $endDate,
            'searchQuery' => $searchQuery,
        ])

        @if ($dateErrors?->isNotEmpty())
            <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                <ul class="list-disc list-inside space-y-1">
                    @foreach ($dateErrors->all() as $message)
                        <li>{{ $message }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (! $hasDateSearch)
            <div class="rounded-2xl border border-dashed border-brand-300/80 bg-gradient-to-br from-brand-50/60 to-white p-10 text-center shadow-inner">
                <p class="font-semibold text-slate-900">{!! __('layanan.empty_state_title', ['strong' => '<strong class="text-brand-800">'.e(__('layanan.empty_state_title_strong')).'</strong>']) !!}</p>
                <p class="mt-2 text-sm text-slate-600 max-w-md mx-auto">{{ __('layanan.empty_state_sub') }}</p>
            </div>
        @else
            @if ($dateErrors === null || $dateErrors->isEmpty())
                @if (filled($rangeLabel))
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between sm:gap-4">
                        <p class="text-sm text-slate-600">
                            {{ __('layanan.range_prefix') }} <span class="font-semibold text-slate-900">{{ $rangeLabel }}</span>
                            @if ($profiles->total() > 0)
                                <span class="text-slate-400">·</span>
                                <span class="font-semibold text-brand-800">{{ __('layanan.companions_ready', ['count' => $profiles->total()]) }}</span>
                            @endif
                        </p>
                    </div>
                @endif

                @if ($profiles->isEmpty())
                    <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-12 text-center shadow-sm">
                        <p class="text-slate-800 font-medium">{{ __('layanan.no_results_title') }}</p>
                        <p class="mt-2 text-sm text-slate-600 max-w-md mx-auto">{{ __('layanan.no_results_sub') }}</p>
                    </div>
                @else
                    <ul class="grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-3">
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

                    <div class="mt-8 flex justify-center">
                        {{ $profiles->links() }}
                    </div>
                @endif
            @endif
        @endif
    </div>
</x-marketplace-layout>
