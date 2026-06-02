@props([
    'booking',
    'routeName' => 'bookings.documents.show',
    'compact' => false,
    'variant' => 'default',
    'maxVisible' => null,
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
@endphp

@if ($hasAny)
    @if ($variant === 'list')
        @php
            $visibleItems = collect($items)->filter(fn ($item) => filled($item['path']));
            if ($maxVisible !== null) {
                $visibleItems = $visibleItems->take((int) $maxVisible);
            }
        @endphp
        <ul class="space-y-2">
            @foreach ($visibleItems as $item)
                <li class="flex items-center justify-between gap-2 rounded-lg border border-slate-100 bg-slate-50/60 px-3 py-2">
                    <span class="inline-flex min-w-0 items-center gap-2 text-sm text-slate-800">
                        <svg class="h-4 w-4 shrink-0 text-slate-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M4.5 2A1.5 1.5 0 003 3.5v13A1.5 1.5 0 004.5 18h11a1.5 1.5 0 001.5-1.5V7.621a1.5 1.5 0 00-.44-1.06L11.939 3.44A1.5 1.5 0 0010.939 3H4.5zm2 1.5h6.439l3.122 3.12A.5.5 0 0116 7.121V16.5a.5.5 0 01-.5.5h-11a.5.5 0 01-.5-.5V4.5a.5.5 0 01.5-.5z" clip-rule="evenodd" />
                        </svg>
                        <span class="truncate font-medium">{{ $item['label'] }}</span>
                    </span>
                    <a href="{{ $downloadUrl($item['type']) }}" class="shrink-0 text-xs font-semibold text-emerald-700 hover:text-emerald-800 hover:underline">
                        {{ __('bookings.show.doc_download') }}
                    </a>
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
            <div class="grid grid-cols-2 gap-3 p-5 sm:grid-cols-3 sm:p-6 lg:grid-cols-5">
                @foreach ($items as $item)
                    @continue(! filled($item['path']))
                    @php($kind = $docKind($item['path']))
                    <div class="flex min-w-0 flex-col rounded-xl border border-slate-100 bg-slate-50/80 p-3">
                        <div class="flex items-start gap-2">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg {{ $kind === 'pdf' ? 'bg-red-50 text-red-600' : 'bg-white text-slate-500 ring-1 ring-slate-100' }}">
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
                            <div class="min-w-0 flex-1">
                                <p class="text-xs font-semibold leading-snug text-slate-900">{{ $item['label'] }}</p>
                                <p class="mt-0.5 text-[11px] text-slate-500">{{ $docFormatLabel($item['path']) }}</p>
                            </div>
                        </div>
                        <a
                            href="{{ $previewUrl($item['type']) }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="mt-2.5 inline-flex items-center gap-1 text-xs font-semibold text-brand-700 hover:text-brand-800"
                        >
                            {{ __('bookings.show.doc_view') }}
                            <svg class="h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M4.25 5.5a.75.75 0 01.75-.75h8.5a.75.75 0 01.75.75v8.5a.75.75 0 01-.75.75h-8.5a.75.75 0 01-.75-.75v-8.5zm1.5 1.5v7h7v-7h-7z" clip-rule="evenodd" />
                                <path d="M6.75 2.75a.75.75 0 00-1.5 0v1.5h1.5V2.75zm6 0a.75.75 0 00-1.5 0v1.5h1.5V2.75zM6.75 15.25a.75.75 0 00-1.5 0v1.5h1.5v-1.5zm6 0a.75.75 0 00-1.5 0v1.5h1.5v-1.5z" />
                            </svg>
                        </a>
                    </div>
                @endforeach
            </div>
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

            <div @class(['mt-3 space-y-5' => ! $compact, 'mt-2 flex flex-wrap gap-3' => $compact])>
                @foreach ($items as $item)
                    @continue(! filled($item['path']))
                    @php($kind = $docKind($item['path']))
                    <div @class(['space-y-2', 'min-w-0 flex-1 basis-[calc(50%-0.375rem)] sm:basis-[calc(25%-0.5625rem)]' => $compact])>
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <span class="text-xs font-medium text-slate-700 sm:text-sm">{{ $item['label'] }}</span>
                            <a href="{{ $downloadUrl($item['type']) }}" class="shrink-0 text-[11px] font-semibold text-slate-500 hover:text-brand-700 hover:underline sm:text-xs">
                                {{ __('bookings.show.doc_download') }}
                            </a>
                        </div>

                        @if ($kind === 'image')
                            <a href="{{ $previewUrl($item['type']) }}" target="_blank" rel="noopener noreferrer" class="block overflow-hidden rounded-xl border border-slate-200/90 bg-white ring-1 ring-slate-100">
                                <img
                                    src="{{ $previewUrl($item['type']) }}"
                                    alt="{{ $item['label'] }}"
                                    loading="lazy"
                                    @class([
                                        'h-auto w-full object-contain',
                                        'max-h-[min(28rem,70vh)]' => ! $compact,
                                        'max-h-32' => $compact,
                                    ])
                                />
                            </a>
                        @elseif ($kind === 'pdf')
                            <div @class(['overflow-hidden rounded-xl border border-slate-200/90 bg-white ring-1 ring-slate-100', 'max-w-full' => $compact])>
                                <iframe
                                    src="{{ $previewUrl($item['type']) }}"
                                    title="{{ $item['label'] }}"
                                    class="w-full rounded-lg border-0 bg-white"
                                    @if ($compact)
                                        style="min-height: 9rem; height: 9rem;"
                                    @else
                                        style="min-height: 24rem; height: min(70vh, 36rem);"
                                    @endif
                                ></iframe>
                            </div>
                        @else
                            <div class="rounded-xl border border-dashed border-slate-200 bg-white/80 px-3 py-2 text-xs text-slate-600">
                                <a href="{{ $previewUrl($item['type']) }}" target="_blank" rel="noopener noreferrer" class="font-semibold text-brand-700 hover:underline">{{ __('bookings.show.doc_open_tab') }}</a>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif
@endif
