@props(['message'])

@php
    $urls = $message->attachmentUrls();
@endphp

@if (count($urls) > 0)
    <div class="mt-3 border-t border-slate-100 pt-3">
        <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('support.attachments_heading') }}</p>
        <div class="flex flex-wrap gap-3">
            @foreach ($urls as $att)
                @if ($att['is_image'])
                    <a href="{{ $att['url'] }}" target="_blank" rel="noopener noreferrer" class="group block shrink-0 rounded-xl border border-slate-200 bg-slate-50/80 p-1 shadow-sm ring-1 ring-slate-100 transition hover:ring-brand-200">
                        <img src="{{ $att['url'] }}" alt="{{ __('support.attachment_image_alt', ['name' => $att['original_name']]) }}" class="max-h-36 max-w-[12rem] rounded-lg object-contain" loading="lazy">
                        <span class="mt-1 block max-w-[12rem] truncate px-1 text-[11px] text-slate-600 group-hover:text-brand-700" title="{{ e($att['original_name']) }}">{{ e($att['original_name']) }}</span>
                    </a>
                @else
                    <a href="{{ $att['url'] }}" target="_blank" rel="noopener noreferrer" class="inline-flex max-w-[14rem] items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-medium text-slate-800 shadow-sm transition hover:border-brand-200 hover:text-brand-800">
                        <svg class="h-4 w-4 shrink-0 text-slate-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/></svg>
                        <span class="truncate" title="{{ e($att['original_name']) }}">{{ e($att['original_name']) }}</span>
                    </a>
                @endif
            @endforeach
        </div>
    </div>
@endif
