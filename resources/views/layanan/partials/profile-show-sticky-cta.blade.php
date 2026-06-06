@php
    /** Sticky mobile CTA — expects: $profile, $canBook, $bookingPageUrl, $searchRangeLabel */
@endphp

<div
        class="fixed inset-x-0 bottom-0 z-40 border-t border-slate-200/90 bg-white p-3 shadow-[0_-8px_24px_-8px_rgba(15,23,42,0.12)] lg:hidden"
    role="region"
    aria-label="{{ __('layanan.profile_cta.section_aria') }}"
>
    <div class="mx-auto flex max-w-lg items-center gap-3">
        <div class="min-w-0 flex-1">
            <p class="truncate text-sm font-semibold text-slate-900">{{ $profile->user->name }}</p>
            @if ($searchRangeLabel)
                <p class="truncate text-xs tabular-nums text-slate-500">{{ $searchRangeLabel }}</p>
            @endif
        </div>
        @if ($canBook)
            <a href="{{ $bookingPageUrl }}" class="ui-btn-primary shrink-0 px-5 py-2.5 text-sm">
                {{ __('marketplace.show.book_now') }}
            </a>
        @else
            <a href="{{ $bookingPageUrl }}" class="ui-btn-secondary shrink-0 px-4 py-2.5 text-sm">
                {{ __('layanan.profile_cta.open_booking_page') }}
            </a>
        @endif
    </div>
</div>
