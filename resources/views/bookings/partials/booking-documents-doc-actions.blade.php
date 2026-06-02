{{-- $item, $kind, $previewUrl, $downloadUrl --}}
<span class="flex shrink-0 items-center gap-2">
    <button
        type="button"
        class="text-xs font-semibold text-brand-700 hover:text-brand-800 hover:underline"
        @click.stop="openDocPreview(@js($item['label']), @js($previewUrl($item['type'])), @js($kind))"
    >
        {{ __('bookings.show.doc_view') }}
    </button>
    <span class="text-slate-300" aria-hidden="true">·</span>
    <a href="{{ $downloadUrl($item['type']) }}" class="text-xs font-semibold text-emerald-700 hover:text-emerald-800 hover:underline" @click.stop>
        {{ __('bookings.show.doc_download') }}
    </a>
</span>
