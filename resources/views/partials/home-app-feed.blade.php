@php
    $homeHeroName = $homeHeroName ?? null;
    $homeHelpHref = $homeHelpHref ?? (Route::has('support.index') ? route('support.index') : route('layanan.index'));
    $homeGuideCards = $homeGuideCards ?? [];
    $activeCampaigns = $activeCampaigns ?? collect();
    $featuredMuthowifs = $featuredMuthowifs ?? collect();
    $latestArticles = $latestArticles ?? collect();
    $galleryImages = $galleryImages ?? collect();
    $showLandingChrome = $showLandingChrome ?? true;
    $sectionPad = 'px-4 sm:px-6 lg:px-8 xl:px-10';
@endphp

<div>
    @php
        $homeCategories = [
            [
                'key' => 'umroh',
                'label' => __('dashboard.customer_cat_umroh'),
                'type' => 'layanan',
                'url' => route('layanan.index'),
                'icon' => 'umroh',
            ],
            [
                'key' => 'mobility',
                'label' => __('dashboard.customer_cat_wheelchair'),
                'type' => 'support',
                'category' => 'mobility',
                'url' => route('layanan-pendukung.index', ['category' => 'mobility']),
                'icon' => 'mobility',
            ],
            [
                'key' => 'umrah',
                'label' => __('dashboard.customer_cat_prayer'),
                'type' => 'support',
                'category' => 'umrah',
                'url' => route('layanan-pendukung.index', ['category' => 'umrah']),
                'icon' => 'prayer',
            ],
            [
                'key' => 'other',
                'label' => __('dashboard.customer_cat_photo'),
                'type' => 'support',
                'category' => 'other',
                'url' => route('layanan-pendukung.index', ['category' => 'other']),
                'icon' => 'photo',
            ],
            [
                'key' => 'ziarah',
                'label' => __('dashboard.customer_cat_raudho'),
                'type' => 'support',
                'category' => 'ziarah',
                'url' => route('layanan-pendukung.index', ['category' => 'ziarah']),
                'icon' => 'raudho',
            ],
        ];
        $defaultStartsAt = now()->addDay()->setTime(9, 0)->format('Y-m-d\TH:i');
        $minStartsAt = now()->format('Y-m-d\TH:i');
    @endphp

    {{-- HERO + SEARCH (Traveloka-style: widget floats on hero image) --}}
    <section
        id="cari-layanan"
        class="relative left-1/2 right-1/2 -ml-[50vw] -mr-[50vw] w-screen scroll-mt-24 bg-baytgo-950"
        aria-label="{{ __('dashboard.customer_hero_sub') }}"
        x-data="{
            selected: @js($homeCategories[0]),
            startsAt: @js($defaultStartsAt),
            dateError: '',
            select(cat) {
                this.selected = cat;
                this.dateError = '';
            },
            search() {
                this.dateError = '';
                if (! this.selected) return;

                if (this.selected.type === 'layanan') {
                    const start = this.$refs.umrohDates?.querySelector('[name=start_date]')?.value || '';
                    const end = this.$refs.umrohDates?.querySelector('[name=end_date]')?.value || start;
                    if (! start) {
                        this.dateError = @js(__('welcome.landing_pick_dates_required_range'));
                        this.$refs.umrohDates?.querySelector('button[aria-haspopup=\'dialog\']')?.click();
                        return;
                    }
                    let url = this.selected.url;
                    url += (url.includes('?') ? '&' : '?') + 'start_date=' + encodeURIComponent(start) + '&end_date=' + encodeURIComponent(end);
                    window.location.href = url;
                    return;
                }

                if (! this.startsAt) {
                    this.dateError = @js(__('welcome.landing_pick_dates_required_slot'));
                    return;
                }
                let url = this.selected.url;
                url += (url.includes('?') ? '&' : '?') + 'starts_at=' + encodeURIComponent(this.startsAt);
                window.location.href = url;
            }
        }"
    >
        <div class="pointer-events-none absolute inset-0 overflow-hidden" aria-hidden="true">
            <img src="{{ $welcomeHeroBg }}" alt="" class="h-full w-full object-cover object-[center_35%]" loading="eager" decoding="async" />
            <div class="absolute inset-0 bg-gradient-to-b from-baytgo-950/75 via-baytgo-950/55 to-baytgo-950/85"></div>
        </div>

        <div class="relative w-full {{ $sectionPad }} pb-14 pt-10 sm:pb-16 sm:pt-14 lg:pb-20 lg:pt-16">
            @if ($homeHeroName)
                <p class="mb-2 text-sm font-medium text-white/80 sm:mb-3">{{ __('dashboard.customer_hero_intro') }} {{ $homeHeroName }}</p>
            @endif

            <h1 class="w-full text-[1.5rem] font-bold leading-[1.2] tracking-tight text-white sm:text-4xl lg:text-5xl">
                {{ __('welcome.landing_hero_lead') }}
                <span class="text-gold-muted">{{ __('welcome.landing_hero_accent') }}</span>
            </h1>
            <p class="mt-3 w-full text-sm leading-relaxed text-white/85 sm:mt-4 sm:text-base">{{ __('welcome.landing_hero_sub') }}</p>

            {{-- Category tabs: full-width 5-col, white icons --}}
            <div class="mt-8 w-full sm:mt-10">
                <div class="grid w-full grid-cols-5 gap-1 sm:gap-2 md:gap-3">
                    @foreach ($homeCategories as $cat)
                        <button
                            type="button"
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
                                @if ($cat['icon'] === 'umroh')
                                    <svg class="h-6 w-6 sm:h-7 sm:w-7" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 9.75a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z"/></svg>
                                @elseif ($cat['icon'] === 'mobility')
                                    <svg class="h-6 w-6 sm:h-7 sm:w-7" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0"/><circle cx="8" cy="18" r="2.25"/><path stroke-linecap="round" stroke-linejoin="round" d="M10.25 18H15a3 3 0 0 0 3-3v-2.25"/></svg>
                                @elseif ($cat['icon'] === 'prayer')
                                    <svg class="h-6 w-6 sm:h-7 sm:w-7" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v3m0 12v3M4.5 9.75h15M6 9.75V18a1.5 1.5 0 0 0 1.5 1.5h9A1.5 1.5 0 0 0 18 18V9.75M9 6.75h6"/></svg>
                                @elseif ($cat['icon'] === 'photo')
                                    <svg class="h-6 w-6 sm:h-7 sm:w-7" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 0 1 5.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 0 0-1.134-.175 2.31 2.31 0 0 1-1.64-1.055l-.822-1.316a2.192 2.192 0 0 0-1.736-1.039 48.774 48.774 0 0 0-5.232 0 2.192 2.192 0 0 0-1.736 1.039l-.821 1.316Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0Z"/></svg>
                                @else
                                    <svg class="h-6 w-6 sm:h-7 sm:w-7" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 12V10.332A48.36 48.36 0 0 0 12 9.75c-2.551 0-5.056.2-7.5.582V21"/></svg>
                                @endif
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

            {{-- White search bar: full width --}}
            <div class="mt-4 w-full rounded-2xl bg-white p-3 shadow-xl shadow-black/20 sm:mt-5 sm:rounded-3xl sm:p-4">
                <div class="flex w-full flex-col gap-3 sm:flex-row sm:items-end sm:gap-3">
                    <div class="min-w-0 w-full flex-1">
                        <div x-show="selected?.type === 'layanan'" x-ref="umrohDates" class="w-full">
                            <x-date-range-picker
                                :min-date="now()->toDateString()"
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
                                    min="{{ $minStartsAt }}"
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
                        <span class="sm:inline lg:inline">{{ __('welcome.landing_search_cta') }}</span>
                    </button>
                </div>
            </div>
        </div>
    </section>

    <div class="relative bg-slate-50">
    @if ($activeCampaigns->isNotEmpty())
        <section class="{{ $sectionPad }} pb-8 pt-8 sm:pb-10 sm:pt-10" id="customer-promo">
            <x-campaign-carousel :campaigns="$activeCampaigns" />
        </section>
    @endif

    {{-- MUTHOWIF POPULER --}}
    <section id="customer-recommend" class="{{ $sectionPad }} scroll-mt-24 pb-10 pt-8 sm:pb-12 sm:pt-10" aria-labelledby="customer-rec-heading">
        <div class="mb-5 flex flex-col gap-2 sm:mb-6 sm:flex-row sm:flex-wrap sm:items-end sm:justify-between sm:gap-3">
            <div class="min-w-0 flex-1 pe-2">
                <p class="text-[10px] font-bold uppercase tracking-[0.16em] text-baytgo/70">{{ __('welcome.landing_badge_popular') }}</p>
                <h2 id="customer-rec-heading" class="mt-1 text-lg font-bold text-baytgo sm:text-2xl">{{ __('dashboard.customer_popular_title') }}</h2>
                <p class="mt-1 line-clamp-2 text-xs text-slate-600 sm:text-sm">{{ __('welcome.landing_popular_sub') }}</p>
            </div>
            <a href="{{ route('layanan.index') }}" class="inline-flex shrink-0 items-center gap-1 self-start text-sm font-semibold text-gold-muted hover:text-baytgo">
                {{ __('dashboard.customer_popular_see_all') }}
                <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg>
            </a>
        </div>

        @if ($featuredMuthowifs->isEmpty())
            <p class="rounded-2xl border border-dashed border-slate-200 bg-white py-14 text-center text-sm text-slate-600">{{ __('welcome.popular_empty') }}</p>
        @else
            <div class="relative" x-data="{ scroll(dx) { const el = this.$refs.trackHome; if (el) el.scrollBy({ left: dx, behavior: 'smooth' }); } }">
                <button type="button" @click="scroll(-340)" class="absolute -left-1 top-[40%] z-10 hidden h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full border border-slate-200 bg-white text-baytgo shadow-lg transition hover:bg-slate-50 md:flex" aria-label="{{ __('welcome.carousel_prev') }}">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/></svg>
                </button>
                <button type="button" @click="scroll(340)" class="absolute -right-1 top-[40%] z-10 hidden h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full border border-slate-200 bg-white text-baytgo shadow-lg transition hover:bg-slate-50 md:flex" aria-label="{{ __('welcome.carousel_next') }}">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg>
                </button>
                <div class="-mx-1 flex gap-4 overflow-x-auto scroll-pl-4 px-1 pb-2 snap-x snap-mandatory [scrollbar-width:none] [&::-webkit-scrollbar]:hidden md:px-10" x-ref="trackHome" style="-webkit-overflow-scrolling: touch;">
                    @foreach ($featuredMuthowifs as $profile)
                        @php
                            $minPrice = (int) round((float) ($profile->services->min('daily_price') ?? 0));
                            $formatted = $minPrice > 0 ? 'Rp '.number_format($minPrice, 0, ',', '.') : '—';
                            $rating = $profile->booking_reviews_avg_rating ?? $profile->average_rating;
                            $ratingStr = $rating !== null ? number_format((float) $rating, 1) : '—';
                            $reviewCount = (int) ($profile->booking_reviews_count ?? 0);
                            $loc = method_exists($profile, 'workLocationLabel') ? $profile->workLocationLabel() : null;
                            $tags = collect($profile->services ?? [])->map(fn ($s) => $s->service_type?->label())->filter()->unique()->take(3)->values();
                        @endphp
                        <article class="w-[15rem] shrink-0 snap-start overflow-hidden rounded-2xl border border-slate-100 bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md sm:w-[16.5rem]">
                            <a href="{{ route('layanan.show', $profile) }}" class="block h-full focus:outline-none focus-visible:ring-2 focus-visible:ring-baytgo focus-visible:ring-offset-2">
                                <div class="relative aspect-[4/5] overflow-hidden bg-slate-100">
                                    <img src="{{ $profile->photoUrl() }}" alt="" class="h-full w-full object-cover object-top" loading="lazy" decoding="async" />
                                    <span class="absolute left-2.5 top-2.5 inline-flex items-center gap-1 rounded-full bg-white/95 px-2 py-0.5 text-[10px] font-bold text-emerald-700 shadow-sm">
                                        <svg class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" /></svg>
                                        {{ __('dashboard.customer_verified_badge') }}
                                    </span>
                                </div>
                                <div class="p-4">
                                    <h3 class="line-clamp-1 font-bold text-slate-900">{{ $profile->user->name ?? '—' }}</h3>
                                    <div class="mt-1.5 flex flex-wrap items-center gap-x-1.5 gap-y-0.5 text-xs text-slate-600">
                                        <span class="inline-flex items-center gap-0.5 font-semibold text-slate-800">
                                            <svg class="h-3.5 w-3.5 text-amber-500" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                            {{ $ratingStr }}
                                        </span>
                                        @if ($loc)
                                            <span class="text-slate-400">·</span>
                                            <span class="line-clamp-1">{{ $loc }}</span>
                                        @endif
                                    </div>
                                    @if ($tags->isNotEmpty())
                                        <div class="mt-2.5 flex flex-wrap gap-1.5">
                                            @foreach ($tags as $tag)
                                                <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-semibold text-emerald-800">{{ $tag }}</span>
                                            @endforeach
                                        </div>
                                    @endif
                                    <p class="mt-3 border-t border-slate-100 pt-3 text-xs text-slate-500">
                                        {{ __('welcome.landing_from_label') }}
                                        <span class="font-bold text-baytgo">{{ $formatted }}</span>
                                        <span class="text-slate-400">/{{ __('welcome.landing_per_day') }}</span>
                                    </p>
                                </div>
                            </a>
                        </article>
                    @endforeach
                </div>
            </div>
        @endif
    </section>

    @php
        $galleryList = ($galleryImages ?? collect())->values();
        $galleryGrid = $galleryList->take(8);
    @endphp
    @if ($galleryGrid->isNotEmpty())
        <section
            id="galeri-muthowif"
            class="{{ $sectionPad }} scroll-mt-24 border-t border-slate-100 bg-white py-10 sm:py-12"
            aria-labelledby="gallery-heading"
            x-data="{
                open: false,
                url: '',
                title: '',
                href: null,
                show(u, t, h) { this.url = u; this.title = t; this.href = h; this.open = true; }
            }"
        >
            <div class="mb-8 text-center sm:text-left">
                <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-baytgo/70">{{ __('welcome.landing_gallery_kicker') }}</p>
                <h2 id="gallery-heading" class="mt-2 text-xl font-bold text-baytgo sm:text-2xl">{{ __('welcome.landing_gallery_title') }}</h2>
            </div>

            <div class="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
                @foreach ($galleryGrid as $image)
                    @php
                        $profile = $image->portfolio?->muthowifProfile;
                        $profileHref = $profile ? route('layanan.show', $profile) : null;
                        $caption = $image->portfolio?->title
                            ?: ($profile?->user?->name ?? __('welcome.landing_gallery_title'));
                    @endphp
                    <button
                        type="button"
                        @click="show(@js($image->publicUrl()), @js($caption), @js($profileHref))"
                        class="group relative aspect-[4/3] overflow-hidden rounded-2xl border border-slate-100 bg-slate-100 shadow-sm"
                    >
                        <img src="{{ $image->publicUrl() }}" alt="{{ $caption }}" class="h-full w-full object-cover transition duration-500 group-hover:scale-105" loading="lazy" decoding="async" />
                        <span class="absolute inset-0 bg-gradient-to-t from-baytgo-950/50 via-transparent to-transparent opacity-0 transition group-hover:opacity-100"></span>
                    </button>
                @endforeach
            </div>

            <div
                x-show="open"
                x-cloak
                class="fixed inset-0 z-[90] flex items-center justify-center bg-black/80 p-4"
                @keydown.escape.window="open = false"
            >
                <button type="button" class="absolute inset-0 cursor-default" @click="open = false" aria-label="Close"></button>
                <div class="relative z-10 w-full max-w-3xl overflow-hidden rounded-2xl bg-slate-950 shadow-2xl ring-1 ring-white/10">
                    <img :src="url" :alt="title" class="max-h-[70vh] w-full object-contain bg-black">
                    <div class="flex flex-wrap items-center justify-between gap-3 border-t border-white/10 px-4 py-3">
                        <p class="min-w-0 flex-1 truncate text-sm font-semibold text-white" x-text="title"></p>
                        <div class="flex items-center gap-2">
                            <a
                                x-show="href"
                                x-cloak
                                :href="href"
                                class="rounded-xl bg-gold px-3 py-2 text-xs font-bold text-baytgo-950 transition hover:bg-gold-muted"
                            >{{ __('welcome.landing_gallery_view_profile') }}</a>
                            <button type="button" @click="open = false" class="rounded-xl border border-white/20 px-3 py-2 text-xs font-semibold text-white hover:bg-white/10">{{ __('welcome.landing_gallery_close') }}</button>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    @endif

    @if ($showLandingChrome)
        {{-- CARA KERJA --}}
        <section id="cara-kerja" class="{{ $sectionPad }} scroll-mt-24 border-t border-slate-100 py-10 sm:py-12" aria-labelledby="welcome-work-heading">
            <div class="mb-8 text-center">
                <h2 id="welcome-work-heading" class="text-xl font-bold text-baytgo sm:text-2xl">{{ __('welcome.work_title') }}</h2>
                <p class="mt-2 text-sm text-slate-600">{{ __('welcome.work_sub') }}</p>
            </div>
            <div class="grid gap-4 sm:grid-cols-3 sm:gap-6">
                @foreach (__('welcome.work_steps') as $i => $step)
                    <article class="relative rounded-2xl border border-slate-100 bg-white p-5 text-center shadow-sm sm:p-6">
                        <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-baytgo text-sm font-bold text-white">{{ $i + 1 }}</span>
                        <h3 class="mt-4 text-base font-bold text-slate-900">{{ $step['title'] }}</h3>
                        <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ $step['desc'] }}</p>
                    </article>
                @endforeach
            </div>
        </section>

        {{-- TRUST BAND --}}
        <section class="relative left-1/2 right-1/2 -ml-[50vw] -mr-[50vw] w-screen bg-baytgo-950 py-12 sm:py-14">
            <div class="{{ $sectionPad }} mx-auto max-w-6xl">
                <div class="grid gap-8 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.1fr)] lg:items-center lg:gap-10">
                    <div>
                        <h2 class="text-xl font-bold text-white sm:text-2xl">{{ __('welcome.landing_trust_title') }}</h2>
                        <dl class="mt-6 flex flex-wrap gap-8">
                            <div>
                                <dt class="text-3xl font-bold tabular-nums text-gold-muted">10.000+</dt>
                                <dd class="mt-1 text-sm text-white/75">{{ __('welcome.landing_stat_jamaah') }}</dd>
                            </div>
                            <div>
                                <dt class="text-3xl font-bold tabular-nums text-gold-muted">4.9</dt>
                                <dd class="mt-1 text-sm text-white/75">{{ __('welcome.landing_stat_rating') }}</dd>
                            </div>
                        </dl>
                    </div>
                    <blockquote class="rounded-2xl border border-white/10 bg-white/5 p-6 backdrop-blur-sm">
                        <p class="text-sm leading-relaxed text-white/90 sm:text-base">&ldquo;{{ __('welcome.landing_trust_quote') }}&rdquo;</p>
                        <footer class="mt-4 text-sm font-semibold text-gold-muted">&mdash; {{ __('welcome.landing_trust_quote_by') }}</footer>
                    </blockquote>
                </div>
            </div>
        </section>
    @endif

    {{-- ARTIKEL --}}
    <section id="customer-articles" class="{{ $sectionPad }} scroll-mt-24 border-t border-slate-100 py-10 sm:py-12" aria-labelledby="customer-articles-heading">
        <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
            <h2 id="customer-articles-heading" class="text-xl font-bold text-baytgo sm:text-2xl">{{ __('dashboard.customer_articles_title') }}</h2>
            <a href="{{ route('articles.index') }}" class="inline-flex items-center gap-1 text-sm font-semibold text-gold-muted hover:text-baytgo">
                {{ __('dashboard.customer_articles_see_all') }}
                <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg>
            </a>
        </div>

        @if ($latestArticles->isEmpty())
            @if (is_array($homeGuideCards) && $homeGuideCards !== [])
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    @foreach ($homeGuideCards as $card)
                        <a href="{{ route('welcome') }}#{{ $card['fragment'] ?? '' }}" class="group overflow-hidden rounded-2xl border border-slate-100 bg-white shadow-sm transition hover:shadow-md">
                            <div class="p-5">
                                <span class="inline-flex rounded-lg bg-gold-light/35 px-2 py-1 text-[10px] font-bold uppercase tracking-wider text-baytgo ring-1 ring-gold/25">{{ $card['read'] ?? '' }}</span>
                                <p class="mt-3 font-bold leading-snug text-baytgo group-hover:text-baytgo-800">{{ $card['title'] ?? '' }}</p>
                                <p class="mt-2 text-xs leading-relaxed text-slate-600">{{ $card['desc'] ?? '' }}</p>
                            </div>
                        </a>
                    @endforeach
                </div>
            @else
                <p class="rounded-2xl border border-dashed border-slate-200 bg-white py-10 text-center text-sm text-slate-600">{{ __('welcome.popular_empty') }}</p>
            @endif
        @else
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($latestArticles->take(4) as $article)
                    @php
                        $body = $article->localized('body');
                        $thumbnail = null;
                        if (preg_match('/<img[^>]+src=["\']([^"\']+)["\']/', $body, $m)) {
                            $thumbnail = $m[1];
                        }
                    @endphp
                    <article class="overflow-hidden rounded-2xl border border-slate-100 bg-white shadow-sm transition hover:shadow-md">
                        <a href="{{ route('articles.show', ['slug' => $article->slug]) }}" class="block">
                            <div class="aspect-[16/10] overflow-hidden bg-slate-100">
                                @if ($thumbnail)
                                    <img src="{{ $thumbnail }}" alt="" class="h-full w-full object-cover" loading="lazy" />
                                @endif
                            </div>
                            <div class="p-4">
                                <p class="line-clamp-2 text-sm font-bold text-slate-900">{{ $article->localized('title') }}</p>
                                <p class="mt-2 line-clamp-2 text-xs text-slate-600">{{ $article->localized('excerpt') }}</p>
                            </div>
                        </a>
                    </article>
                @endforeach
            </div>
        @endif
    </section>

    @if ($showLandingChrome)
        {{-- FAQ --}}
        <section id="faq" class="{{ $sectionPad }} scroll-mt-24 border-t border-slate-100 py-10 sm:py-12" aria-labelledby="welcome-faq-heading">
            <h2 id="welcome-faq-heading" class="mb-6 text-center text-xl font-bold text-baytgo sm:text-2xl">{{ __('welcome.faq_title') }}</h2>
            <div class="mx-auto max-w-3xl space-y-3" x-data="{ open: 0 }">
                @foreach (__('welcome.faq_items') as $i => $item)
                    <div class="overflow-hidden rounded-2xl border border-slate-100 bg-white shadow-sm">
                        <button type="button" class="flex w-full items-center justify-between gap-3 px-5 py-4 text-left" @click="open = open === {{ $i }} ? null : {{ $i }}">
                            <span class="text-sm font-semibold text-slate-900">{{ $item['q'] }}</span>
                            <svg class="h-4 w-4 shrink-0 text-slate-400 transition" :class="open === {{ $i }} && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                        </button>
                        <div x-show="open === {{ $i }}" x-cloak class="border-t border-slate-100 px-5 pb-4 pt-3 text-sm leading-relaxed text-slate-600">{{ $item['a'] }}</div>
                    </div>
                @endforeach
            </div>
        </section>

        {{-- FINAL CTA --}}
        <section id="tentang" class="{{ $sectionPad }} scroll-mt-24 pb-8 pt-2 sm:pb-10">
            <div class="relative overflow-hidden rounded-3xl bg-baytgo px-6 py-10 text-white shadow-xl sm:px-10 sm:py-12">
                <div class="pointer-events-none absolute -right-16 -top-16 h-64 w-64 rounded-full bg-gold/15 blur-3xl" aria-hidden="true"></div>
                <div class="relative flex flex-col items-center gap-8 text-center lg:flex-row lg:justify-between lg:text-left">
                    <div class="max-w-xl">
                        <h2 class="text-2xl font-bold leading-snug sm:text-3xl">{{ __('welcome.landing_cta_title') }}</h2>
                        <p class="mt-3 text-sm text-white/85 sm:text-base">{{ __('welcome.landing_cta_sub') }}</p>
                        <dl class="mt-6 flex flex-wrap justify-center gap-6 lg:justify-start">
                            <div>
                                <dt class="text-2xl font-bold tabular-nums">500+</dt>
                                <dd class="text-xs text-white/75">{{ __('welcome.landing_stat_guides') }}</dd>
                            </div>
                            <div>
                                <dt class="text-2xl font-bold tabular-nums">10.000+</dt>
                                <dd class="text-xs text-white/75">{{ __('welcome.landing_stat_jamaah') }}</dd>
                            </div>
                            <div>
                                <dt class="text-2xl font-bold tabular-nums">4.9</dt>
                                <dd class="text-xs text-white/75">{{ __('welcome.landing_stat_rating') }}</dd>
                            </div>
                        </dl>
                    </div>
                    <a href="{{ route('layanan.index') }}" class="inline-flex shrink-0 items-center gap-2 rounded-xl bg-gold px-7 py-3.5 text-sm font-bold text-baytgo-950 shadow-lg transition hover:bg-gold-muted">
                        {{ __('welcome.landing_hero_cta') }}
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5 15.75 12l-7.5 7.5"/></svg>
                    </a>
                </div>
            </div>
        </section>
    @endif
    </div>{{-- /light canvas --}}
</div>
