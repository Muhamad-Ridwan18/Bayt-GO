@props([
    'booking',
    'routeName' => 'bookings.documents.show',
    'compact' => false,
])

@php
    $hasAny = filled($booking->ticket_outbound_path)
        || filled($booking->ticket_return_path)
        || filled($booking->itinerary_path)
        || filled($booking->visa_path);

    $items = [
        ['type' => 'outbound', 'path' => $booking->ticket_outbound_path, 'label' => __('bookings.show.doc_outbound')],
        ['type' => 'return', 'path' => $booking->ticket_return_path, 'label' => __('bookings.show.doc_return')],
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
@endphp

@if ($hasAny)
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
