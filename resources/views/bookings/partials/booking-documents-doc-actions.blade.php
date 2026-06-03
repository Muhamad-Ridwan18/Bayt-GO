{{-- $item, $kind, $previewUrl, $downloadUrl, $actionStyle --}}
@php
    $actionStyle = $actionStyle ?? 'link';
@endphp
@if ($actionStyle === 'pill')
    <span class="flex shrink-0 items-center gap-1.5">
        <button
            type="button"
            class="inline-flex items-center gap-1 rounded-md bg-emerald-50 px-2 py-1 text-[11px] font-semibold text-emerald-800 ring-1 ring-emerald-100 transition hover:bg-emerald-100"
            @click.stop="openDocPreview(@js($item['label']), @js($previewUrl($item['type'])), @js($kind))"
        >
            <svg class="h-3 w-3" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M10 12a2 2 0 100-4 2 2 0 000 4z" /><path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7-4.478 0-8.268-2.943-9.542-7zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd" /></svg>
            {{ __('bookings.show.doc_view') }}
        </button>
        <a href="{{ $downloadUrl($item['type']) }}" class="inline-flex items-center gap-1 rounded-md bg-emerald-50 px-2 py-1 text-[11px] font-semibold text-emerald-800 ring-1 ring-emerald-100 transition hover:bg-emerald-100" @click.stop>
            <svg class="h-3 w-3" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M10.75 2.75a.75.75 0 00-1.5 0v8.614L6.295 8.235a.75.75 0 10-1.09 1.03l4.25 4.5a.75.75 0 001.09 0l4.25-4.5a.75.75 0 00-1.09-1.03l-2.955 3.129V2.75z" /><path d="M3.5 12.75a.75.75 0 00-1.5 0v2.5A2.75 2.75 0 004.75 18h10.5A2.75 2.75 0 0018 15.25v-2.5a.75.75 0 00-1.5 0v2.5c0 .69-.56 1.25-1.25 1.25H4.75c-.69 0-1.25-.56-1.25-1.25v-2.5z" /></svg>
            {{ __('bookings.show.doc_download') }}
        </a>
    </span>
@else
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
@endif
