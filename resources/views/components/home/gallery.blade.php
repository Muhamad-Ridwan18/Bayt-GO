@props(['page'])

@if ($page->hasGallery())
    <section
        id="galeri-muthowif"
        class="home-section-pad scroll-mt-24 border-t border-slate-100 bg-gradient-to-b from-white via-slate-50/80 to-white py-10 sm:py-14"
        aria-labelledby="gallery-heading"
        x-data="homeGallery(@js($page->galleryItems))"
    >
        <x-home.section-heading
            :kicker="__('welcome.landing_gallery_kicker')"
            :title="__('welcome.landing_gallery_title')"
            title-id="gallery-heading"
        />

        <div class="mt-2 space-y-3 sm:space-y-4" x-show="!reducedMotion">
            <div class="marquee-wrap -mx-4 sm:mx-0">
                <div class="marquee-track gap-3 px-4 sm:gap-4 sm:px-0">
                    <template x-for="(item, i) in row1Loop" :key="'r1-'+i">
                        <button
                            type="button"
                            @click="show(item.url, item.caption, item.href)"
                            class="group relative h-36 w-52 shrink-0 overflow-hidden rounded-2xl border border-slate-100 bg-slate-100 shadow-sm sm:h-44 sm:w-64"
                        >
                            <img :src="item.url" :alt="item.caption" class="h-full w-full object-cover transition duration-500 group-hover:scale-105" loading="lazy" decoding="async" />
                            <span class="pointer inset-0 bg-gradient-to-t from-baytgo-950/45 via-transparent to-transparent opacity-0 transition group-hover:opacity-100"></span>
                        </button>
                    </template>
                </div>
            </div>

            <div class="marquee-wrap -mx-4 sm:mx-0" x-show="row2.length > 0">
                <div class="marquee-track-reverse gap-3 px-4 sm:gap-4 sm:px-0">
                    <template x-for="(item, i) in row2Loop" :key="'r2-'+i">
                        <button
                            type="button"
                            @click="show(item.url, item.caption, item.href)"
                            class="group relative h-36 w-52 shrink-0 overflow-hidden rounded-2xl border border-slate-100 bg-slate-100 shadow-sm sm:h-44 sm:w-64"
                        >
                            <img :src="item.url" :alt="item.caption" class="h-full w-full object-cover transition duration-500 group-hover:scale-105" loading="lazy" decoding="async" />
                            <span class="absolute inset-0 bg-gradient-to-t from-baytgo-950/45 via-transparent to-transparent opacity-0 transition group-hover:opacity-100"></span>
                        </button>
                    </template>
                </div>
            </div>
        </div>

        <div class="mt-2 grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4" x-show="reducedMotion" x-cloak>
            <template x-for="(item, i) in items" :key="'static-'+i">
                <button
                    type="button"
                    @click="show(item.url, item.caption, item.href)"
                    class="group relative aspect-[4/3] overflow-hidden rounded-2xl border border-slate-100 bg-slate-100 shadow-sm"
                >
                    <img :src="item.url" :alt="item.caption" class="h-full w-full object-cover transition duration-500 group-hover:scale-105" loading="lazy" decoding="async" />
                </button>
            </template>
        </div>

        <div
            x-show="open"
            x-cloak
            class="fixed inset-0 z-[90] flex items-center justify-center bg-black/80 p-4"
            @keydown.escape.window="close()"
        >
            <button type="button" class="absolute inset-0 cursor-default" @click="close()" aria-label="Close"></button>
            <div class="relative z-10 w-full max-w-3xl overflow-hidden rounded-2xl bg-slate-950 shadow-2xl ring-1 ring-white/10">
                <img :src="url" :alt="title" class="max-h-[70vh] w-full bg-black object-contain">
                <div class="flex flex-wrap items-center justify-between gap-3 border-t border-white/10 px-4 py-3">
                    <p class="min-w-0 flex-1 truncate text-sm font-semibold text-white" x-text="title"></p>
                    <div class="flex items-center gap-2">
                        <a
                            x-show="href"
                            x-cloak
                            :href="href"
                            class="rounded-xl bg-gold px-3 py-2 text-xs font-bold text-baytgo-950 transition hover:bg-gold-muted"
                        >{{ __('welcome.landing_gallery_view_profile') }}</a>
                        <button type="button" @click="close()" class="rounded-xl border border-white/20 px-3 py-2 text-xs font-semibold text-white hover:bg-white/10">{{ __('welcome.landing_gallery_close') }}</button>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endif
