<form method="GET" action="{{ route('layanan.index') }}" class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm ring-1 ring-slate-100 sm:p-5">
    <div class="flex items-start gap-3">
        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-baytgo ring-1 ring-emerald-100">
            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                <path fill-rule="evenodd" d="M6.75 2.25A.75.75 0 017.5 3v1.5h9V3A.75.75 0 0118 3v1.5h.75a3 3 0 013 3v11.25a3 3 0 01-3 3H5.25a3 3 0 01-3-3V7.5a3 3 0 013-3H6V3a.75.75 0 01.75-.75zm13.5 9a1.5 1.5 0 00-1.5-1.5H5.25a1.5 1.5 0 00-1.5 1.5v7.5a1.5 1.5 0 001.5 1.5h13.5a1.5 1.5 0 001.5-1.5v-7.5z" clip-rule="evenodd" />
            </svg>
        </span>
        <div class="min-w-0">
            <h2 class="text-base font-bold text-slate-900 sm:text-lg">{{ __('welcome.search_section_title') }}</h2>
            <p class="mt-0.5 text-xs text-slate-500 sm:text-sm">{{ __('layanan.search_form_sub') }}</p>
        </div>
    </div>

    <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-12 lg:items-end lg:gap-3">
        <div class="sm:col-span-1 lg:col-span-3">
            <label for="dashboard_start_date" class="block text-sm font-medium text-slate-700">{{ __('layanan.start_date') }}</label>
            <input
                type="date"
                name="start_date"
                id="dashboard_start_date"
                value=""
                min="{{ now()->toDateString() }}"
                required
                class="mt-1.5 block h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-900 focus:border-baytgo focus:outline-none focus:ring-2 focus:ring-baytgo/20"
            />
        </div>
        <div class="sm:col-span-1 lg:col-span-3">
            <label for="dashboard_end_date" class="block text-sm font-medium text-slate-700">{{ __('layanan.end_date') }}</label>
            <input
                type="date"
                name="end_date"
                id="dashboard_end_date"
                value=""
                min="{{ now()->toDateString() }}"
                class="mt-1.5 block h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-900 focus:border-baytgo focus:outline-none focus:ring-2 focus:ring-baytgo/20"
                title="{{ __('layanan.end_date_title') }}"
            />
        </div>
        <div class="sm:col-span-2 lg:col-span-4">
            <label for="dashboard_q" class="block text-sm font-medium text-slate-700">{{ __('layanan.name_label') }}</label>
            <input
                type="search"
                name="q"
                id="dashboard_q"
                value=""
                placeholder="{{ __('layanan.name_placeholder') }}"
                autocomplete="off"
                class="mt-1.5 block h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-900 placeholder:text-slate-400 focus:border-baytgo focus:outline-none focus:ring-2 focus:ring-baytgo/20"
            />
        </div>
        <div class="sm:col-span-2 lg:col-span-2">
            <x-submit-button class="inline-flex h-11 w-full items-center justify-center gap-2 rounded-xl bg-baytgo px-5 text-sm font-semibold text-white shadow-md hover:bg-baytgo-800">
                <svg class="h-4 w-4 shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z" clip-rule="evenodd" /></svg>
                {{ __('layanan.submit_search') }}
            </x-submit-button>
        </div>
    </div>

    <p class="mt-4 rounded-xl border border-emerald-100 bg-emerald-50/80 px-3 py-2.5 text-xs leading-relaxed text-emerald-900 sm:text-sm">
        <span class="font-semibold">{{ __('welcome.search_tip_label') }}</span>
        {{ __('welcome.search_tip_body') }}
    </p>
</form>
