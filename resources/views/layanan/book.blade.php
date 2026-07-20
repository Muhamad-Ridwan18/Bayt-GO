<x-marketplace-layout :title="__('layanan.book_document_title', ['name' => $page->profile->user->name])" wide>
    <div class="ui-booking-page">
        <nav aria-label="{{ __('layanan.book_breadcrumb_aria') }}" class="ui-toolbar text-sm">
            <a href="{{ $page->indexedUrl }}" class="font-semibold text-brand-700 hover:text-brand-800">{{ __('layanan.breadcrumb_find') }}</a>
            <span class="text-slate-300" aria-hidden="true">/</span>
            <a href="{{ $page->profileUrl }}" class="max-w-[12rem] truncate font-medium text-slate-700 hover:text-brand-800 sm:max-w-xs">{{ $page->profile->user->name }}</a>
            <span class="text-slate-300" aria-hidden="true">/</span>
            <span class="font-bold text-slate-900">{{ __('layanan.book_breadcrumb_here') }}</span>
        </nav>

        <div id="booking-box" class="min-w-0 scroll-mt-24">
            @include('layanan.partials.booking-panel', [
                'profile' => $page->profile,
                'group' => $page->group,
                'private' => $page->private,
                'page' => $page->panel,
            ])
        </div>

        @include('layanan.partials.book-sticky-cta', [
            'profile' => $page->profile,
            'canSubmit' => $page->canSubmit,
            'searchRangeLabel' => $page->searchRangeLabel,
        ])
    </div>
</x-marketplace-layout>
