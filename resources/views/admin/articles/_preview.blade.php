<aside class="space-y-4 xl:sticky xl:top-6 xl:max-h-[calc(100vh-5rem)] xl:overflow-y-auto xl:self-start" aria-label="{{ __('admin.articles.preview_aria') }}">
    <div class="flex flex-wrap items-start justify-between gap-3 rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
        <div>
            <p class="text-sm font-bold text-slate-900">{{ __('admin.articles.preview_title') }}</p>
            <p class="mt-0.5 text-xs text-slate-500">{{ __('admin.articles.preview_sub') }}</p>
        </div>
        <div class="inline-flex rounded-xl border border-slate-200 bg-slate-50 p-0.5" role="group" aria-label="{{ __('admin.articles.preview_devices_aria') }}">
            <button
                type="button"
                class="rounded-lg p-2 transition"
                :class="previewDevice === 'desktop' ? 'bg-white text-baytgo shadow-sm' : 'text-slate-500 hover:text-slate-800'"
                @click="previewDevice = 'desktop'"
                :title="@js(__('admin.articles.device_desktop'))"
            >
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 6a2 2 0 012-2h12a2 2 0 012 2v8a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM8 20h8M12 16v4" /></svg>
            </button>
            <button
                type="button"
                class="rounded-lg p-2 transition"
                :class="previewDevice === 'tablet' ? 'bg-white text-baytgo shadow-sm' : 'text-slate-500 hover:text-slate-800'"
                @click="previewDevice = 'tablet'"
                :title="@js(__('admin.articles.device_tablet'))"
            >
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 4h12a2 2 0 012 2v13a2 2 0 01-2 2H6a2 2 0 01-2-2V6a2 2 0 012-2zm6 16.5h.01" /></svg>
            </button>
            <button
                type="button"
                class="rounded-lg p-2 transition"
                :class="previewDevice === 'mobile' ? 'bg-white text-baytgo shadow-sm' : 'text-slate-500 hover:text-slate-800'"
                @click="previewDevice = 'mobile'"
                :title="@js(__('admin.articles.device_mobile'))"
            >
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 3h8a2 2 0 012 2v14a2 2 0 01-2 2H8a2 2 0 01-2-2V5a2 2 0 012-2zm4 16.5h.01" /></svg>
            </button>
        </div>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-gradient-to-b from-slate-50 to-white p-4 shadow-inner">
        <div
            class="mx-auto w-full overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-lg shadow-slate-900/5 transition-[max-width] duration-200"
            :class="previewFrameClass()"
        >
            <div class="border-b border-slate-100 bg-slate-50/90 px-4 py-3">
                <span class="inline-flex rounded-full bg-baytgo/10 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wide text-baytgo" x-text="activeBlock().category || '—'"></span>
                <h2 class="mt-4 text-xl font-bold leading-snug tracking-tight text-slate-900" x-text="activeBlock().title || labels.previewTitleFallback"></h2>
                <p class="mt-2 text-sm leading-relaxed text-slate-600" x-text="activeBlock().excerpt"></p>
                <div class="mt-4 flex flex-wrap items-center gap-x-3 gap-y-1 border-t border-slate-100 pt-3 text-xs text-slate-600">
                    <span
                        class="font-medium text-slate-800"
                        x-show="activeBlock().author"
                        x-text="authorLine(activeBlock().author)"
                    ></span>
                    <span class="hidden h-3 w-px bg-slate-200 sm:inline" x-show="activeBlock().author" aria-hidden="true"></span>
                    <time class="text-slate-600" x-text="formatPublished()"></time>
                    <span class="hidden h-3 w-px bg-slate-200 sm:inline" aria-hidden="true"></span>
                    <span class="text-slate-600" x-text="readingLabel(readingMinutes(activeBlock().bodyHtml))"></span>
                </div>
                <template x-if="firstImageSrc(activeBlock().bodyHtml)">
                    <div class="mt-4 overflow-hidden rounded-xl border border-slate-100">
                        <img :src="firstImageSrc(activeBlock().bodyHtml)" alt="" class="max-h-56 w-full object-cover" />
                    </div>
                </template>
                <template x-if="! firstImageSrc(activeBlock().bodyHtml)">
                    <div class="mt-4 flex aspect-[21/9] max-h-36 items-center justify-center rounded-xl border border-dashed border-slate-200 bg-gradient-to-br from-slate-100 to-slate-50 text-center text-[11px] text-slate-500">
                        {{ __('admin.articles.preview_no_image') }}
                    </div>
                </template>
            </div>
            <div
                class="article-prose max-w-none px-4 py-8 text-base sm:px-6"
                :class="{ 'text-right': activeLocale === 'ar' }"
                :dir="activeLocale === 'ar' ? 'rtl' : 'ltr'"
                x-html="activeBlock().bodyHtml"
            ></div>
        </div>
    </div>
</aside>
