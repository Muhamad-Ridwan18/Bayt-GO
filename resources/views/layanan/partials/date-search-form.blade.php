@props([
    'startDate' => '',
    'endDate' => '',
    'searchQuery' => '',
])

@php
    $dateClass = 'block w-full min-w-[11rem] h-11 rounded-xl border-slate-300 bg-white px-3 shadow-sm focus:border-brand-500 focus:ring-brand-500 text-sm text-slate-900';
    $textClass = 'block w-full min-w-0 h-11 rounded-xl border-slate-300 bg-white px-3 shadow-sm focus:border-brand-500 focus:ring-brand-500 text-sm text-slate-900';
@endphp

<form method="GET" action="{{ route('layanan.index') }}" class="rounded-3xl border border-slate-200/90 bg-white p-5 sm:p-6 md:p-7 shadow-market ring-1 ring-brand-100/60 w-full min-w-0">
    {{-- Flex: hindari kolom 12 yang membuat tombol terlalu sempit di container sempit --}}
    <div class="flex flex-col gap-4 lg:flex-row lg:flex-wrap lg:items-end lg:gap-x-3 lg:gap-y-4">
        <div class="w-full sm:max-w-md lg:w-[13.5rem] lg:max-w-none lg:flex-shrink-0">
            <label for="start_date" class="block text-sm font-medium text-slate-700">{{ __('layanan.start_date') }}</label>
            <input type="date" name="start_date" id="start_date" value="{{ $startDate }}"
                   min="{{ now()->toDateString() }}" required
                   class="{{ $dateClass }} mt-2" />
        </div>
        <div class="w-full sm:max-w-md lg:w-[13.5rem] lg:max-w-none lg:flex-shrink-0">
            <label for="end_date" class="block text-sm font-medium text-slate-700">{{ __('layanan.end_date') }}</label>
            <input type="date" name="end_date" id="end_date" value="{{ $endDate }}"
                   min="{{ now()->toDateString() }}"
                   class="{{ $dateClass }} mt-2"
                   title="{{ __('layanan.end_date_title') }}" />
        </div>
        <div class="w-full lg:min-w-[12rem] lg:flex-1">
            <label for="q" class="block text-sm font-medium text-slate-700">{{ __('layanan.name_label') }}</label>
            <input type="search" name="q" id="q" value="{{ $searchQuery }}"
                   placeholder="{{ __('layanan.name_placeholder') }}"
                   autocomplete="off"
                   class="{{ $textClass }} mt-2 placeholder:text-slate-400" />
        </div>
        <div class="w-full lg:w-auto lg:flex-shrink-0 lg:min-w-[11.5rem] lg:pt-[1.75rem]">
            <button type="submit"
                    class="inline-flex h-11 w-full min-h-[2.75rem] items-center justify-center gap-2 whitespace-nowrap rounded-xl bg-brand-600 px-4 text-sm font-semibold text-white shadow-md shadow-brand-600/25 transition hover:bg-brand-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:ring-offset-2">
                <svg class="h-4 w-4 shrink-0 opacity-90" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z" clip-rule="evenodd" /></svg>
                {{ __('layanan.submit_search') }}
            </button>
        </div>
    </div>

    <p class="mt-4 text-xs leading-relaxed text-slate-500">
        <span class="text-slate-600 font-medium">{{ __('layanan.help_quick') }}</span> {{ __('layanan.help_body') }}
        <span class="hidden sm:inline"> </span>
        <span class="block sm:inline mt-1 sm:mt-0 text-slate-400">{{ __('layanan.help_results') }}</span>
    </p>
</form>
