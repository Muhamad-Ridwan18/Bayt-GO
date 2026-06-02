{{-- Membutuhkan Alpine: docModalOpen, docTitle, docPreviewUrl, docKind, closeDocPreview() --}}
<div
    x-show="docModalOpen"
    x-cloak
    class="fixed inset-0 z-[100] flex items-center justify-center p-4 sm:p-6"
    role="dialog"
    aria-modal="true"
    :aria-label="docTitle"
>
    <div class="absolute inset-0 bg-slate-900/70 backdrop-blur-[2px]" @click="closeDocPreview()"></div>
    <div class="relative z-10 flex max-h-[92vh] w-full max-w-4xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-slate-200/90" @click.stop>
        <div class="flex shrink-0 items-center justify-between gap-3 border-b border-slate-100 px-4 py-3 sm:px-5">
            <h4 class="min-w-0 truncate text-sm font-bold text-slate-900" x-text="docTitle"></h4>
            <button
                type="button"
                class="shrink-0 rounded-lg p-1.5 text-slate-500 transition hover:bg-slate-100 hover:text-slate-800"
                @click="closeDocPreview()"
                aria-label="{{ __('bookings.show.doc_modal_close') }}"
            >
                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z" /></svg>
            </button>
        </div>
        <div class="min-h-0 flex-1 overflow-auto bg-slate-50/80 p-4 sm:p-5">
            <img
                x-show="docKind === 'image'"
                x-cloak
                :src="docPreviewUrl"
                :alt="docTitle"
                class="mx-auto block h-auto max-h-[min(75vh,36rem)] w-full object-contain"
            />
            <iframe
                x-show="docKind === 'pdf'"
                x-cloak
                :src="docPreviewUrl"
                :title="docTitle"
                class="mx-auto block h-[min(75vh,36rem)] w-full rounded-xl border border-slate-200 bg-white shadow-sm"
            ></iframe>
            <div x-show="docKind !== 'image' && docKind !== 'pdf'" x-cloak class="rounded-xl border border-dashed border-slate-200 bg-white px-4 py-8 text-center text-sm text-slate-600">
                <p>{{ __('bookings.show.doc_preview_unsupported') }}</p>
                <a :href="docPreviewUrl" target="_blank" rel="noopener noreferrer" class="mt-3 inline-flex font-semibold text-brand-700 hover:underline">
                    {{ __('bookings.show.doc_open_tab') }}
                </a>
            </div>
        </div>
        <div class="flex shrink-0 justify-end gap-2 border-t border-slate-100 px-4 py-3 sm:px-5">
            <a
                x-show="docPreviewUrl"
                :href="docPreviewUrl + (docPreviewUrl.includes('?') ? '&' : '?') + 'download=1'"
                class="inline-flex items-center gap-1.5 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-800 transition hover:bg-emerald-100"
            >
                {{ __('bookings.show.doc_download') }}
            </a>
        </div>
    </div>
</div>
