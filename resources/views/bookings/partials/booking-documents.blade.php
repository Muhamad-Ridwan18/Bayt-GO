@props([
    'booking',
    'routeName' => 'bookings.documents.show',
])

@php
    $hasAny = filled($booking->ticket_outbound_path)
        || filled($booking->ticket_return_path)
        || filled($booking->itinerary_path)
        || filled($booking->visa_path);
@endphp

@if ($hasAny)
    <div class="mt-5 rounded-2xl border border-slate-200/90 bg-slate-50/50 p-4 ring-1 ring-slate-100/80 sm:p-5">
        <h3 class="text-sm font-bold text-slate-900">{{ __('bookings.show.documents_heading') }}</h3>
        <p class="mt-1 text-xs text-slate-600">{{ __('bookings.show.documents_intro') }}</p>
        <ul class="mt-3 space-y-2 text-sm">
            @if (filled($booking->ticket_outbound_path))
                <li class="flex flex-wrap items-center justify-between gap-2">
                    <span class="text-slate-700">{{ __('bookings.show.doc_outbound') }}</span>
                    <a href="{{ route($routeName, [$booking, 'outbound']) }}" class="font-semibold text-brand-700 hover:text-brand-800 hover:underline">{{ __('bookings.show.doc_download') }}</a>
                </li>
            @endif
            @if (filled($booking->ticket_return_path))
                <li class="flex flex-wrap items-center justify-between gap-2">
                    <span class="text-slate-700">{{ __('bookings.show.doc_return') }}</span>
                    <a href="{{ route($routeName, [$booking, 'return']) }}" class="font-semibold text-brand-700 hover:text-brand-800 hover:underline">{{ __('bookings.show.doc_download') }}</a>
                </li>
            @endif
            @if (filled($booking->itinerary_path))
                <li class="flex flex-wrap items-center justify-between gap-2">
                    <span class="text-slate-700">{{ __('bookings.show.doc_itinerary') }}</span>
                    <a href="{{ route($routeName, [$booking, 'itinerary']) }}" class="font-semibold text-brand-700 hover:text-brand-800 hover:underline">{{ __('bookings.show.doc_download') }}</a>
                </li>
            @endif
            @if (filled($booking->visa_path))
                <li class="flex flex-wrap items-center justify-between gap-2">
                    <span class="text-slate-700">{{ __('bookings.show.doc_visa') }}</span>
                    <a href="{{ route($routeName, [$booking, 'visa']) }}" class="font-semibold text-brand-700 hover:text-brand-800 hover:underline">{{ __('bookings.show.doc_download') }}</a>
                </li>
            @endif
        </ul>
    </div>
@endif
