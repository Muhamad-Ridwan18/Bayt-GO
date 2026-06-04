{{-- Mobile: hero → cari tanggal → ringkasan aktivitas → rekomendasi. Desktop: kiri konten, kanan ringkasan. --}}
<div class="flex flex-col gap-6 lg:grid lg:grid-cols-12 lg:items-start lg:gap-8">
    <div class="contents lg:col-span-8 lg:flex lg:flex-col lg:gap-6">
        {{-- Hero --}}
        <section class="order-1 relative overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-md ring-1 ring-slate-100/80 lg:order-none">
            <div class="pointer-events-none absolute inset-0 z-0" aria-hidden="true">
                <img src="{{ $welcomeHeroBg }}" alt="" class="h-full w-full min-h-[10rem] object-cover object-[78%_32%] sm:min-h-[12rem]" loading="eager" decoding="async" />
            </div>
            <div class="pointer-events-none absolute inset-0 z-[1] bg-gradient-to-r from-welcomeCanvas from-[30%] via-welcomeCanvas/95 via-[52%] to-welcomeCanvas/15 sm:from-[35%] sm:via-[58%]" aria-hidden="true"></div>
            <div class="relative z-10 p-5 sm:p-7 lg:max-w-xl">
                <p class="inline-flex items-center gap-2 rounded-full border border-emerald-200/70 bg-emerald-50 px-3 py-1 text-[10px] font-bold uppercase tracking-wider text-emerald-900">
                    <svg class="h-3.5 w-3.5 text-emerald-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" /></svg>
                    {{ __('dashboard.customer_hero_kicker') }}
                </p>
                <p class="mt-4 text-xl font-bold leading-snug text-slate-900 sm:text-2xl">
                    {{ __('dashboard.customer_hero_intro') }}
                    <span class="text-baytgo">{{ $user->name }}</span>
                    <span aria-hidden="true">👋</span>
                </p>
                <p class="mt-2 text-sm leading-relaxed text-slate-600 sm:text-base">{{ __('dashboard.customer_hero_sub') }}</p>
            </div>
            <div class="relative z-10 grid grid-cols-2 gap-2.5 border-t border-slate-100/80 bg-white/90 p-4 backdrop-blur-sm sm:grid-cols-2 sm:gap-3 sm:p-5 lg:grid-cols-4">
                <div class="rounded-xl border border-slate-100 bg-white p-3 shadow-sm">
                    <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-emerald-50 text-emerald-700" aria-hidden="true">
                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M12.516 2.17a.75.75 0 01.466.747l-.286 2.051a.75.75 0 01-.548.582 11.319 11.319 0 00-4.702 2.271.75.75 0 01-.826-.033l-1.64-1.117a.75.75 0 00-.987.052l-1.378 1.378a.75.75 0 00.052.987l1.117 1.64a.75.75 0 01.033.826 11.32 11.32 0 00-2.27 4.702.75.75 0 01-.582.548l-2.051.286a.75.75 0 00-.747.466V12a.75.75 0 00.747.466l2.051.286a.75.75 0 01.582.548 11.319 11.319 0 002.27 4.702.75.75 0 01-.033.826l-1.117 1.64a.75.75 0 00-.052.987l1.378 1.378a.75.75 0 00.987-.052l1.64-1.117a.75.75 0 01.826-.033 11.317 11.317 0 004.702-2.27.75.75 0 01.548-.582l2.051-.286a.75.75 0 00.747-.466V12a.75.75 0 00-.747-.466l-2.051-.286a.75.75 0 01-.548-.582 11.32 11.32 0 00-2.27-4.702.75.75 0 01.033-.826l1.117-1.64a.75.75 0 00.052-.987L18.72 9.53a.75.75 0 00-.987-.052l-1.64 1.117a.75.75 0 01-.826.033 11.317 11.317 0 00-4.702-2.27.75.75 0 01-.582-.548l-.286-2.051A.75.75 0 0012.516 2.17z" clip-rule="evenodd" /></svg>
                    </span>
                    <p class="mt-2 text-xs font-bold text-slate-900">{{ __('dashboard.customer_feature_verified_title') }}</p>
                    <p class="mt-0.5 text-[10px] leading-snug text-slate-500 sm:text-[11px]">{{ __('dashboard.customer_feature_verified_desc') }}</p>
                </div>
                <div class="rounded-xl border border-slate-100 bg-white p-3 shadow-sm">
                    <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-sky-50 text-sky-700" aria-hidden="true">
                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5a2.25 2.25 0 002.25-2.25m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5a2.25 2.25 0 012.25 2.25v7.5" /></svg>
                    </span>
                    <p class="mt-2 text-xs font-bold text-slate-900">{{ __('dashboard.customer_feature_schedule_title') }}</p>
                    <p class="mt-0.5 text-[10px] leading-snug text-slate-500 sm:text-[11px]">{{ __('dashboard.customer_feature_schedule_desc') }}</p>
                </div>
                <div class="rounded-xl border border-slate-100 bg-white p-3 shadow-sm">
                    <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-violet-50 text-violet-700" aria-hidden="true">
                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a48.667 48.667 0 00-7.5 0m12 0v1.5a3 3 0 01-3 3h-12a3 3 0 01-3-3v-1.5m12 0V9M6 18.72V9m0 0a48.667 48.667 0 017.5 0M6 9V6.75A2.25 2.25 0 018.25 4.5h7.5A2.25 2.25 0 0118 6.75V9" /></svg>
                    </span>
                    <p class="mt-2 text-xs font-bold text-slate-900">{{ __('dashboard.customer_feature_group_title') }}</p>
                    <p class="mt-0.5 text-[10px] leading-snug text-slate-500 sm:text-[11px]">{{ __('dashboard.customer_feature_group_desc') }}</p>
                </div>
                <div class="rounded-xl border border-slate-100 bg-white p-3 shadow-sm">
                    <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-amber-50 text-amber-800" aria-hidden="true">
                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.72 1.072c-.442.663-1.32.902-2.027.55a12.284 12.284 0 01-7.4-7.4c-.352-.707-.113-1.585.55-2.027l1.072-.72c.363-.271.527-.732.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 6.75z" /></svg>
                    </span>
                    <p class="mt-2 text-xs font-bold text-slate-900">{{ __('dashboard.customer_feature_support_title') }}</p>
                    <p class="mt-0.5 text-[10px] leading-snug text-slate-500 sm:text-[11px]">{{ __('dashboard.customer_feature_support_desc') }}</p>
                </div>
            </div>
        </section>

        {{-- Pencarian tanggal --}}
        <div class="order-2 lg:order-none" id="customer-search">
            @include('partials.dashboard-customer-search')
        </div>

        {{-- Rekomendasi --}}
        <section class="order-4 lg:order-none" aria-labelledby="customer-rec-heading" id="customer-recommend">
            <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
                <div class="flex items-center gap-2">
                    <svg class="h-5 w-5 text-amber-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M10.868 2.884c-.321-.772-1.415-.772-1.736 0l-1.83 4.401-4.753.381c-.833.067-1.171 1.107-.536 1.651l3.62 3.102-1.106 4.637c-.194.813.691 1.456 1.405 1.02L10 15.591l4.069 2.485c.713.436 1.598-.207 1.404-1.02l-1.106-4.637 3.62-3.102c.635-.544.297-1.584-.536-1.65l-4.752-.382-1.83-4.401z" clip-rule="evenodd" /></svg>
                    <h2 id="customer-rec-heading" class="text-base font-bold text-slate-900 sm:text-lg">{{ __('dashboard.customer_recommend_title') }}</h2>
                </div>
                <a href="{{ route('layanan.index') }}" class="text-sm font-semibold text-baytgo hover:text-baytgo-800">{{ __('welcome.popular_see_all') }} →</a>
            </div>

            @if ($featuredMuthowifs->isEmpty())
                <p class="rounded-2xl border border-dashed border-slate-200 bg-white py-12 text-center text-sm text-slate-600">{{ __('welcome.popular_empty') }}</p>
            @else
                <div class="relative" x-data="{ scroll(dx) { const el = this.$refs.trackC; if (el) el.scrollBy({ left: dx, behavior: 'smooth' }); } }">
                    <div class="-mx-1 flex gap-3 overflow-x-auto scroll-pl-4 px-1 pb-2 snap-x snap-mandatory" x-ref="trackC" style="-webkit-overflow-scrolling: touch;">
                        @foreach ($featuredMuthowifs as $profile)
                            @php
                                $minPrice = (int) round((float) ($profile->services->min('daily_price') ?? 0));
                                $formatted = $minPrice > 0 ? 'Rp '.number_format($minPrice, 0, ',', '.') : '—';
                                $rating = $profile->booking_reviews_avg_rating;
                                $ratingStr = $rating !== null ? number_format((float) $rating, 1, ',', '') : '—';
                                $languages = array_slice($profile->languagesForDisplay(), 0, 3);
                                $langsLine = $languages !== [] ? implode(', ', $languages) : null;
                            @endphp
                            <article class="w-[11.5rem] shrink-0 snap-start overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm sm:w-[12.5rem]">
                                <a href="{{ route('layanan.show', $profile) }}" class="block focus:outline-none focus-visible:ring-2 focus-visible:ring-baytgo">
                                    <div class="relative h-28 overflow-hidden bg-slate-100">
                                        <img src="{{ route('layanan.photo', $profile) }}" alt="" class="h-full w-full object-cover object-[50%_15%]" loading="lazy" />
                                        <span class="absolute right-2 top-2 inline-flex items-center gap-0.5 rounded-full bg-white/95 px-1.5 py-0.5 text-[10px] font-bold shadow-sm ring-1 ring-amber-200/60">
                                            <svg class="h-3 w-3 text-amber-500" viewBox="0 0 20 20" fill="currentColor"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                            {{ $ratingStr }}
                                        </span>
                                    </div>
                                    <div class="p-3">
                                        <p class="truncate font-bold text-slate-900">{{ $profile->user->name ?? '—' }}</p>
                                        @if ($langsLine)
                                            <p class="mt-0.5 truncate text-[10px] text-slate-500">{{ $langsLine }}</p>
                                        @endif
                                        <p class="mt-2 text-xs font-semibold text-baytgo">{{ __('welcome.popular_from', ['amount' => $formatted]) }}</p>
                                        <span class="mt-2 block w-full rounded-lg border border-slate-200 py-1.5 text-center text-[10px] font-semibold text-slate-700">{{ __('dashboard.customer_view_profile') }}</span>
                                    </div>
                                </a>
                            </article>
                        @endforeach
                    </div>
                    <div class="mt-2 flex justify-center gap-2 sm:hidden">
                        <button type="button" class="rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-semibold text-baytgo shadow-sm" @click="scroll(-220)">←</button>
                        <button type="button" class="rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-semibold text-baytgo shadow-sm" @click="scroll(220)">→</button>
                    </div>
                </div>
            @endif
        </section>
    </div>

    {{-- Ringkasan aktivitas --}}
    <aside class="contents lg:col-span-4 lg:block lg:sticky lg:top-24">
        <div class="order-3 overflow-hidden rounded-3xl bg-gradient-to-br from-baytgo to-baytgo-800 p-5 text-white shadow-lg ring-1 ring-baytgo/20 sm:p-6 lg:order-none">
            <div class="flex items-start justify-between gap-2">
                <div>
                    <p class="text-sm font-bold text-white">{{ __('dashboard.customer_status_title') }}</p>
                    <p class="mt-0.5 text-xs text-white/75">{{ __('dashboard.customer_status_sub') }}</p>
                </div>
                <span class="shrink-0 rounded-full bg-white/15 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wide text-white/90 ring-1 ring-white/20">{{ __('dashboard.customer_hero_kicker') }}</span>
            </div>
            <div class="mt-5 grid grid-cols-2 gap-3">
                <a href="{{ route('bookings.index') }}" class="rounded-2xl bg-white/10 p-3 ring-1 ring-white/15 transition hover:bg-white/15">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-white/15" aria-hidden="true">
                        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5a2.25 2.25 0 002.25-2.25m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5a2.25 2.25 0 012.25 2.25v7.5" /></svg>
                    </span>
                    <p class="mt-2 text-xl font-bold tabular-nums">{{ $activeBookingCount }}</p>
                    <p class="text-[10px] font-medium leading-tight text-white/80">{{ __('dashboard.customer_stat_active') }}</p>
                </a>
                @if (Route::has('support.index'))
                    <a href="{{ route('support.index') }}" class="rounded-2xl bg-white/10 p-3 ring-1 ring-white/15 transition hover:bg-white/15">
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-white/15" aria-hidden="true">
                            <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9 5.25h.008v.008H12v-.008z" /></svg>
                        </span>
                        <p class="mt-2 text-xl font-bold tabular-nums">{{ $supportOpenCount }}</p>
                        <p class="text-[10px] font-medium leading-tight text-white/80">{{ __('dashboard.customer_stat_support') }}</p>
                    </a>
                @else
                    <div class="rounded-2xl bg-white/10 p-3 ring-1 ring-white/15">
                        <p class="mt-6 text-xl font-bold tabular-nums">{{ $supportOpenCount }}</p>
                        <p class="text-[10px] font-medium text-white/80">{{ __('dashboard.customer_stat_support') }}</p>
                    </div>
                @endif
                <a href="{{ route('bookings.index') }}" class="rounded-2xl bg-white/10 p-3 ring-1 ring-white/15 transition hover:bg-white/15">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-white/15" aria-hidden="true">
                        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </span>
                    <p class="mt-2 text-xl font-bold tabular-nums">{{ $upcomingTripCount }}</p>
                    <p class="text-[10px] font-medium leading-tight text-white/80">{{ __('dashboard.customer_stat_upcoming') }}</p>
                </a>
                <div class="rounded-2xl bg-white/10 p-3 ring-1 ring-white/15">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-white/15" aria-hidden="true">
                        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.5c0 1.036.84 1.875 1.875 1.875H16.5a1.875 1.875 0 001.875-1.875V6.75A1.875 1.875 0 0016.5 4.875h-9A1.875 1.875 0 005.625 6.75v9.75z" /></svg>
                    </span>
                    <p class="mt-2 text-xl font-bold tabular-nums">{{ $reviewsGivenCount }}</p>
                    <p class="text-[10px] font-medium leading-tight text-white/80">{{ __('dashboard.customer_stat_reviews') }}</p>
                </div>
            </div>
            <a href="{{ route('profile.edit') }}" class="mt-5 flex items-center justify-center gap-1 rounded-xl bg-white/15 py-2.5 text-sm font-semibold text-white ring-1 ring-white/20 transition hover:bg-white/25">
                {{ __('dashboard.customer_status_account_cta') }}
                <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M3 10a.75.75 0 01.75-.75h10.638L10.23 5.29a.75.75 0 111.04-1.08l5.5 5.25a.75.75 0 010 1.08l-5.5 5.25a.75.75 0 11-1.04-1.08l4.158-3.96H3.75A.75.75 0 013 10z" clip-rule="evenodd" /></svg>
            </a>
        </div>
    </aside>
</div>
