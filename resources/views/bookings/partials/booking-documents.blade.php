@props([
    'booking',
    'routeName' => 'bookings.documents.show',
    'compact' => false,
    'variant' => 'default',
    'maxVisible' => null,
    'collapseLimit' => null,
    'actionStyle' => 'link',
])

@php
    $hasAny = filled($booking->ticket_outbound_path)
        || filled($booking->ticket_return_path)
        || filled($booking->passport_path)
        || filled($booking->itinerary_path)
        || filled($booking->visa_path);

    $items = [
        ['type' => 'outbound', 'path' => $booking->ticket_outbound_path, 'label' => __('bookings.show.doc_outbound')],
        ['type' => 'return', 'path' => $booking->ticket_return_path, 'label' => __('bookings.show.doc_return')],
        ['type' => 'passport', 'path' => $booking->passport_path, 'label' => __('bookings.show.doc_passport')],
        ['type' => 'itinerary', 'path' => $booking->itinerary_path, 'label' => __('bookings.show.doc_itinerary')],
        ['type' => 'visa', 'path' => $booking->visa_path, 'label' => __('bookings.show.doc_visa')],
    ];

    $previewUrl = fn (string $type) => route($routeName, [$booking, $type]);
    $downloadUrl = fn (string $type) => route($routeName, [$booking, $type]).'?download=1';

    $docKind = static function (?string $path): string {
        if (! filled($path)) {
            return 'none';
        }
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return match ($ext) {
            'jpg', 'jpeg', 'png', 'gif', 'webp' => 'image',
            'pdf' => 'pdf',
            default => 'file',
        };
    };

    $docFormatLabel = static function (?string $path) use ($docKind): string {
        $kind = $docKind($path);
        if ($kind === 'pdf') {
            return 'PDF';
        }
        if ($kind === 'image') {
            return strtoupper(pathinfo((string) $path, PATHINFO_EXTENSION) ?: 'IMG');
        }

        return strtoupper(pathinfo((string) $path, PATHINFO_EXTENSION) ?: __('bookings.show.doc_file'));
    };

    $previewInParent = $variant === 'list';
@endphp

@if ($hasAny)
    @unless ($previewInParent)
        <div
            x-data="{
                docModalOpen: false,
                docTitle: '',
                docPreviewUrl: '',
                docKind: 'image',
                openDocPreview(title, url, kind) {
                    this.docTitle = title;
                    this.docPreviewUrl = url;
                    this.docKind = kind;
                    this.docModalOpen = true;
                    document.body.classList.add('overflow-y-hidden');
                },
                closeDocPreview() {
                    this.docModalOpen = false;
                    document.body.classList.remove('overflow-y-hidden');
                },
            }"
            @keydown.escape.window="docModalOpen && closeDocPreview()"
        >
    @endunless

    @if ($variant === 'list')
        @php
            $visibleItems = collect($items)->filter(fn ($item) => filled($item['path']))->values();
            if ($maxVisible !== null) {
                $visibleItems = $visibleItems->take((int) $maxVisible);
            }
            $useCollapse = $collapseLimit !== null && $collapseLimit > 0;
        @endphp
        <ul class="space-y-2">
            @foreach ($visibleItems as $index => $item)
                @php($kind = $docKind($item['path']))
                <li
                    @class([
                        'flex items-center justify-between gap-2 rounded-lg border border-slate-100 bg-white px-3 py-2.5 shadow-sm',
                    ])
                    @if ($useCollapse)
                        x-show="showAllDocs || {{ $index }} < {{ (int) $collapseLimit }}"
                    @endif
                >
                    <span class="inline-flex min-w-0 items-center gap-2 text-sm text-slate-800">
                        <svg class="h-4 w-4 shrink-0 text-slate-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M4.5 2A1.5 1.5 0 003 3.5v13A1.5 1.5 0 004.5 18h11a1.5 1.5 0 001.5-1.5V7.621a1.5 1.5 0 00-.44-1.06L11.939 3.44A1.5 1.5 0 0010.939 3H4.5zm2 1.5h6.439l3.122 3.12A.5.5 0 0116 7.121V16.5a.5.5 0 01-.5.5h-11a.5.5 0 01-.5-.5V4.5a.5.5 0 01.5-.5z" clip-rule="evenodd" />
                        </svg>
                        <span class="truncate font-medium">{{ $item['label'] }}</span>
                    </span>
                    @include('bookings.partials.booking-documents-doc-actions', [
                        'item' => $item,
                        'kind' => $kind,
                        'previewUrl' => $previewUrl,
                        'downloadUrl' => $downloadUrl,
                        'actionStyle' => $actionStyle,
                    ])
                </li>
            @endforeach
        </ul>
    @elseif ($variant === 'cards')
        <section class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-sm ring-1 ring-slate-100/80">
            <div class="border-b border-slate-100 px-5 py-4 sm:px-6">
                <h2 class="flex items-center gap-2 text-base font-bold text-slate-900">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-brand-50 text-brand-700">
                        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M4.5 2A1.5 1.5 0 003 3.5v13A1.5 1.5 0 004.5 18h11a1.5 1.5 0 001.5-1.5V7.621a1.5 1.5 0 00-.44-1.06L11.939 3.44A1.5 1.5 0 0010.939 3H4.5zm2 1.5h6.439l3.122 3.12A.5.5 0 0116 7.121V16.5a.5.5 0 01-.5.5h-11a.5.5 0 01-.5-.5V4.5a.5.5 0 01.5-.5z" clip-rule="evenodd" />
                        </svg>
                    </span>
                    {{ __('bookings.show.documents_heading') }}
                </h2>
                <p class="mt-1 text-xs text-slate-600">{{ __('bookings.show.documents_intro_cards') }}</p>
            </div>
            <ul class="divide-y divide-slate-100">
                @foreach ($items as $item)
                    @continue(! filled($item['path']))
                    @php($kind = $docKind($item['path']))
                    <li class="flex flex-wrap items-center justify-between gap-3 px-5 py-3.5 sm:px-6">
                        <div class="flex min-w-0 items-center gap-3">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg {{ $kind === 'pdf' ? 'bg-red-50 text-red-600' : 'bg-slate-100 text-slate-500 ring-1 ring-slate-200/80' }}">
                                @if ($kind === 'pdf')
                                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M4.5 2A1.5 1.5 0 003 3.5v13A1.5 1.5 0 004.5 18h11a1.5 1.5 0 001.5-1.5V7.621a1.5 1.5 0 00-.44-1.06L11.939 3.44A1.5 1.5 0 0010.939 3H4.5zm2 1.5h6.439l3.122 3.12A.5.5 0 0116 7.121V16.5a.5.5 0 01-.5.5h-11a.5.5 0 01-.5-.5V4.5a.5.5 0 01.5-.5z" clip-rule="evenodd" />
                                    </svg>
                                @else
                                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M1.5 6a2.25 2.25 0 012.25-2.25h8.086a2.25 2.25 0 011.591.659l2.829 2.828A2.25 2.25 0 0118 8.914V15a2.25 2.25 0 01-2.25 2.25H3.75A2.25 2.25 0 011.5 15V6zm2.25-1.5a.75.75 0 00-.75.75v9.75c0 .414.336.75.75.75h12.75a.75.75 0 00.75-.75V8.914a.75.75 0 00-.22-.53l-2.829-2.828a.75.75 0 00-.53-.22H3.75z" clip-rule="evenodd" />
                                    </svg>
                                @endif
                            </span>
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-slate-900">{{ $item['label'] }}</p>
                                <p class="text-[11px] text-slate-500">{{ $docFormatLabel($item['path']) }}</p>
                            </div>
                        </div>
                        @include('bookings.partials.booking-documents-doc-actions', [
                            'item' => $item,
                            'kind' => $kind,
                            'previewUrl' => $previewUrl,
                            'downloadUrl' => $downloadUrl,
                        ])
                    </li>
                @endforeach
            </ul>
            <p class="flex items-center gap-2 border-t border-slate-100 px-5 py-3 text-[11px] text-slate-500 sm:px-6">
                <svg class="h-3.5 w-3.5 shrink-0 text-slate-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M10 1a4.5 4.5 0 00-4.5 4.5V9H5a2 2 0 00-2 2v6a2 2 0 002 2h10a2 2 0 002-2v-6a2 2 0 00-2-2h-.5V5.5A4.5 4.5 0 0010 1zm3 8V5.5a3 3 0 10-6 0V9h6z" clip-rule="evenodd" />
                </svg>
                {{ __('bookings.show.documents_privacy_note') }}
            </p>
        </section>
    @else
        <div @class([
            'rounded-2xl border border-slate-200/90 bg-slate-50/50 p-4 ring-1 ring-slate-100/80 sm:p-5',
            'mt-5' => ! $compact,
            'mt-3' => $compact,
        ])>
            <h3 @class(['font-bold text-slate-900', 'text-sm' => ! $compact, 'text-[0.65rem] font-semibold uppercase tracking-wide text-slate-500' => $compact])>
                {{ __('bookings.show.documents_heading') }}
            </h3>
            @unless ($compact)
                <p class="mt-1 text-xs text-slate-600">{{ __('bookings.show.documents_intro_inline') }}</p>
            @endunless

            <ul @class(['mt-3 space-y-2', 'mt-2' => $compact])>
                @foreach ($items as $item)
                    @continue(! filled($item['path']))
                    @php($kind = $docKind($item['path']))
                    <li @class([
                        'flex items-center justify-between gap-2 rounded-lg border border-slate-100 bg-white/80 px-3 py-2',
                        'text-sm' => ! $compact,
                    ])>
                        <span class="min-w-0 font-medium text-slate-800">{{ $item['label'] }}</span>
                        @include('bookings.partials.booking-documents-doc-actions', [
                            'item' => $item,
                            'kind' => $kind,
                            'previewUrl' => $previewUrl,
                            'downloadUrl' => $downloadUrl,
                        ])
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    @unless ($previewInParent)
        @include('bookings.partials.booking-documents-preview-modal')
        </div>
    @endunless
@endif
