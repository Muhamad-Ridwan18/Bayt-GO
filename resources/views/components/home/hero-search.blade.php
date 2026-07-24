@props(['page'])

<section
    id="cari-layanan"
    class="relative left-1/2 right-1/2 -ml-[50vw] -mr-[50vw] w-screen scroll-mt-24 bg-baytgo-950"
    aria-label="{{ __('dashboard.customer_hero_sub') }}"
    x-data="homeSearch(@js($page->searchAlpineConfig()))"
>
    <div class="pointer-events-none absolute inset-0 overflow-hidden" aria-hidden="true">
        <img src="{{ $page->heroBgUrl }}" alt="" class="h-full w-full object-cover object-[center_35%]" loading="eager" decoding="async" />
        <div class="absolute inset-0 bg-gradient-to-b from-baytgo-950/75 via-baytgo-950/55 to-baytgo-950/85"></div>
    </div>

    <div class="relative w-full home-section-pad pb-14 pt-10 sm:pb-16 sm:pt-14 lg:pb-20 lg:pt-16">
        @if ($page->heroName)
            <p class="mb-2 text-sm font-medium text-white/80 sm:mb-3">{{ __('dashboard.customer_hero_intro') }} {{ $page->heroName }}</p>
        @endif

        <h1 class="w-full text-[1.5rem] font-bold leading-[1.2] tracking-tight text-white sm:text-4xl lg:text-5xl">
            {{ __('welcome.landing_hero_lead') }}
            <span class="text-gold-muted">{{ __('welcome.landing_hero_accent') }}</span>
        </h1>
        <p class="mt-3 w-full text-sm leading-relaxed text-white/85 sm:mt-4 sm:text-base">{{ __('welcome.landing_hero_sub') }}</p>

        <div class="mt-8 w-full sm:mt-10">
            <div class="grid w-full grid-cols-5 gap-1 sm:gap-2 md:gap-3" role="tablist">
                @foreach ($page->categories as $cat)
                    <button
                        type="button"
                        role="tab"
                        @click="select(@js($cat))"
                        class="flex min-w-0 flex-col items-center gap-1.5 rounded-xl px-1 py-2.5 text-center outline-none transition focus-visible:ring-2 focus-visible:ring-white/40 sm:rounded-2xl sm:px-2 sm:py-3"
                        :class="selected?.key === @js($cat['key'])
                            ? 'bg-white text-baytgo shadow-lg'
                            : 'text-white hover:bg-white/10'"
                    >
                        <span
                            class="flex h-8 w-8 items-center justify-center sm:h-9 sm:w-9"
                            :class="selected?.key === @js($cat['key']) ? 'text-baytgo' : 'text-white'"
                            aria-hidden="true"
                        >
                            <x-home.category-icon :icon="$cat['icon']" />
                        </span>
                        <span
                            class="line-clamp-2 w-full text-[9px] font-semibold leading-tight sm:text-[11px] md:text-xs"
                            :class="selected?.key === @js($cat['key']) ? 'text-baytgo' : 'text-white/90'"
                        >{{ $cat['label'] }}</span>
                    </button>
                @endforeach
            </div>
        </div>

        <div class="mt-4 h-px w-full bg-white/20 sm:mt-5" aria-hidden="true"></div>

        <div class="mt-4 w-full rounded-2xl bg-white p-3 shadow-xl shadow-black/20 sm:mt-5 sm:rounded-3xl sm:p-4">
            <div class="flex w-full flex-col gap-3 sm:flex-row sm:items-end sm:gap-3">
                <div class="min-w-0 w-full flex-1">
                    <div x-show="selected?.type === 'layanan'" x-ref="umrohDates" class="w-full">
                        <x-date-range-picker
                            :min-date="$page->search['minDate']"
                            :label="__('layanan.date_range')"
                            :required="false"
                            input-class="block w-full h-12 rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm font-medium text-slate-900 shadow-sm transition hover:border-baytgo/30 hover:bg-white focus:border-baytgo focus:outline-none focus:ring-2 focus:ring-baytgo/20 sm:rounded-2xl"
                        />
                    </div>

                    <div x-show="selected?.type === 'support'" x-cloak class="w-full">
                        <label for="home_starts_at" class="block text-sm font-medium text-slate-700">{{ __('layanan_pendukung.starts_at') }}</label>
                        <div class="relative mt-2 w-full">
                            <span class="pointer-events-none absolute inset-y-0 left-3.5 flex items-center text-slate-400">
                                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M6.75 2.25A.75.75 0 017.5 3v1.5h9V3A.75.75 0 0118 3v1.5h.75a3 3 0 013 3v11.25a3 3 0 01-3 3H5.25a3 3 0 01-3-3V7.5a3 3 0 013-3H6V3a.75.75 0 01.75-.75zm13.5 9a1.5 1.5 0 00-1.5-1.5H5.25a1.5 1.5 0 00-1.5 1.5v7.5a1.5 1.5 0 001.5 1.5h13.5a1.5 1.5 0 001.5-1.5v-7.5z" clip-rule="evenodd" /></svg>
                            </span>
                            <input
                                id="home_starts_at"
                                type="datetime-local"
                                x-model="startsAt"
                                min="{{ $page->search['minStartsAt'] }}"
                                class="block h-12 w-full rounded-xl border border-slate-200 bg-slate-50 py-2 pl-11 pr-4 text-sm font-medium text-slate-900 shadow-sm transition hover:border-baytgo/30 hover:bg-white focus:border-baytgo focus:outline-none focus:ring-2 focus:ring-baytgo/20 sm:rounded-2xl"
                            >
                        </div>
                    </div>

                    <p x-show="dateError" x-cloak class="mt-2 text-xs font-medium text-red-600" x-text="dateError"></p>
                </div>

                <button
                    type="button"
                    @click="search()"
                    class="inline-flex h-12 w-full shrink-0 items-center justify-center gap-2 rounded-xl bg-baytgo px-6 text-sm font-bold text-white shadow-lg shadow-baytgo/25 transition hover:bg-baytgo-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-baytgo/40 focus-visible:ring-offset-2 sm:w-auto sm:min-w-[3.5rem] sm:rounded-2xl sm:px-5 lg:min-w-[11rem]"
                    aria-label="{{ __('welcome.landing_search_cta') }}"
                >
                    <svg class="h-5 w-5 shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z" clip-rule="evenodd" /></svg>
                    <span>{{ __('welcome.landing_search_cta') }}</span>
                </button>
            </div>
        </div>
    </div>
</section>
