@php
    use App\Support\IndonesianNumber;

    $listQuery = array_filter([
        'q' => ($searchQuery ?? '') !== '' ? $searchQuery : null,
        'category' => ($activeCategory ?? null)?->value,
    ]);
    $listQueryString = http_build_query($listQuery);
@endphp

<x-marketplace-layout :title="__('layanan_pendukung.page_title')" :meta-description="__('layanan_pendukung.hero_lead')">
    <div class="ui-stack-compact">
        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
            <p class="text-xs font-bold uppercase tracking-wider text-baytgo">{{ __('layanan_pendukung.hero_kicker') }}</p>
            <h1 class="mt-2 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">{{ __('layanan_pendukung.hero_title') }}</h1>
            <p class="mt-3 max-w-2xl text-sm leading-relaxed text-slate-600 sm:text-base">{{ __('layanan_pendukung.hero_lead') }}</p>

            <form method="GET" action="{{ route('layanan-pendukung.index') }}" class="mt-6 space-y-4">
                @if ($activeCategory)
                    <input type="hidden" name="category" value="{{ $activeCategory->value }}">
                @endif
                <div class="flex flex-col gap-3 sm:flex-row">
                    <input
                        type="search"
                        name="q"
                        value="{{ $searchQuery }}"
                        placeholder="{{ __('layanan_pendukung.search_placeholder') }}"
                        class="min-w-0 flex-1 rounded-xl border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500"
                    >
                    <x-submit-button class="rounded-xl bg-baytgo px-5 py-2.5 text-sm font-semibold text-white shadow-md hover:bg-baytgo-800">
                        {{ __('layanan_pendukung.search_submit') }}
                    </x-submit-button>
                </div>
            </form>

            <div class="mt-5">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('layanan_pendukung.category_filter') }}</p>
                <div class="mt-2 flex flex-wrap gap-2">
                    @php
                        $allQuery = array_filter(['q' => ($searchQuery ?? '') !== '' ? $searchQuery : null]);
                    @endphp
                    <a href="{{ route('layanan-pendukung.index', $allQuery) }}"
                       @class([
                           'inline-flex rounded-full px-3 py-1.5 text-xs font-semibold ring-1 transition',
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
                               'inline-flex rounded-full px-3 py-1.5 text-xs font-semibold ring-1 transition',
                               ($activeCategory ?? null) === $cat
                                   ? 'bg-baytgo text-white ring-baytgo'
                                   : 'bg-white text-slate-700 ring-slate-200 hover:bg-slate-50',
                           ])>
                            {{ $cat->label() }}
                        </a>
                    @endforeach
                </div>
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
            <div class="flex justify-center pt-4">{{ $packages->links() }}</div>
        @endif
    </div>
</x-marketplace-layout>
